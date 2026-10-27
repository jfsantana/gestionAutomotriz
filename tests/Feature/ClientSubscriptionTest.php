<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\CustomerPayment;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Users\Models\PlatformPolicy;
use App\Domain\Users\Models\UserPolicyAcceptance;
use App\Domain\Vehicles\Models\Vehicle;
use App\Mail\ClientFleetPaymentRequestedMail;
use App\Models\User;
use Database\Seeders\PlatformPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_customer_creates_a_requested_monthly_payment_without_being_upgraded(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser('individual');
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);

        $response = $this->from(route('portal.vehicles.index'))
            ->actingAs($user)
            ->post(route('portal.customers.upgrade-to-fleet', $customer), ['billing_cycle' => 'monthly']);

        $response->assertRedirect(route('portal.vehicles.index'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'USD 15.00'));
        $this->assertDatabaseHas('pagos_clientes', [
            'customer_id' => $customer->id,
            'customer_type' => 'individual',
            'vehicle_count' => 4,
            'billing_cycle' => 'monthly',
            'amount' => '15.00',
            'currency' => 'USD',
            'status' => 'requested',
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_fleet_premium' => false,
            'vehicle_limit' => 3,
        ]);
    }

    public function test_fleet_owner_annual_price_uses_its_rates_and_charges_eleven_months(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser('fleet_owner');
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);

        $response = $this->actingAs($user)
            ->post(route('portal.customers.upgrade-to-fleet', $customer), ['billing_cycle' => 'annual']);

        $response->assertRedirect();
        $payment = CustomerPayment::query()->sole();
        $this->assertSame('187.00', $payment->amount);
        $this->assertSame(11, $payment->pricing_breakdown['billing_months_charged']);
        $this->assertSame('17.00', $payment->pricing_breakdown['monthly_total']);
    }

    public function test_super_admin_confirmation_activates_premium_and_sets_the_annual_expiration(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 15)->startOfDay());
        [$customer, $user] = $this->createCustomerAndPortalUser('fleet_owner');
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'annual',
        ]);
        $payment = CustomerPayment::query()->sole();
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)
            ->patch(route('client-subscriptions.confirm', $payment));

        $response->assertRedirect();
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $payment->id,
            'status' => 'paid',
            'confirmed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_fleet_premium' => true,
            'vehicle_limit' => null,
            'fleet_expires_at' => '2027-01-15 00:00:00',
        ]);
    }

    public function test_super_admin_confirmation_sets_a_one_month_expiration_for_monthly_payment(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 15)->startOfDay());
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);
        $payment = CustomerPayment::query()->sole();
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)
            ->patch(route('client-subscriptions.confirm', $payment));

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_fleet_premium' => true,
            'vehicle_limit' => null,
            'fleet_expires_at' => '2026-02-15 00:00:00',
        ]);
    }

    public function test_super_admin_confirmation_ignores_json_object_key_order_in_the_pricing_snapshot(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);
        $payment = CustomerPayment::query()->sole();
        $pricingBreakdown = $payment->pricing_breakdown;
        $pricingBreakdown['vehicles'] = array_reverse($pricingBreakdown['vehicles'], true);
        $payment->update([
            'pricing_breakdown' => array_reverse($pricingBreakdown, true),
            'status' => 'superseded',
        ]);
        $admin = $this->createSuperAdmin();

        $response = $this->from(route('client-subscriptions.index'))
            ->actingAs($admin)
            ->patch(route('client-subscriptions.confirm', $payment));

        $response->assertRedirect(route('client-subscriptions.index'))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $payment->id,
            'status' => 'paid',
            'confirmed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_fleet_premium' => true,
            'vehicle_limit' => null,
        ]);
    }

    public function test_super_admin_can_reverse_a_confirmed_payment_and_revoke_its_current_subscription(): void
    {
        $this->travelTo('2026-01-15 12:00:00');
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'annual',
        ]);
        $payment = CustomerPayment::query()->sole();
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin)->patch(route('client-subscriptions.confirm', $payment));
        $this->actingAs($admin)
            ->get(route('client-subscriptions.index'))
            ->assertOk()
            ->assertSee('Revertir pago');

        $response = $this->from(route('client-subscriptions.index'))
            ->actingAs($admin)
            ->patch(route('client-subscriptions.reverse', $payment));

        $response->assertRedirect(route('client-subscriptions.index'))
            ->assertSessionHas('success')
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $payment->id,
            'status' => 'reversed',
            'reversed_by' => $admin->id,
            'reversed_at' => '2026-01-15 12:00:00',
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_fleet_premium' => false,
            'vehicle_limit' => 3,
            'fleet_expires_at' => null,
        ]);
    }

    public function test_reversing_an_old_payment_does_not_cancel_a_later_subscription(): void
    {
        $this->travelTo('2026-01-15 12:00:00');
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $admin = $this->createSuperAdmin();

        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'annual',
        ]);
        $oldPayment = CustomerPayment::query()->sole();
        $this->actingAs($admin)->patch(route('client-subscriptions.confirm', $oldPayment));

        $this->travelTo('2027-01-15 12:00:00');
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'annual',
        ]);
        $currentPayment = CustomerPayment::query()->where('status', 'requested')->sole();
        $this->actingAs($admin)->patch(route('client-subscriptions.confirm', $currentPayment));

        $response = $this->from(route('client-subscriptions.index'))
            ->actingAs($admin)
            ->patch(route('client-subscriptions.reverse', $oldPayment));

        $response->assertRedirect(route('client-subscriptions.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_fleet_premium' => true,
            'fleet_expires_at' => '2028-01-15 12:00:00',
        ]);
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $oldPayment->id,
            'status' => 'reversed',
        ]);
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $currentPayment->id,
            'status' => 'paid',
        ]);
    }

    public function test_customer_cannot_reverse_a_payment_twice(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);
        $payment = CustomerPayment::query()->sole();
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin)->patch(route('client-subscriptions.confirm', $payment));
        $this->actingAs($admin)->patch(route('client-subscriptions.reverse', $payment));

        $response = $this->from(route('client-subscriptions.index'))
            ->actingAs($admin)
            ->patch(route('client-subscriptions.reverse', $payment));

        $response->assertRedirect(route('client-subscriptions.index'))
            ->assertSessionHasErrors('payment');
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $payment->id,
            'status' => 'reversed',
        ]);
    }

    public function test_super_admin_can_review_the_payment_request_and_its_amount(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->get(route('client-subscriptions.index'));

        $response->assertOk()
            ->assertSee($customer->full_name)
            ->assertSee('USD 15.00')
            ->assertSee('Solicitud')
            ->assertSee($user->email)
            ->assertSee('Confirmar pago');
    }

    public function test_super_admin_can_hide_a_payment_request_without_deleting_its_database_record(): void
    {
        Mail::fake();
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);
        $payment = CustomerPayment::query()->sole();
        $admin = $this->createSuperAdmin();

        $response = $this->from(route('client-subscriptions.index'))
            ->actingAs($admin)
            ->delete(route('client-subscriptions.hide', $payment));

        $response->assertRedirect(route('client-subscriptions.index'))
            ->assertSessionHas('success');
        $this->assertTrue(CustomerPayment::withTrashed()->findOrFail($payment->id)->trashed());
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $payment->id,
            'status' => 'requested',
        ]);
        $this->assertSame(0, CustomerPayment::query()->count());
        $this->actingAs($admin)
            ->get(route('client-subscriptions.index'))
            ->assertOk()
            ->assertDontSee($customer->full_name);
    }

    public function test_super_admin_cannot_hide_a_confirmed_payment_from_the_report(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);
        $payment = CustomerPayment::query()->sole();
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin)->patch(route('client-subscriptions.confirm', $payment));

        $response = $this->from(route('client-subscriptions.index'))
            ->actingAs($admin)
            ->delete(route('client-subscriptions.hide', $payment));

        $response->assertRedirect(route('client-subscriptions.index'))
            ->assertSessionHasErrors('payment');
        $this->assertNull($payment->fresh()->deleted_at);
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
    }

    public function test_customer_can_find_and_submit_the_upgrade_request_form_in_my_vehicles(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);

        $response = $this->actingAs($user)->get(route('portal.vehicles.index'));

        $response->assertOk()
            ->assertSee('Registrar solicitud de pago')
            ->assertSee('name="billing_cycle"', false)
            ->assertSee('value="monthly"', false)
            ->assertSee('value="annual"', false)
            ->assertSee('La solicitud no cobra automáticamente ni desbloquea los vehículos.')
            ->assertSee('Contactar a AutoBitácora')
            ->assertSee(route('portal.customers.upgrade-to-fleet', $customer), false);
    }

    public function test_customer_sees_the_payment_request_status_in_my_vehicles_after_requesting_upgrade(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);

        $response = $this->actingAs($user)->get(route('portal.vehicles.index'));

        $response->assertOk()
            ->assertSee('Tu solicitud está registrada y pendiente de confirmación por AutoBitácora.')
            ->assertSee('USD 15.00')
            ->assertSee('Enviamos un correo a ti y a AutoBitácora.');
    }

    public function test_customer_cannot_create_a_duplicate_payment_request(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $route = route('portal.customers.upgrade-to-fleet', $customer);
        $this->actingAs($user)->post($route, ['billing_cycle' => 'monthly']);
        $originalPayment = CustomerPayment::query()->sole();

        $response = $this->from(route('portal.vehicles.index'))
            ->actingAs($user)
            ->post($route, ['billing_cycle' => 'monthly']);

        $response->assertRedirect(route('portal.vehicles.index'))
            ->assertSessionHasErrors('billing_cycle');
        $this->assertDatabaseCount('pagos_clientes', 1);

        $replacement = $this->actingAs($user)->post($route, ['billing_cycle' => 'annual']);

        $replacement->assertRedirect();
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $originalPayment->id,
            'status' => 'superseded',
        ]);
        $this->assertDatabaseHas('pagos_clientes', [
            'customer_id' => $customer->id,
            'billing_cycle' => 'annual',
            'amount' => '165.00',
            'status' => 'requested',
        ]);
        $this->assertDatabaseCount('pagos_clientes', 2);
    }

    public function test_payment_request_emails_customer_and_each_active_super_admin(): void
    {
        Mail::fake();
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $admin = $this->createSuperAdmin();
        User::create([
            'name' => 'Super Admin Inactivo',
            'username' => 'inactive_super_admin',
            'email' => 'inactive-admin@example.test',
            'password' => 'secret',
            'role' => 'super_admin',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('portal.customers.upgrade-to-fleet', $customer), ['billing_cycle' => 'monthly']);

        $response->assertRedirect()
            ->assertSessionHas('success');
        Mail::assertSentTimes(ClientFleetPaymentRequestedMail::class, 2);
        Mail::assertSent(ClientFleetPaymentRequestedMail::class, function (ClientFleetPaymentRequestedMail $mail) use ($user): bool {
            return $mail->hasTo($user->email) && ! $mail->isSuperAdmin;
        });
        Mail::assertSent(ClientFleetPaymentRequestedMail::class, function (ClientFleetPaymentRequestedMail $mail) use ($admin): bool {
            return $mail->hasTo($admin->email) && $mail->isSuperAdmin;
        });
    }

    public function test_customer_can_request_plan_after_trial_expires_even_with_three_or_fewer_vehicles(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['carro']);
        $customer->update(['fleet_trial_started_at' => now()->subMonths(2)->subMinute()]);

        $response = $this->actingAs($user)
            ->post(route('portal.customers.upgrade-to-fleet', $customer), ['billing_cycle' => 'monthly']);

        $response->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('pagos_clientes', [
            'customer_id' => $customer->id,
            'vehicle_count' => 1,
            'amount' => '3.00',
            'status' => 'requested',
        ]);
    }

    public function test_customer_cannot_request_upgrade_within_trial_when_at_or_below_the_vehicle_limit(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['carro']);

        $response = $this->from(route('portal.vehicles.index'))
            ->actingAs($user)
            ->post(route('portal.customers.upgrade-to-fleet', $customer), ['billing_cycle' => 'monthly']);

        $response->assertRedirect(route('portal.vehicles.index'))
            ->assertSessionHasErrors('vehicles');
        $this->assertDatabaseCount('pagos_clientes', 0);
    }

    public function test_customer_must_submit_a_supported_billing_cycle(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);

        $response = $this->from(route('portal.vehicles.index'))
            ->actingAs($user)
            ->post(route('portal.customers.upgrade-to-fleet', $customer), ['billing_cycle' => 'weekly']);

        $response->assertRedirect(route('portal.vehicles.index'))
            ->assertSessionHasErrors('billing_cycle');
        $this->assertDatabaseCount('pagos_clientes', 0);
    }

    public function test_customer_cannot_request_pricing_when_a_vehicle_has_no_type(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $customer->vehicles()->first()->update(['vehicle_type' => null]);

        $response = $this->from(route('portal.vehicles.index'))
            ->actingAs($user)
            ->post(route('portal.customers.upgrade-to-fleet', $customer), ['billing_cycle' => 'monthly']);

        $response->assertRedirect(route('portal.vehicles.index'))
            ->assertSessionHasErrors('vehicles');
        $this->assertDatabaseCount('pagos_clientes', 0);
    }

    public function test_unauthenticated_customer_upgrade_request_redirects_to_login(): void
    {
        [$customer] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);

        $response = $this->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('pagos_clientes', 0);
    }

    public function test_payment_request_does_not_activate_if_vehicle_inventory_changes_before_confirmation(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);
        $payment = CustomerPayment::query()->sole();
        $this->createVehicles($customer, ['camion']);
        $admin = $this->createSuperAdmin();

        $response = $this->from(route('client-subscriptions.index'))
            ->actingAs($admin)
            ->patch(route('client-subscriptions.confirm', $payment));

        $response->assertRedirect(route('client-subscriptions.index'))
            ->assertSessionHasErrors('payment');
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $payment->id,
            'status' => 'superseded',
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_fleet_premium' => false,
            'vehicle_limit' => 3,
        ]);

        $newRequest = $this->actingAs($user)
            ->post(route('portal.customers.upgrade-to-fleet', $customer), ['billing_cycle' => 'monthly']);

        $newRequest->assertRedirect();
        $this->assertDatabaseHas('pagos_clientes', [
            'customer_id' => $customer->id,
            'status' => 'requested',
            'amount' => '20.00',
        ]);
    }

    public function test_customer_cannot_request_upgrade_for_another_customers_account(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        [$anotherCustomer] = $this->createCustomerAndPortalUser('individual', 'second');
        $this->createVehicles($anotherCustomer, ['motocicleta', 'carro', 'van', 'camion']);

        $response = $this->actingAs($user)
            ->post(route('portal.customers.upgrade-to-fleet', $anotherCustomer), ['billing_cycle' => 'monthly']);

        $response->assertNotFound();
        $this->assertDatabaseCount('pagos_clientes', 0);
    }

    public function test_non_super_admin_cannot_confirm_reverse_or_hide_a_customer_payment(): void
    {
        [$customer, $user] = $this->createCustomerAndPortalUser();
        $this->createVehicles($customer, ['motocicleta', 'carro', 'van', 'camion']);
        $this->actingAs($user)->post(route('portal.customers.upgrade-to-fleet', $customer), [
            'billing_cycle' => 'monthly',
        ]);
        $payment = CustomerPayment::query()->sole();
        $companyAdmin = User::create([
            'name' => 'Admin de Taller',
            'username' => 'workshop_admin',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'role' => 'company_admin',
            'company_id' => $customer->company_id,
            'is_active' => true,
        ]);
        $policy = PlatformPolicy::activePolicy();
        if ($policy) {
            UserPolicyAcceptance::create([
                'user_id' => $companyAdmin->id,
                'platform_policy_id' => $policy->id,
                'policy_version' => $policy->version,
                'accepted_at' => now(),
            ]);
        }

        $response = $this->actingAs($companyAdmin)
            ->patch(route('client-subscriptions.confirm', $payment));

        $response->assertForbidden();
        $this->actingAs($companyAdmin)
            ->patch(route('client-subscriptions.reverse', $payment))
            ->assertForbidden();
        $this->actingAs($companyAdmin)
            ->delete(route('client-subscriptions.hide', $payment))
            ->assertForbidden();
        $this->assertDatabaseHas('pagos_clientes', [
            'id' => $payment->id,
            'status' => 'requested',
        ]);
    }

    public function test_expired_fleet_subscriptions_restore_the_three_vehicle_limit(): void
    {
        [$customer] = $this->createCustomerAndPortalUser();
        $customer->update([
            'is_fleet_premium' => true,
            'vehicle_limit' => null,
            'fleet_expires_at' => now()->subMinute(),
        ]);

        $this->artisan('fleet-subscriptions:process-expired')
            ->expectsOutputToContain('Se restableció el límite de 3 vehículos para 1 clientes.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_fleet_premium' => false,
            'vehicle_limit' => 3,
        ]);
    }

    private function createCustomerAndPortalUser(string $customerType = 'individual', string $suffix = 'first'): array
    {
        $company = Company::create([
            'name' => "Taller {$suffix}",
            'slug' => "taller-{$suffix}",
            'status' => 'active',
        ]);
        $customer = Customer::create([
            'company_id' => $company->id,
            'full_name' => "Cliente {$suffix}",
            'customer_type' => $customerType,
            'fleet_trial_started_at' => now(),
        ]);
        $this->seed(PlatformPolicySeeder::class);
        $policy = PlatformPolicy::activePolicy();
        $user = User::create([
            'name' => "Usuario {$suffix}",
            'username' => "customer_{$suffix}",
            'email' => "customer_{$suffix}@example.test",
            'password' => 'secret',
            'role' => 'customer',
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);

        if ($policy) {
            UserPolicyAcceptance::create([
                'user_id' => $user->id,
                'platform_policy_id' => $policy->id,
                'policy_version' => $policy->version,
                'accepted_at' => now(),
            ]);
        }

        return [$customer, $user];
    }

    private function createVehicles(Customer $customer, array $types): void
    {
        $existingCount = $customer->allVehicles()->count();

        foreach ($types as $index => $type) {
            Vehicle::create([
                'customer_id' => $customer->id,
                'plate' => 'PAY'.$customer->id.'-'.($existingCount + $index),
                'vehicle_type' => $type,
                'make' => 'Toyota',
                'model' => 'Corolla',
            ]);
        }
    }

    private function createSuperAdmin(): User
    {
        $this->seed(PlatformPolicySeeder::class);
        $policy = PlatformPolicy::activePolicy();
        $admin = User::create([
            'name' => 'Super Admin',
            'username' => 'super_admin',
            'email' => 'superadmin@example.test',
            'password' => 'secret',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        if ($policy) {
            UserPolicyAcceptance::create([
                'user_id' => $admin->id,
                'platform_policy_id' => $policy->id,
                'policy_version' => $policy->version,
                'accepted_at' => now(),
            ]);
        }

        return $admin;
    }
}

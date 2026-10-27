<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\CompanyPaymentRequest;
use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\CustomerPayment;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Mail\CompanySubscriptionPaymentRequestedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CompanySubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_keeps_operational_access_during_the_three_day_grace_period(): void
    {
        $this->travelTo('2026-10-22 12:00:00');
        [$company, $admin] = $this->createWorkshopSubscription([
            'status' => 'active',
            'ends_at' => '2026-10-20',
        ]);

        $response = $this->actingAs($admin)->get('/customers');

        $response->assertOk()
            ->assertSee('Periodo de gracia')
            ->assertSee('los módulos siguen disponibles durante el periodo de gracia');
        $this->assertSame($company->id, $admin->company_id);

        $this->travelTo('2026-10-23 23:59:59');
        $this->actingAs($admin)->get('/customers')->assertOk();

        $this->travelTo('2026-10-24 00:00:00');
        $this->actingAs($admin)
            ->get('/customers')
            ->assertRedirect(route('subscription.status'));
        $this->actingAs($admin)
            ->get(route('company.dashboard'))
            ->assertRedirect(route('subscription.status'));
    }

    public function test_company_without_an_assigned_subscription_is_blocked_even_if_its_company_status_is_trial(): void
    {
        $company = Company::create([
            'name' => 'Taller sin plan asignado',
            'slug' => 'taller-sin-plan-asignado',
            'status' => 'trial',
        ]);
        $admin = User::factory()->create([
            'role' => 'company_admin',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/customers')
            ->assertRedirect(route('subscription.status'));
        $this->actingAs($admin)
            ->get(route('company.dashboard'))
            ->assertRedirect(route('subscription.status'));

        $this->actingAs($admin)
            ->get(route('subscription.status'))
            ->assertOk()
            ->assertSee('No hay una suscripción registrada para esta empresa.')
            ->assertSee('No es posible registrar una renovación en el estado actual.');
    }

    public function test_company_admin_can_request_payment_after_grace_while_operational_routes_are_blocked(): void
    {
        $this->travelTo('2026-10-24 12:00:00');
        [, $admin, $plan] = $this->createWorkshopSubscription([
            'status' => 'expired',
            'ends_at' => '2026-10-20',
        ]);
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        Mail::fake();

        $this->actingAs($admin)
            ->get('/customers')
            ->assertRedirect(route('subscription.status'));
        $this->actingAs($admin)
            ->get(route('subscription.status'))
            ->assertOk()
            ->assertSee('Registrar solicitud de pago');

        $response = $this->from(route('subscription.status'))
            ->actingAs($admin)
            ->post(route('company-subscription-payments.store'), ['billing_cycle' => 'monthly']);

        $response->assertRedirect(route('subscription.status'))
            ->assertSessionHas('success');
        $paymentRequest = CompanyPaymentRequest::query()->sole();
        $this->assertSame($plan->monthly_price, $paymentRequest->amount);
        $this->assertSame('requested', $paymentRequest->status);
        Mail::assertSentTimes(CompanySubscriptionPaymentRequestedMail::class, 2);
        Mail::assertSent(CompanySubscriptionPaymentRequestedMail::class, function (CompanySubscriptionPaymentRequestedMail $mail) use ($admin): bool {
            return $mail->hasTo($admin->email) && ! $mail->isSuperAdmin;
        });
        Mail::assertSent(CompanySubscriptionPaymentRequestedMail::class, function (CompanySubscriptionPaymentRequestedMail $mail) use ($superAdmin): bool {
            return $mail->hasTo($superAdmin->email) && $mail->isSuperAdmin;
        });
    }

    public function test_new_billing_cycle_replaces_a_previous_pending_company_request(): void
    {
        [, $admin, $plan] = $this->createWorkshopSubscription([]);
        User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        Mail::fake();

        $this->actingAs($admin)
            ->post(route('company-subscription-payments.store'), ['billing_cycle' => 'monthly'])
            ->assertSessionHas('success');
        $firstRequest = CompanyPaymentRequest::query()->sole();

        $response = $this->actingAs($admin)
            ->post(route('company-subscription-payments.store'), ['billing_cycle' => 'yearly']);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('company_payment_requests', [
            'id' => $firstRequest->id,
            'status' => 'superseded',
        ]);
        $this->assertDatabaseHas('company_payment_requests', [
            'company_id' => $firstRequest->company_id,
            'billing_cycle' => 'yearly',
            'amount' => $plan->yearly_price,
            'status' => 'requested',
        ]);
    }

    public function test_super_admin_confirmation_activates_and_extends_a_workshop_subscription(): void
    {
        $this->travelTo('2026-10-24 12:00:00');
        [$company, $admin, $plan, $subscription] = $this->createWorkshopSubscription([
            'status' => 'expired',
            'ends_at' => '2026-10-20',
        ]);
        $paymentRequest = CompanyPaymentRequest::create([
            'company_id' => $company->id,
            'company_subscription_id' => $subscription->id,
            'subscription_plan_id' => $plan->id,
            'requested_by' => $admin->id,
            'billing_cycle' => 'yearly',
            'amount' => $plan->yearly_price,
            'currency' => 'USD',
            'status' => 'requested',
        ]);
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)
            ->patch(route('company-subscription-payments.confirm', $paymentRequest));

        $response->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseHas('company_payment_requests', [
            'id' => $paymentRequest->id,
            'status' => 'paid',
            'confirmed_by' => $superAdmin->id,
        ]);
        $this->assertDatabaseHas('company_subscriptions', [
            'id' => $subscription->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => '2026-10-24 00:00:00',
            'ends_at' => '2027-10-24 00:00:00',
        ]);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_reports_include_company_requests_and_active_fleet_customers(): void
    {
        $this->travelTo('2026-10-24 12:00:00');
        [$company, $admin, $plan, $subscription] = $this->createWorkshopSubscription([
            'status' => 'active',
            'ends_at' => '2026-11-20',
        ]);
        $customer = Customer::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente Flota Reporte',
            'email' => 'flota@example.test',
            'customer_type' => 'fleet_owner',
            'is_fleet_premium' => true,
            'vehicle_limit' => null,
            'fleet_expires_at' => now()->addMonth(),
        ]);
        CustomerPayment::create([
            'customer_id' => $customer->id,
            'customer_type' => 'fleet_owner',
            'vehicle_count' => 4,
            'billing_cycle' => 'monthly',
            'amount' => '10.00',
            'currency' => 'USD',
            'status' => 'requested',
            'pricing_breakdown' => ['vehicles' => []],
        ]);
        CompanyPaymentRequest::create([
            'company_id' => $company->id,
            'company_subscription_id' => $subscription->id,
            'subscription_plan_id' => $plan->id,
            'requested_by' => $admin->id,
            'billing_cycle' => 'monthly',
            'amount' => $plan->monthly_price,
            'currency' => 'USD',
            'status' => 'requested',
        ]);
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('client-subscriptions.index'))
            ->assertOk()
            ->assertSee('Cliente Flota Reporte')
            ->assertSee($company->name)
            ->assertSee('Taller/Concesionario');

        $this->actingAs($superAdmin)
            ->get(route('company-subscriptions.index'))
            ->assertOk()
            ->assertSee('Cliente Flota Reporte')
            ->assertSee($company->name)
            ->assertSee('Propietario de Flota')
            ->assertSee('Taller/Concesionario');
    }

    /**
     * @param  array<string, mixed>  $subscriptionAttributes
     * @return array{Company, User, SubscriptionPlan, CompanySubscription}
     */
    private function createWorkshopSubscription(array $subscriptionAttributes): array
    {
        $unique = uniqid();
        $company = Company::create([
            'name' => 'Taller de Prueba',
            'slug' => 'taller-prueba-'.$unique,
            'status' => $subscriptionAttributes['status'] ?? 'active',
        ]);
        $admin = User::create([
            'name' => 'Administrador de Prueba',
            'username' => 'admin-'.$unique,
            'email' => 'admin-'.$unique.'@example.test',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Plan de Taller de Prueba',
            'slug' => 'plan-taller-'.$unique,
            'monthly_price' => '49.99',
            'yearly_price' => '499.00',
            'is_active' => true,
        ]);
        $subscription = CompanySubscription::create(array_merge([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => '2026-09-20',
            'ends_at' => '2026-11-20',
        ], $subscriptionAttributes));

        return [$company, $admin, $plan, $subscription];
    }
}

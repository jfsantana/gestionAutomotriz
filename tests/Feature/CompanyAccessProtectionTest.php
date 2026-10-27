<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAccessProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_suspend_and_reactivate_company_access_without_deleting_data(): void
    {
        $context = $this->createCompanyContext();
        $customer = $this->createCustomer($context['company']);

        $this->actingAs($context['superAdmin'])
            ->get(route('companies.show', $context['company']))
            ->assertOk()
            ->assertSee('Suspender acceso');

        $this->actingAs($context['superAdmin'])
            ->from(route('companies.show', $context['company']))
            ->patch(route('companies.toggle-access', $context['company']))
            ->assertRedirect(route('companies.show', $context['company']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id' => $context['company']->id,
            'status' => 'suspended',
            'deleted_at' => null,
        ]);
        $this->assertModelExists($customer);
        $this->actingAs($context['superAdmin'])
            ->get(route('companies.show', $context['company']))
            ->assertOk()
            ->assertSee('Reactivar acceso');

        $this->actingAs($context['companyAdmin'])
            ->get(route('customers.index'))
            ->assertRedirect(route('subscription.status'));

        $this->actingAs($context['companyAdmin'])
            ->get(route('subscription.status'))
            ->assertOk()
            ->assertSee('Acceso del Taller Suspendido');

        $this->actingAs($context['superAdmin'])
            ->from(route('companies.show', $context['company']))
            ->patch(route('companies.toggle-access', $context['company']))
            ->assertRedirect(route('companies.show', $context['company']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id' => $context['company']->id,
            'status' => 'active',
            'deleted_at' => null,
        ]);
    }

    public function test_super_admin_cannot_suspend_company_with_active_fleet_customer(): void
    {
        $context = $this->createCompanyContext();
        $customer = $this->createCustomer($context['company'], [
            'is_fleet_premium' => true,
            'fleet_expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($context['superAdmin'])
            ->from(route('companies.show', $context['company']))
            ->patch(route('companies.toggle-access', $context['company']))
            ->assertRedirect(route('companies.show', $context['company']))
            ->assertSessionHas('error', 'No se puede suspender este taller porque tiene clientes con una suscripción de flota vigente.');

        $this->assertDatabaseHas('companies', [
            'id' => $context['company']->id,
            'status' => 'active',
            'deleted_at' => null,
        ]);
        $this->assertModelExists($customer);
    }

    public function test_editing_company_status_cannot_bypass_fleet_customer_protection(): void
    {
        $context = $this->createCompanyContext();
        $this->createCustomer($context['company'], [
            'is_fleet_premium' => true,
            'fleet_expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($context['superAdmin'])
            ->from(route('companies.edit', $context['company']))
            ->put(route('companies.update', $context['company']), [
                'name' => $context['company']->name,
                'country' => 'DO',
                'status' => 'suspended',
            ])
            ->assertRedirect(route('companies.edit', $context['company']))
            ->assertSessionHas('error', 'No se puede suspender este taller porque tiene clientes con una suscripción de flota vigente.');

        $this->assertDatabaseHas('companies', [
            'id' => $context['company']->id,
            'status' => 'active',
        ]);
    }

    public function test_subscription_suspension_cannot_bypass_fleet_customer_protection(): void
    {
        $context = $this->createCompanyContext();
        $this->createCustomer($context['company'], [
            'is_fleet_premium' => true,
            'fleet_expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($context['superAdmin'])
            ->from(route('company-subscriptions.edit', $context['subscription']))
            ->patch(route('company-subscriptions.toggle-suspension', $context['subscription']))
            ->assertInvalid(['status' => 'No se puede bloquear esta empresa porque tiene clientes con una suscripción de flota vigente.']);

        $this->assertDatabaseHas('company_subscriptions', [
            'id' => $context['subscription']->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('companies', [
            'id' => $context['company']->id,
            'status' => 'active',
        ]);
    }

    public function test_assigning_blocked_subscription_cannot_bypass_fleet_customer_protection(): void
    {
        $context = $this->createCompanyContext();
        $this->createCustomer($context['company'], [
            'is_fleet_premium' => true,
            'fleet_expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($context['superAdmin'])
            ->from(route('company-subscriptions.create'))
            ->post(route('company-subscriptions.store'), [
                'company_id' => $context['company']->id,
                'subscription_plan_id' => $context['subscription']->subscription_plan_id,
                'status' => 'suspended',
                'billing_cycle' => 'monthly',
                'starts_at' => today()->toDateString(),
                'ends_at' => today()->addMonth()->toDateString(),
            ])
            ->assertInvalid(['status' => 'No se puede bloquear esta empresa porque tiene clientes con una suscripción de flota vigente.']);

        $this->assertDatabaseCount('company_subscriptions', 1);
        $this->assertDatabaseHas('companies', [
            'id' => $context['company']->id,
            'status' => 'active',
        ]);
    }

    public function test_manually_expiring_subscription_cannot_bypass_fleet_customer_protection(): void
    {
        $context = $this->createCompanyContext();
        $this->createCustomer($context['company'], [
            'is_fleet_premium' => true,
            'fleet_expires_at' => now()->addMonth(),
        ]);

        $this->actingAs($context['superAdmin'])
            ->from(route('company-subscriptions.edit', $context['subscription']))
            ->put(route('company-subscriptions.update', $context['subscription']), [
                'subscription_plan_id' => $context['subscription']->subscription_plan_id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'starts_at' => today()->subMonths(2)->toDateString(),
                'ends_at' => today()->subMonth()->toDateString(),
            ])
            ->assertInvalid(['status' => 'No se puede bloquear esta empresa porque tiene clientes con una suscripción de flota vigente.']);

        $this->assertDatabaseHas('company_subscriptions', [
            'id' => $context['subscription']->id,
            'status' => 'active',
        ]);
        $this->assertSame(
            today()->addMonth()->toDateString(),
            $context['subscription']->fresh()->ends_at->toDateString(),
        );
    }

    /**
     * @return array{company: Company, companyAdmin: User, superAdmin: User, subscription: CompanySubscription}
     */
    private function createCompanyContext(): array
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);
        $company = Company::create([
            'name' => 'Taller Protegido',
            'slug' => 'taller-protegido',
            'status' => 'active',
        ]);
        $companyAdmin = User::factory()->create([
            'role' => 'company_admin',
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Plan Protección',
            'slug' => 'plan-proteccion',
            'monthly_price' => 50,
            'yearly_price' => 500,
            'is_active' => true,
        ]);
        $subscription = CompanySubscription::create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => today(),
            'ends_at' => today()->addMonth(),
        ]);

        return compact('company', 'companyAdmin', 'superAdmin', 'subscription');
    }

    private function createCustomer(Company $company, array $attributes = []): Customer
    {
        return Customer::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente Protegido',
            ...$attributes,
        ]);
    }
}

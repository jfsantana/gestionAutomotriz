<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Companies\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminCompany;
    protected User $mechanic;
    protected Company $company;
    protected SubscriptionPlan $planPro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'username' => 'super_admin_sub',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->company = Company::create([
            'name' => 'Taller Santo Domingo Pro',
            'slug' => 'taller-sd-pro',
            'status' => 'active',
        ]);

        $this->adminCompany = User::create([
            'name' => 'Admin Taller Pro',
            'username' => 'admin_taller_pro',
            'email' => 'admin@pro.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->mechanic = User::create([
            'name' => 'Mecánico Luis',
            'username' => 'mecanico_luis',
            'email' => 'luis@pro.do',
            'password' => 'secret123',
            'role' => 'mechanic',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->planPro = SubscriptionPlan::create([
            'name' => 'Plan Taller Completo',
            'slug' => 'plan-taller-completo',
            'monthly_price' => 49.99,
            'yearly_price' => 499.00,
            'max_mechanics' => 5,
            'is_active' => true,
        ]);
    }

    /**
     * Valida que el super_admin pueda crear planes de suscripción.
     */
    public function test_super_admin_can_create_subscription_plan(): void
    {
        $payload = [
            'name' => 'Plan Básico Taller',
            'monthly_price' => 25.00,
            'yearly_price' => 250.00,
            'max_mechanics' => 2,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->superAdmin)->post('/subscription-plans', $payload);

        $response->assertRedirect(route('subscription-plans.index'));
        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'Plan Básico Taller',
            'slug' => 'plan-basico-taller',
            'monthly_price' => 25.00,
        ]);
    }

    /**
     * Valida que el super_admin pueda asignar suscripción a una empresa.
     */
    public function test_super_admin_can_assign_subscription_to_company(): void
    {
        $payload = [
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->planPro->id,
            'status' => 'trial',
            'billing_cycle' => 'trial',
            'starts_at' => Carbon::today()->toDateString(),
            'ends_at' => Carbon::today()->addDays(15)->toDateString(),
            'notes' => 'Periodo de prueba otorgado',
        ];

        $response = $this->actingAs($this->superAdmin)->post('/company-subscriptions', $payload);

        $response->assertRedirect(route('company-subscriptions.index'));
        $this->assertDatabaseHas('company_subscriptions', [
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->planPro->id,
            'status' => 'trial',
        ]);
    }

    /**
     * Valida que una empresa activa pueda acceder a módulos operativos.
     */
    public function test_active_company_can_access_operational_modules(): void
    {
        CompanySubscription::create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->planPro->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addMonth(),
        ]);

        $response = $this->actingAs($this->adminCompany)->get('/customers');
        $response->assertStatus(200);

        $responseOrders = $this->actingAs($this->adminCompany)->get('/maintenance-orders');
        $responseOrders->assertStatus(200);
    }

    /**
     * Valida que una empresa suspendida o vencida sea bloqueada de los módulos operativos.
     */
    public function test_expired_or_suspended_company_is_blocked_from_operational_routes(): void
    {
        CompanySubscription::create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->planPro->id,
            'status' => 'suspended', // Bloqueo manual por superadmin
            'billing_cycle' => 'monthly',
            'starts_at' => Carbon::today()->subMonth(),
            'ends_at' => Carbon::today()->addMonth(),
            'suspended_at' => now(),
        ]);

        $response = $this->actingAs($this->adminCompany)->get('/customers');

        // Middleware redirige a la pantalla de estado de suscripción
        $response->assertRedirect(route('subscription.status'));
    }

    /**
     * Valida que la empresa pueda ver su pantalla de estado de suscripción.
     */
    public function test_company_admin_can_view_subscription_status_page(): void
    {
        $sub = CompanySubscription::create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->planPro->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => Carbon::today(),
            'ends_at' => Carbon::today()->addDays(5),
        ]);

        $response = $this->actingAs($this->adminCompany)->get('/subscription-status');

        $response->assertStatus(200);
        $response->assertSee('Plan Taller Completo');
        $response->assertSee('Aviso de Vencimiento Próximo');
    }

    /**
     * Valida que un company_admin no pueda entrar al módulo de administración global de suscripciones.
     */
    public function test_company_admin_cannot_manage_subscription_plans_or_all_subscriptions(): void
    {
        $responsePlans = $this->actingAs($this->adminCompany)->get('/subscription-plans');
        $responsePlans->assertStatus(403);

        $responseSubs = $this->actingAs($this->adminCompany)->get('/company-subscriptions');
        $responseSubs->assertStatus(403);
    }

    /**
     * Valida el comando Artisan que detecta y marca suscripciones vencidas.
     */
    public function test_artisan_command_marks_expired_subscriptions(): void
    {
        CompanySubscription::create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->planPro->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => Carbon::today()->subMonths(2),
            'ends_at' => Carbon::today()->subDays(2), // Fecha ya vencida
        ]);

        $this->artisan('subscriptions:process-expired')
            ->expectsOutputToContain('Se marcaron 1 suscripciones como vencidas (expired).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('company_subscriptions', [
            'company_id' => $this->company->id,
            'status' => 'expired',
        ]);
    }
}

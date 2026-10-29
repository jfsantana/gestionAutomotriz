<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Users\Models\ClientInvitation;
use App\Domain\Users\Models\PlatformPolicy;
use App\Domain\Users\Models\UserPolicyAcceptance;
use App\Domain\Vehicles\Models\Vehicle;
use App\Mail\ClientPortalInvitationMail;
use App\Models\User;
use Database\Seeders\PlatformPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClientPortalCompleteFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $adminCompany;

    protected Customer $customer;

    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlatformPolicySeeder::class);
        $activePolicy = PlatformPolicy::activePolicy();

        $this->company = Company::create([
            'name' => 'Taller AutoService Premium',
            'slug' => 'taller-autoservice-premium',
            'status' => 'active',
        ]);

        $this->adminCompany = User::create([
            'name' => 'Admin Taller',
            'username' => 'admin_service',
            'email' => 'admin@service.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        if ($activePolicy) {
            UserPolicyAcceptance::create([
                'user_id' => $this->adminCompany->id,
                'platform_policy_id' => $activePolicy->id,
                'policy_version' => $activePolicy->version,
                'accepted_at' => now(),
            ]);
        }

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'full_name' => 'Juan Propietario',
            'email' => 'juan.propietario@gmail.com',
            'phone' => '809-555-7788',
            'fleet_trial_started_at' => now(),
        ]);

        $this->vehicle = Vehicle::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'plate' => 'A123999',
            'make' => 'Mazda',
            'model' => 'CX-5',
            'year' => 2022,
            'current_mileage' => 25000,
        ]);
    }

    /**
     * Valida que el taller pueda enviar la invitación por correo al cliente.
     */
    public function test_workshop_admin_can_send_portal_invitation_to_customer(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->adminCompany)->post("/customers/{$this->customer->id}/invite");

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('client_invitations', [
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'juan.propietario@gmail.com',
            'role' => 'customer',
            'account_status' => 'INVITED',
            'is_active' => 0,
        ]);

        Mail::assertSent(ClientPortalInvitationMail::class, function ($mail) {
            return $mail->hasTo('juan.propietario@gmail.com');
        });
    }

    /**
     * Valida que el cliente cree su contraseña, active su cuenta y entre al portal.
     */
    public function test_customer_can_activate_account_via_token(): void
    {
        $this->customer->update(['fleet_trial_started_at' => null]);

        $user = User::create([
            'name' => 'Juan Propietario',
            'username' => 'juan_prop',
            'email' => 'juan.propietario@gmail.com',
            'password' => 'temp-pass',
            'role' => 'customer',
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'is_active' => false,
            'account_status' => 'INVITED',
        ]);

        $plainToken = 'token_secreto_valido_123456';
        $hashedToken = hash('sha256', $plainToken);

        ClientInvitation::create([
            'user_id' => $user->id,
            'customer_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'token' => $hashedToken,
            'expires_at' => now()->addHours(48),
        ]);

        // Ver pantalla de activación
        $responseShow = $this->get("/portal/activate/{$plainToken}");
        $responseShow->assertStatus(200);
        $responseShow->assertSee('Activar Cuenta de Cliente');

        // Enviar nueva contraseña
        $responseActivate = $this->post("/portal/activate/{$plainToken}", [
            'password' => 'MiClaveSegura2026',
            'password_confirmation' => 'MiClaveSegura2026',
        ]);

        $responseActivate->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->assertEquals('ACTIVE', $user->fresh()->account_status);
        $this->assertTrue($user->fresh()->is_active);
        $this->assertNotNull($user->fresh()->activated_at);
        $this->assertNotNull($this->customer->fresh()->fleet_trial_started_at);
    }

    /**
     * Valida que el cliente pueda registrar un mantenimiento personal diferenciado.
     */
    public function test_customer_can_register_personal_maintenance_order(): void
    {
        $user = User::create([
            'name' => 'Juan Propietario',
            'username' => 'juan_prop',
            'email' => 'juan.propietario@gmail.com',
            'password' => 'secret',
            'role' => 'customer',
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);

        $activePolicy = PlatformPolicy::activePolicy();
        if ($activePolicy) {
            UserPolicyAcceptance::create([
                'user_id' => $user->id,
                'platform_policy_id' => $activePolicy->id,
                'policy_version' => $activePolicy->version,
                'accepted_at' => now(),
            ]);
        }

        $payload = [
            'vehicle_id' => $this->vehicle->id,
            'service_name_snapshot' => 'Cambio de Aceite sintético propio',
            'maintenance_type' => 'preventive',
            'service_date' => now()->toDateString(),
            'current_mileage' => 26000,
            'cost' => 3200.00,
            'customer_notes' => 'Compré los repuestos y los instalé yo mismo.',
        ];

        $response = $this->actingAs($user)->post('/portal/maintenance/create-personal', $payload);

        $response->assertRedirect(route('portal.maintenance.index'));

        $this->assertDatabaseHas('maintenance_orders', [
            'vehicle_id' => $this->vehicle->id,
            'customer_id' => $this->customer->id,
            'origin' => 'customer',
            'is_verified' => 0,
            'service_name_snapshot' => 'Cambio de Aceite sintético propio',
        ]);

        $this->assertEquals(26000, $this->vehicle->fresh()->current_mileage);
    }

    /**
     * Valida que el cliente NO pueda acceder al panel ni módulos administrativos del taller.
     */
    public function test_customer_cannot_access_workshop_administrative_modules(): void
    {
        $user = User::create([
            'name' => 'Juan Propietario',
            'username' => 'juan_prop',
            'email' => 'juan.propietario@gmail.com',
            'password' => 'secret',
            'role' => 'customer',
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);

        $activePolicy = PlatformPolicy::activePolicy();
        if ($activePolicy) {
            UserPolicyAcceptance::create([
                'user_id' => $user->id,
                'platform_policy_id' => $activePolicy->id,
                'policy_version' => $activePolicy->version,
                'accepted_at' => now(),
            ]);
        }

        $responseDashboard = $this->actingAs($user)->get('/dashboard');
        $responseDashboard->assertStatus(403);

        $responseStaff = $this->actingAs($user)->get('/staff');
        $responseStaff->assertStatus(403);

        $responseCompanies = $this->actingAs($user)->get('/companies');
        $responseCompanies->assertStatus(403);
    }

    public function test_non_premium_customer_sees_only_three_vehicles_and_hidden_count(): void
    {
        $user = $this->createPortalCustomerUser();
        $this->createAdditionalVehicles(4);

        $response = $this->actingAs($user)->get(route('portal.vehicles.index'));

        $response->assertOk()
            ->assertViewHas('vehicles', fn ($vehicles): bool => $vehicles->count() === 3)
            ->assertViewHas('hidden_vehicles_count', 2)
            ->assertSee('PORTAL4')
            ->assertDontSee('A123999')
            ->assertSee('Tienes 2 vehículos adicionales registrados por el taller.')
            ->assertSee('Actualiza al plan Propietario de Flota para gestionar toda tu plantilla.')
            ->assertSee('Desbloquear Todos los vehículos')
            ->assertSee('href="https://nfcvzla.venecode.com/jsantana/"', false);
        $this->assertSame(5, $this->customer->vehicles()->count());
    }

    public function test_customer_cannot_open_a_vehicle_outside_the_three_visible_during_trial(): void
    {
        $user = $this->createPortalCustomerUser();
        $this->createAdditionalVehicles(3);

        $this->actingAs($user)
            ->get(route('portal.vehicles.show', $this->vehicle))
            ->assertNotFound();
    }

    public function test_premium_customer_sees_all_vehicles_without_hidden_vehicles(): void
    {
        $this->customer->update(['is_fleet_premium' => true]);
        $user = $this->createPortalCustomerUser();
        $this->createAdditionalVehicles(4);

        $response = $this->actingAs($user)->get(route('portal.vehicles.index'));

        $response->assertOk()
            ->assertViewHas('vehicles', fn ($vehicles): bool => $vehicles->count() === 5)
            ->assertViewHas('hidden_vehicles_count', 0)
            ->assertDontSee('Desbloquear Todos los vehículos')
            ->assertDontSee('Vehículos adicionales bloqueados');
    }

    public function test_expired_premium_customer_sees_only_three_vehicles_and_the_upgrade_card(): void
    {
        $this->customer->update([
            'is_fleet_premium' => true,
            'vehicle_limit' => null,
            'fleet_expires_at' => now()->subMinute(),
        ]);
        $user = $this->createPortalCustomerUser();
        $this->createAdditionalVehicles(4);

        $response = $this->actingAs($user)->get(route('portal.vehicles.index'));

        $response->assertOk()
            ->assertViewHas('vehicles', fn ($vehicles): bool => $vehicles->count() === 3)
            ->assertViewHas('hidden_vehicles_count', 2)
            ->assertSee('Tienes 2 vehículos adicionales registrados por el taller.')
            ->assertSee('Desbloquear Todos los vehículos');
    }

    public function test_customer_with_expired_trial_sees_no_vehicles_but_can_still_request_access(): void
    {
        $user = $this->createPortalCustomerUser();
        $this->customer->update(['fleet_trial_started_at' => now()->subMonths(2)->subMinute()]);

        $response = $this->actingAs($user)->get(route('portal.vehicles.index'));

        $response->assertOk()
            ->assertViewHas('vehicles', fn ($vehicles): bool => $vehicles->isEmpty())
            ->assertViewHas('hidden_vehicles_count', 1)
            ->assertSee('Tu período gratuito terminó.')
            ->assertSee('Registrar solicitud de pago')
            ->assertSee('habilitar al suscribirte');
    }

    public function test_customer_with_expired_trial_cannot_open_vehicle_or_maintenance_pages(): void
    {
        $user = $this->createPortalCustomerUser();
        $this->customer->update(['fleet_trial_started_at' => now()->subMonths(2)->subMinute()]);

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertRedirect(route('portal.vehicles.index'));
        $this->get(route('portal.vehicles.show', $this->vehicle))
            ->assertRedirect(route('portal.vehicles.index'));
        $this->get(route('portal.maintenance.index'))
            ->assertRedirect(route('portal.vehicles.index'));
        $this->get(route('portal.maintenance.create'))
            ->assertRedirect(route('portal.vehicles.index'));
        $this->post(route('portal.maintenance.store'), [
            'vehicle_id' => $this->vehicle->id,
            'service_name_snapshot' => 'Cambio de aceite',
            'maintenance_type' => 'preventive',
            'service_date' => now()->toDateString(),
            'current_mileage' => 100,
        ])->assertRedirect(route('portal.vehicles.index'));
        $this->assertDatabaseCount('maintenance_orders', 0);
    }

    /**
     * Valida que el cliente pueda dar de baja un vehículo de su cuenta.
     */
    public function test_customer_can_decommission_their_vehicle(): void
    {
        $user = User::create([
            'name' => 'Juan Propietario',
            'username' => 'juan_prop',
            'email' => 'juan.propietario@gmail.com',
            'password' => 'secret',
            'role' => 'customer',
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);

        $activePolicy = PlatformPolicy::activePolicy();
        if ($activePolicy) {
            UserPolicyAcceptance::create([
                'user_id' => $user->id,
                'platform_policy_id' => $activePolicy->id,
                'policy_version' => $activePolicy->version,
                'accepted_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->post("/portal/vehicles/{$this->vehicle->id}/decommission");

        $response->assertRedirect(route('portal.vehicles.index'));
        // La baja elimina definitivamente el vehículo para liberar la placa para un nuevo registro
        $this->assertDatabaseMissing('vehicles', ['id' => $this->vehicle->id]);
    }

    private function createPortalCustomerUser(): User
    {
        $this->customer->update(['fleet_trial_started_at' => now()]);

        $user = User::create([
            'name' => 'Juan Propietario',
            'username' => 'juan_prop',
            'email' => 'juan.propietario@gmail.com',
            'password' => 'secret',
            'role' => 'customer',
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);

        $activePolicy = PlatformPolicy::activePolicy();
        if ($activePolicy) {
            UserPolicyAcceptance::create([
                'user_id' => $user->id,
                'platform_policy_id' => $activePolicy->id,
                'policy_version' => $activePolicy->version,
                'accepted_at' => now(),
            ]);
        }

        return $user;
    }

    private function createAdditionalVehicles(int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            Vehicle::create([
                'customer_id' => $this->customer->id,
                'plate' => "PORTAL{$index}",
                'make' => 'Toyota',
                'model' => 'Corolla',
            ]);
        }
    }
}

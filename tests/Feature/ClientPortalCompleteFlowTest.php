<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Users\Models\ClientInvitation;
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
        $activePolicy = \App\Domain\Users\Models\PlatformPolicy::activePolicy();

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
            \App\Domain\Users\Models\UserPolicyAcceptance::create([
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

        $activePolicy = \App\Domain\Users\Models\PlatformPolicy::activePolicy();
        if ($activePolicy) {
            \App\Domain\Users\Models\UserPolicyAcceptance::create([
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

        $activePolicy = \App\Domain\Users\Models\PlatformPolicy::activePolicy();
        if ($activePolicy) {
            \App\Domain\Users\Models\UserPolicyAcceptance::create([
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

        $activePolicy = \App\Domain\Users\Models\PlatformPolicy::activePolicy();
        if ($activePolicy) {
            \App\Domain\Users\Models\UserPolicyAcceptance::create([
                'user_id' => $user->id,
                'platform_policy_id' => $activePolicy->id,
                'policy_version' => $activePolicy->version,
                'accepted_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->post("/portal/vehicles/{$this->vehicle->id}/decommission");

        $response->assertRedirect(route('portal.vehicles.index'));
        $this->assertEquals('DECOMMISSIONED', $this->vehicle->fresh()->customer_status);
        $this->assertSoftDeleted('vehicles', ['id' => $this->vehicle->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $superAdmin;
    protected User $adminCompanyA;
    protected User $adminCompanyB;
    protected User $mechanicCompanyA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::create([
            'name' => 'Taller Alfa',
            'slug' => 'taller-alfa',
            'status' => 'active',
        ]);

        $this->companyB = Company::create([
            'name' => 'Taller Beta',
            'slug' => 'taller-beta',
            'status' => 'active',
        ]);

        $this->superAdmin = User::factory()->create([
            'username' => 'super_admin_staff',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->adminCompanyA = User::create([
            'name' => 'Admin Alfa',
            'username' => 'admin_alfa',
            'email' => 'admin@alfa.com',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);

        $this->adminCompanyB = User::create([
            'name' => 'Admin Beta',
            'username' => 'admin_beta',
            'email' => 'admin@beta.com',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyB->id,
            'is_active' => true,
        ]);

        $this->mechanicCompanyA = User::create([
            'name' => 'Mecánico Alfa',
            'username' => 'mecanico_alfa',
            'email' => 'mecanico@alfa.com',
            'password' => 'secret123',
            'role' => 'mechanic',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);
    }

    /**
     * Valida que un company_admin solo vea los mecánicos de su propia empresa.
     */
    public function test_company_admin_only_sees_staff_from_their_own_company(): void
    {
        $mechanicB = User::create([
            'name' => 'Mecánico Beta',
            'username' => 'mecanico_beta',
            'email' => 'mecanico@beta.com',
            'password' => 'secret123',
            'role' => 'mechanic',
            'company_id' => $this->companyB->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminCompanyA)->get('/staff');

        $response->assertStatus(200);
        $response->assertSee('mecanico_alfa');
        $response->assertDontSee('mecanico_beta');
    }

    /**
     * Valida que un company_admin pueda registrar un mecánico con su company_id forzado.
     */
    public function test_company_admin_can_create_mechanic_assigned_to_their_company(): void
    {
        $payload = [
            'name' => 'Carlos Mecánico',
            'username' => 'cmecanico',
            'email' => 'cmecanico@alfa.com',
            'password' => 'clave1234',
            'role' => 'mechanic',
            'company_id' => $this->companyB->id, // Intento malicioso de asignar otra empresa
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminCompanyA)->post('/staff', $payload);

        $response->assertRedirect(route('staff.index'));

        // El controlador debe ignorar companyB y forzar companyA
        $this->assertDatabaseHas('users', [
            'username' => 'cmecanico',
            'company_id' => $this->companyA->id,
            'role' => 'mechanic',
        ]);
    }

    /**
     * Valida que un company_admin no pueda editar un mecánico de otra empresa (Aislamiento 403).
     */
    public function test_company_admin_cannot_edit_staff_from_another_company(): void
    {
        $response = $this->actingAs($this->adminCompanyB)->get("/staff/{$this->mechanicCompanyA->id}/edit");

        $response->assertStatus(403);
    }

    /**
     * Valida que un company_admin no pueda actualizar un mecánico de otra empresa.
     */
    public function test_company_admin_cannot_update_staff_from_another_company(): void
    {
        $response = $this->actingAs($this->adminCompanyB)->put("/staff/{$this->mechanicCompanyA->id}", [
            'name' => 'Nombre Hackeado',
            'username' => 'mecanico_alfa',
            'email' => 'mecanico@alfa.com',
            'role' => 'mechanic',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Valida que un mecánico no pueda acceder al módulo de gestión de personal.
     */
    public function test_mechanic_cannot_access_staff_management(): void
    {
        $response = $this->actingAs($this->mechanicCompanyA)->get('/staff');

        $response->assertStatus(403);
    }

    /**
     * Valida que se pueda alternar el estado activo/inactivo del personal.
     */
    public function test_company_admin_can_toggle_staff_status(): void
    {
        $response = $this->actingAs($this->adminCompanyA)->patch("/staff/{$this->mechanicCompanyA->id}/toggle-status");

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseHas('users', [
            'id' => $this->mechanicCompanyA->id,
            'is_active' => 0,
        ]);
    }
}

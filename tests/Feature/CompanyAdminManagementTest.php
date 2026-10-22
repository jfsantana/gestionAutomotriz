<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $mechanic;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Taller Santo Domingo',
            'slug' => 'taller-santo-domingo',
            'status' => 'active',
        ]);

        $this->superAdmin = User::factory()->create([
            'username' => 'super_admin_tester',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->mechanic = User::factory()->create([
            'username' => 'mechanic_tester',
            'role' => 'mechanic',
            'is_active' => true,
        ]);
    }

    /**
     * Valida que un no-super_admin no tenga acceso a company-admins.
     */
    public function test_non_super_admin_cannot_access_company_admins(): void
    {
        $response = $this->actingAs($this->mechanic)->get('/company-admins');

        $response->assertStatus(403);
    }

    /**
     * Valida que el super_admin pueda listar administradores de empresa.
     */
    public function test_super_admin_can_view_company_admins_list(): void
    {
        $admin = User::create([
            'name' => 'Carlos Administrador',
            'username' => 'carlos_admin',
            'email' => 'carlos@taller.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->get('/company-admins');

        $response->assertStatus(200);
        $response->assertSee('carlos_admin');
        $response->assertSee('Taller Santo Domingo');
    }

    /**
     * Valida la creación de un company_admin asociado a una empresa.
     */
    public function test_super_admin_can_create_a_company_admin(): void
    {
        $payload = [
            'name' => 'Manuel Gómez',
            'username' => 'mgomez',
            'email' => 'mgomez@taller.do',
            'password' => 'password123',
            'company_id' => $this->company->id,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->superAdmin)->post('/company-admins', $payload);

        $this->assertDatabaseHas('users', [
            'username' => 'mgomez',
            'email' => 'mgomez@taller.do',
            'role' => 'company_admin',
            'company_id' => $this->company->id,
            'is_active' => 1,
        ]);

        $created = User::where('username', 'mgomez')->first();
        $response->assertRedirect(route('company-admins.show', $created));
    }

    /**
     * Valida que company_id sea obligatorio para un company_admin.
     */
    public function test_company_id_is_required_for_company_admin(): void
    {
        $payload = [
            'name' => 'Sin Empresa',
            'username' => 'sinempresa',
            'email' => 'sinempresa@taller.do',
            'password' => 'password123',
            'company_id' => '',
        ];

        $response = $this->actingAs($this->superAdmin)->post('/company-admins', $payload);

        $response->assertSessionHasErrors('company_id');
    }

    /**
     * Valida que se pueda actualizar el estado activo/inactivo y datos del administrador.
     */
    public function test_super_admin_can_update_and_deactivate_company_admin(): void
    {
        $admin = User::create([
            'name' => 'Admin Inicial',
            'username' => 'admin_inicial',
            'email' => 'inicial@taller.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->put("/company-admins/{$admin->id}", [
            'name' => 'Admin Actualizado',
            'username' => 'admin_actualizado',
            'email' => 'inicial@taller.do',
            'company_id' => $this->company->id,
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('company-admins.show', $admin));
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin Actualizado',
            'username' => 'admin_actualizado',
            'is_active' => 0,
        ]);
    }

    /**
     * Valida que un usuario desactivado no pueda autenticarse.
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'name' => 'Inactivo Test',
            'username' => 'inactivo_user',
            'email' => 'inactivo@taller.do',
            'password' => 'clave1234',
            'role' => 'company_admin',
            'company_id' => $this->company->id,
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'inactivo_user',
            'password' => 'clave1234',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Valida que la pantalla de login sea accesible.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Iniciar Sesión');
        $response->assertSee('AutoBitacora');
    }

    /**
     * Valida que el superadmin pueda autenticarse con username y password.
     */
    public function test_super_admin_can_authenticate_using_username(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $response = $this->post('/login', [
            'username' => 'jsantana',
            'password' => '*S4nt4n4s',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));
    }

    #[TestWith([null])]
    #[TestWith(['/dashboard'])]
    #[TestWith(['/'])]
    #[TestWith(['/maintenance-orders'])]
    public function test_company_admin_login_always_opens_company_dashboard(?string $intendedPath): void
    {
        $company = Company::create(['name' => 'Taller autenticación', 'slug' => 'auth-workshop', 'status' => 'active']);
        $user = User::factory()->create([
            'role' => 'company_admin',
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        if ($intendedPath !== null) {
            $this->withSession(['url.intended' => url($intendedPath)]);
        }

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('company.dashboard'));
        $response->assertSessionMissing('url.intended');
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->get(route('company.dashboard'))->assertOk()->assertViewIs('dashboard.company');
    }

    public function test_customer_first_successful_login_starts_the_two_month_trial(): void
    {
        $company = Company::create([
            'name' => 'Taller portal cliente',
            'slug' => 'taller-portal-cliente',
            'status' => 'active',
        ]);
        $customer = Customer::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente de prueba',
        ]);
        $user = User::factory()->create([
            'role' => 'customer',
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('portal.dashboard'));
        $this->assertNotNull($customer->fresh()->fleet_trial_started_at);
    }

    public function test_failed_customer_login_does_not_start_the_trial(): void
    {
        $company = Company::create([
            'name' => 'Taller portal sin acceso',
            'slug' => 'taller-portal-sin-acceso',
            'status' => 'active',
        ]);
        $customer = Customer::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente sin acceso',
        ]);
        $user = User::factory()->create([
            'role' => 'customer',
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);

        $this->post('/login', [
            'username' => $user->username,
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('username');

        $this->assertNull($customer->fresh()->fleet_trial_started_at);
    }

    /**
     * Valida que credenciales inválidas no autentiquen.
     */
    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $response = $this->post('/login', [
            'username' => 'jsantana',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('username');
    }

    /**
     * Valida que un usuario no autenticado sea redirigido a login al intentar entrar al dashboard.
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Valida que un usuario autenticado pueda cerrar sesión.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'role' => 'mechanic',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }
}

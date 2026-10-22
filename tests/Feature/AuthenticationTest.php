<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertSee('gestionAutomotriz');
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

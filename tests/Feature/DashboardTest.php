<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['/'])]
    #[TestWith(['/dashboard'])]
    public function test_company_admin_is_redirected_from_general_dashboard_to_company_dashboard(string $path): void
    {
        $user = User::factory()->create(['role' => 'company_admin', 'is_active' => true]);

        $this->actingAs($user)->get($path)->assertRedirect(route('company.dashboard'));
        $this->get(route('company.dashboard'))
            ->assertOk()
            ->assertViewIs('dashboard.company')
            ->assertSee('class="sidebar sidebar-company ', false)
            ->assertSee('Panel de control operativo y trazabilidad vehicular del taller')
            ->assertSee('href="'.route('company.dashboard').'"', false)
            ->assertDontSee('Estado de Salud del Sistema');
    }

    /**
     * Prueba que la ruta principal cargue correctamente la vista dashboard para un usuario autenticado.
     */
    public function test_dashboard_page_loads_successfully_on_home_route_when_authenticated(): void
    {
        $user = User::factory()->create([
            'username' => 'admin_test',
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $response->assertSee('AutoBitacora');
        $response->assertSee('Sistema Multiempresa de Gestión y Trazabilidad Automotriz');
        $response->assertSee('Estado de Salud del Sistema');
        $response->assertSee('admin_test');
        $response->assertSee('class="sidebar bg-white ', false);
        $response->assertDontSee('class="sidebar sidebar-company ', false);
    }

    /**
     * Prueba que la ruta explícita /dashboard cargue el dashboard para un usuario autenticado.
     */
    public function test_dashboard_page_loads_successfully_on_dashboard_route_when_authenticated(): void
    {
        $user = User::factory()->create([
            'username' => 'mechanic_test',
            'role' => 'mechanic',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Bienvenido, '.$user->name);
        $response->assertSee('mechanic');
        $response->assertSee('class="sidebar bg-white ', false);
    }

    public function test_super_admin_does_not_receive_company_sidebar_palette_on_company_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($user)->get(route('company.dashboard'))
            ->assertOk()
            ->assertSee('class="sidebar bg-white ', false)
            ->assertDontSee('class="sidebar sidebar-company ', false);
    }

    public function test_company_admin_sidebar_keeps_existing_style_in_other_modules(): void
    {
        $user = User::factory()->create(['role' => 'company_admin', 'is_active' => true]);

        $this->actingAs($user)->get(route('maintenance-orders.index'))
            ->assertOk()
            ->assertSee('class="sidebar bg-white ', false)
            ->assertDontSee('class="sidebar sidebar-company ', false);
    }
}

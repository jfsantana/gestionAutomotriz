<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
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
        $response->assertSee('Bienvenido, ' . $user->name);
        $response->assertSee('mechanic');
    }
}


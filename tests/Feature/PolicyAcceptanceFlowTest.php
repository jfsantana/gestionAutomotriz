<?php

namespace Tests\Feature;

use App\Domain\Users\Models\PlatformPolicy;
use App\Domain\Users\Models\UserPolicyAcceptance;
use App\Models\User;
use Database\Seeders\PlatformPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyAcceptanceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected PlatformPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlatformPolicySeeder::class);
        $this->policy = PlatformPolicy::activePolicy();

        $this->user = User::factory()->create([
            'username' => 'usuario_prueba',
            'role' => 'company_admin',
            'is_active' => true,
        ]);
    }

    /**
     * Valida que un usuario que no ha aceptado la política sea redirigido a la vista de políticas al entrar al dashboard.
     */
    public function test_user_without_accepted_policy_is_redirected_to_policy_modal(): void
    {
        $response = $this->actingAs($this->user)->get('/dashboard');

        $response->assertRedirect(route('policy.show'));
    }

    /**
     * Valida que se renderice la pantalla de políticas con el contenido y versión 1.0.
     */
    public function test_policy_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)->get(route('policy.show'));

        $response->assertStatus(200);
        $response->assertSee('Políticas de Uso y Condiciones de la Plataforma');
        $response->assertSee('Versión 1.0');
        $response->assertSee('Objeto y alcance de la plataforma');
        $response->assertSee('Entorno cerrado por empresa');
    }

    /**
     * Valida que al aceptar se registre la evidencia con IP y versión y se permita navegar.
     */
    public function test_user_can_accept_policy_and_gains_access_to_application(): void
    {
        $response = $this->actingAs($this->user)->post(route('policy.accept'), [
            'policy_id' => $this->policy->id,
            'accepted' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('user_policy_acceptances', [
            'user_id' => $this->user->id,
            'platform_policy_id' => $this->policy->id,
            'policy_version' => '1.0',
        ]);

        $this->assertTrue($this->user->fresh()->hasAcceptedCurrentPolicy());

        // Ahora ya puede entrar al dashboard sin redirección
        $responseDashboard = $this->actingAs($this->user)->get('/dashboard');
        $responseDashboard->assertStatus(200);
    }

    /**
     * Valida que si se publica una nueva versión de políticas (ej: 2.0), el usuario vuelva a ser bloqueado hasta reaceptar.
     */
    public function test_new_policy_version_forces_reacceptance(): void
    {
        // 1. Aceptar versión 1.0
        UserPolicyAcceptance::create([
            'user_id' => $this->user->id,
            'platform_policy_id' => $this->policy->id,
            'policy_version' => '1.0',
            'accepted_at' => now(),
        ]);

        $this->assertTrue($this->user->hasAcceptedCurrentPolicy());

        // 2. Publicar nueva versión 2.0 activa
        $this->policy->update(['is_active' => false]);
        $newPolicy = PlatformPolicy::create([
            'version' => '2.0',
            'title' => 'Políticas de Uso Actualizadas v2.0',
            'content' => 'Contenido renovado con nuevos términos...',
            'effective_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        // 3. El usuario ya no cuenta como aceptado para la versión vigente
        $this->assertFalse($this->user->fresh()->hasAcceptedCurrentPolicy());

        $response = $this->actingAs($this->user)->get('/dashboard');
        $response->assertRedirect(route('policy.show'));
    }
}

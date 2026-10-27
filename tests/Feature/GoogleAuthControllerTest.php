<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class GoogleAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_offers_google_authentication(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Continuar con Google')
            ->assertSee(route('login.google.redirect'));
    }

    public function test_google_redirect_reports_missing_credentials(): void
    {
        config(['services.google.client_id' => null]);

        $this->get(route('login.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_google_redirect_starts_oauth_with_account_selection(): void
    {
        $this->configureGoogle();
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('with')
            ->once()
            ->with(['prompt' => 'select_account'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('login.google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_verified_google_customer_email_authenticates_existing_customer(): void
    {
        $this->configureGoogle();
        $customer = Customer::create([
            'full_name' => 'Cliente Google',
            'email' => 'cliente.google@example.test',
        ]);
        $user = User::factory()->create([
            'name' => 'Cliente Google',
            'email' => 'cliente.google@example.test',
            'role' => 'customer',
            'customer_id' => $customer->id,
            'email_verification_required' => true,
            'email_verified_at' => null,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $this->mockGoogleUser('CLIENTE.GOOGLE@example.test', true);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('portal.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertFalse($user->fresh()->email_verification_required);
        $this->assertNotNull($customer->fresh()->fleet_trial_started_at);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_verified_google_company_admin_email_authenticates_to_company_dashboard(): void
    {
        $this->configureGoogle();
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'role' => 'company_admin',
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $this->mockGoogleUser('admin@example.test', true);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('company.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    #[TestWith(['super_admin'])]
    #[TestWith(['mechanic'])]
    public function test_verified_google_email_authenticates_other_active_roles_to_dashboard(string $role): void
    {
        $this->configureGoogle();
        $user = User::factory()->create([
            'email' => "{$role}@example.test",
            'role' => $role,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $this->mockGoogleUser($user->email, true);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_verified_google_email_creates_and_authenticates_an_orphan_fleet_customer(): void
    {
        $this->configureGoogle();
        $this->mockGoogleUser('new.fleet@example.test', true, 'Nuevo Propietario');

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('portal.dashboard'));

        $user = User::query()->where('email', 'new.fleet@example.test')->firstOrFail();
        $customer = Customer::query()->where('email', 'new.fleet@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertModelExists($user);
        $this->assertModelExists($customer);
        $this->assertSame('customer', $user->role);
        $this->assertSame($customer->id, $user->customer_id);
        $this->assertSame('ACTIVE', $user->account_status);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->email_verification_required);
        $this->assertSame('Nuevo Propietario', $customer->full_name);
        $this->assertSame('fleet_owner', $customer->customer_type);
        $this->assertNull($customer->company_id);
        $this->assertNull($user->company_id);
        $this->assertFalse($customer->is_fleet_premium);
        $this->assertSame(3, $customer->vehicle_limit);
        $this->assertNotNull($customer->fleet_trial_started_at);
    }

    public function test_google_login_does_not_duplicate_an_existing_workshop_customer_without_a_user(): void
    {
        $this->configureGoogle();
        $company = Company::create([
            'name' => 'Taller Asociado',
            'slug' => 'taller-asociado-google',
            'status' => 'active',
        ]);
        $customer = Customer::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente del taller',
            'email' => 'contacto.taller@example.test',
        ]);
        $this->mockGoogleUser('contacto.taller@example.test', true);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
        $this->assertModelExists($customer);
        $this->assertDatabaseMissing('users', ['email' => 'contacto.taller@example.test']);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_google_login_rejects_an_unverified_email(): void
    {
        $this->configureGoogle();
        User::factory()->create([
            'email' => 'unverified@example.test',
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $this->mockGoogleUser('unverified@example.test', false);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_google_login_rejects_inactive_accounts(): void
    {
        $this->configureGoogle();
        User::factory()->create([
            'email' => 'inactive@example.test',
            'is_active' => false,
            'account_status' => 'DEACTIVATED',
        ]);
        $this->mockGoogleUser('inactive@example.test', true);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_google_login_does_not_activate_a_customer_invitation(): void
    {
        $this->configureGoogle();
        User::factory()->create([
            'email' => 'invited@example.test',
            'role' => 'customer',
            'is_active' => false,
            'account_status' => 'INVITED',
        ]);
        $this->mockGoogleUser('invited@example.test', true);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    public function test_google_login_reports_invalid_oauth_state(): void
    {
        $this->configureGoogle();
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('login.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }

    private function configureGoogle(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    private function mockGoogleUser(string $email, bool $emailVerified, ?string $name = 'Google Test User'): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn(GoogleUser::fake([
            'email' => $email,
            'email_verified' => $emailVerified,
            'name' => $name,
        ]));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Customer;
use App\Domain\Users\Models\PlatformPolicy;
use App\Domain\Users\Models\UserPolicyAcceptance;
use App\Models\User;
use Database\Seeders\PlatformPolicySeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class CustomerSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_self_register_without_a_workshop_and_must_verify_email(): void
    {
        Notification::fake();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        ]);

        $response = $this->post(route('customer.register.store'), [
            'full_name' => 'Cliente Independiente',
            'email' => 'independiente@example.test',
            'customer_type' => 'individual',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $customer = Customer::query()->where('email', 'independiente@example.test')->firstOrFail();
        $user = User::query()->where('email', 'independiente@example.test')->firstOrFail();

        $this->assertNull($customer->company_id);
        $this->assertNull($user->company_id);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->email_verification_required);
        $this->assertTrue($customer->hasFleetTrialActive());
        $this->assertSame($customer->id, $user->customer_id);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertSame('es', $user->preferredLocale());

        $originalLocale = app()->getLocale();
        app()->setLocale($user->preferredLocale());
        $verificationMessage = (new VerifyEmail)->toMail($user);
        app()->setLocale($originalLocale);

        $this->assertSame('Verifica tu correo electrónico', $verificationMessage->subject);
        $this->assertSame('Haz clic en el botón de abajo para verificar tu dirección de correo electrónico.', $verificationMessage->introLines[0]);
        $this->assertSame('Verificar correo electrónico', $verificationMessage->actionText);
        $this->assertSame('Si no creaste una cuenta, no es necesario realizar ninguna otra acción.', $verificationMessage->outroLines[0]);

        $this->get(route('portal.vehicles.index'))
            ->assertRedirect(route('verification.notice'));

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('portal.dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertFalse($user->fresh()->email_verification_required);
    }

    public function test_registration_shows_a_spanish_message_when_email_is_already_registered(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        ]);
        User::factory()->create(['email' => 'existente@example.test']);

        $this->post(route('customer.register.store'), [
            'full_name' => 'Cliente Independiente',
            'email' => 'existente@example.test',
            'customer_type' => 'individual',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors([
            'email' => 'El campo correo electrónico ya está en uso.',
        ]);
    }

    public function test_registration_warns_when_email_is_configured_to_be_logged_instead_of_sent(): void
    {
        config(['mail.default' => 'log']);
        Notification::fake();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        ]);

        $this->post(route('customer.register.store'), [
            'full_name' => 'Cliente Independiente',
            'email' => 'correo.registrado@example.test',
            'customer_type' => 'individual',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
        ])->assertRedirect(route('verification.notice'))
            ->assertSessionHasErrors([
                'email' => 'Tu cuenta se creó, pero el servidor solo registra los correos y no los envía. Contacta al administrador para configurar el envío.',
            ]);

        $this->assertAuthenticated();
        Notification::assertNothingSent();
    }

    public function test_registration_keeps_the_account_and_reports_a_mail_transport_failure_in_spanish(): void
    {
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        ]);
        $this->mock(Dispatcher::class)
            ->shouldReceive('send')
            ->once()
            ->andThrow(new TransportException('SMTP unavailable'));

        $this->followingRedirects()
            ->post(route('customer.register.store'), [
                'full_name' => 'Cliente Independiente',
                'email' => 'correo.sin.envio@example.test',
                'customer_type' => 'individual',
                'password' => 'secure-password',
                'password_confirmation' => 'secure-password',
            ])->assertOk()
            ->assertSee('Tu cuenta se creó, pero no se pudo enviar el correo de verificación.');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'correo.sin.envio@example.test',
            'role' => 'customer',
        ]);
    }

    public function test_verification_resend_warns_when_email_is_configured_to_be_logged_instead_of_sent(): void
    {
        config(['mail.default' => 'log']);
        Notification::fake();
        $user = User::factory()->create(['email_verified_at' => null]);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertSessionHasErrors([
                'email' => 'El servidor no está configurado para enviar correos. Contacta al administrador.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_orphan_customer_can_register_only_three_vehicles_during_the_trial(): void
    {
        $customer = Customer::create([
            'full_name' => 'Propietaria Independiente',
            'email' => 'propietaria@example.test',
            'customer_type' => 'individual',
            'fleet_trial_started_at' => now(),
        ]);
        $user = User::create([
            'name' => $customer->full_name,
            'username' => 'propietaria',
            'email' => $customer->email,
            'password' => 'secure-password',
            'role' => 'customer',
            'customer_id' => $customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $user->markEmailAsVerified();

        $payload = [
            'vehicle_type' => 'carro',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'current_mileage' => 100,
        ];

        for ($index = 1; $index <= 3; $index++) {
            $this->actingAs($user)
                ->post(route('portal.vehicles.store'), $payload + ['plate' => "ORP00{$index}"])
                ->assertRedirect();
        }

        $this->assertSame(3, $customer->vehicles()->count());
        $this->assertDatabaseHas('vehicles', [
            'customer_id' => $customer->id,
            'company_id' => null,
            'plate' => 'ORP001',
        ]);

        $this->actingAs($user)
            ->post(route('portal.vehicles.store'), $payload + ['plate' => 'ORP004'])
            ->assertSessionHasErrors('vehicle_limit');

        $this->assertSame(3, $customer->fresh()->vehicles()->count());
    }

    public function test_orphan_customer_can_register_a_vehicle_with_a_photo(): void
    {
        Storage::fake('public');

        $customer = Customer::create([
            'full_name' => 'Propietario Independiente',
            'email' => 'propietario@example.test',
            'customer_type' => 'individual',
            'fleet_trial_started_at' => now(),
        ]);
        $user = User::create([
            'name' => $customer->full_name,
            'username' => 'propietario',
            'email' => $customer->email,
            'password' => 'secure-password',
            'role' => 'customer',
            'customer_id' => $customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $user->markEmailAsVerified();

        $this->actingAs($user)
            ->post(route('portal.vehicles.store'), [
                'plate' => 'ORP010',
                'vehicle_type' => 'carro',
                'make' => 'Toyota',
                'model' => 'Corolla',
                'current_mileage' => 100,
                'fuel_type' => 'hibrido',
                'photo' => UploadedFile::fake()->image('vehiculo.png'),
            ])
            ->assertRedirect();

        $vehicle = $customer->vehicles()->firstOrFail();

        $this->assertSame('hibrido', $vehicle->fuel_type);
        $this->assertNotNull($vehicle->photo_path);
        Storage::disk('public')->assertExists($vehicle->photo_path);
    }

    public function test_orphan_customer_can_update_their_vehicle(): void
    {
        Storage::fake('public');

        $customer = Customer::create([
            'full_name' => 'Propietario Independiente',
            'email' => 'propietario@example.test',
            'customer_type' => 'individual',
            'fleet_trial_started_at' => now(),
        ]);
        $user = User::create([
            'name' => $customer->full_name,
            'username' => 'propietario',
            'email' => $customer->email,
            'password' => 'secure-password',
            'role' => 'customer',
            'customer_id' => $customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $user->markEmailAsVerified();

        $this->actingAs($user)
            ->post(route('portal.vehicles.store'), [
                'plate' => 'ORP020',
                'vehicle_type' => 'carro',
                'make' => 'Toyota',
                'model' => 'Corolla',
                'current_mileage' => 100,
            ])
            ->assertRedirect();

        $vehicle = $customer->vehicles()->firstOrFail();

        $this->actingAs($user)
            ->put(route('portal.vehicles.update', $vehicle), [
                'plate' => 'orp021',
                'vehicle_type' => 'carro',
                'make' => 'Toyota',
                'model' => 'Corolla Cross',
                'current_mileage' => 250,
                'fuel_type' => 'electrico',
                'photo' => UploadedFile::fake()->image('nueva-foto.png'),
            ])
            ->assertRedirect(route('portal.vehicles.show', $vehicle));

        $vehicle->refresh();

        $this->assertSame('ORP021', $vehicle->plate);
        $this->assertSame('Corolla Cross', $vehicle->model);
        $this->assertSame('electrico', $vehicle->fuel_type);
        $this->assertSame(250, $vehicle->current_mileage);
        $this->assertNotNull($vehicle->photo_path);
        Storage::disk('public')->assertExists($vehicle->photo_path);
    }

    public function test_orphan_customer_can_register_the_same_plate_again_after_decommission(): void
    {
        Storage::fake('public');

        $customer = Customer::create([
            'full_name' => 'Propietario Independiente',
            'email' => 'propietario@example.test',
            'customer_type' => 'individual',
            'fleet_trial_started_at' => now(),
        ]);
        $user = User::create([
            'name' => $customer->full_name,
            'username' => 'propietario',
            'email' => $customer->email,
            'password' => 'secure-password',
            'role' => 'customer',
            'customer_id' => $customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $user->markEmailAsVerified();

        $payload = [
            'vehicle_type' => 'carro',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'current_mileage' => 100,
        ];

        $this->actingAs($user)
            ->post(route('portal.vehicles.store'), $payload + ['plate' => 'ORP030'])
            ->assertRedirect();

        $vehicle = $customer->vehicles()->firstOrFail();

        $this->actingAs($user)
            ->post(route('portal.vehicles.decommission', $vehicle))
            ->assertRedirect(route('portal.vehicles.index'));

        $this->assertSame(0, $customer->fresh()->vehicles()->count());
        $this->assertDatabaseMissing('vehicles', ['plate' => 'ORP030']);

        $this->actingAs($user)
            ->post(route('portal.vehicles.store'), $payload + ['plate' => 'ORP030'])
            ->assertRedirect();

        $this->assertDatabaseHas('vehicles', [
            'customer_id' => $customer->id,
            'plate' => 'ORP030',
            'deleted_at' => null,
        ]);
    }

    public function test_orphan_customer_can_see_the_platform_policies_link_in_the_portal_menu(): void
    {
        $this->seed(PlatformPolicySeeder::class);

        $customer = Customer::create([
            'full_name' => 'Propietario Independiente',
            'email' => 'propietario@example.test',
            'customer_type' => 'individual',
            'fleet_trial_started_at' => now(),
        ]);
        $user = User::create([
            'name' => $customer->full_name,
            'username' => 'propietario',
            'email' => $customer->email,
            'password' => 'secure-password',
            'role' => 'customer',
            'customer_id' => $customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $user->markEmailAsVerified();

        $policy = PlatformPolicy::activePolicy();
        UserPolicyAcceptance::create([
            'user_id' => $user->id,
            'platform_policy_id' => $policy->id,
            'policy_version' => $policy->version,
            'accepted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('portal.vehicles.index'))
            ->assertOk()
            ->assertSee('Políticas y Condiciones')
            ->assertSee('viewAcceptedPolicyModal');
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
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
}

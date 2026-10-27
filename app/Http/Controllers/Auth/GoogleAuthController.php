<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Companies\Models\Customer;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->isGoogleConfigured()) {
            return $this->failed('El inicio de sesión con Google todavía no está configurado.');
        }

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isGoogleConfigured()) {
            return $this->failed('El inicio de sesión con Google todavía no está configurado.');
        }

        if ($request->query('error')) {
            return $this->failed('Se canceló el inicio de sesión con Google. Puedes intentarlo de nuevo.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            Log::notice('Se rechazó una respuesta OAuth de Google con estado inválido.', [
                'exception_class' => $exception::class,
            ]);

            return $this->failed('La sesión de Google expiró o no es válida. Inicia el proceso nuevamente.');
        } catch (GuzzleException $exception) {
            Log::error('No se pudo completar la autenticación con Google.', [
                'exception_class' => $exception::class,
            ]);

            return $this->failed('No se pudo verificar tu cuenta de Google. Inténtalo nuevamente más tarde.');
        }

        $email = $googleUser->getEmail();
        $googleAttributes = $googleUser->getRaw();

        if (! is_string($email)
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || ($googleAttributes['email_verified'] ?? false) !== true) {
            return $this->failed('Google no confirmó una dirección de correo válida para esta cuenta.');
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
            ->first();

        if (! $user) {
            if (Customer::withTrashed()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->exists()) {
                return $this->failed('Este correo ya aparece en el sistema como contacto de un taller. Solicita al taller que te envíe una invitación para activar el acceso.');
            }

            $user = $this->createOrphanFleetCustomer($email, $googleUser->getName());
        }

        if (($user->role === 'customer' && $user->account_status === 'INVITED')
            || ! $user->is_active
            || in_array($user->account_status, ['BLOCKED', 'DEACTIVATED'], true)) {
            return $this->failed('Tu cuenta se encuentra pendiente de activación o desactivada.');
        }

        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'email_verification_required' => false,
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        if ($user->role === 'customer') {
            $user->customer?->startFleetTrialIfNeeded();

            return redirect()->intended(route('portal.dashboard'));
        }

        if ($user->role === 'company_admin') {
            $request->session()->forget('url.intended');

            return redirect()->route('company.dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }

    private function isGoogleConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }

    private function createOrphanFleetCustomer(string $email, ?string $googleName): User
    {
        return DB::transaction(function () use ($email, $googleName): User {
            $normalizedEmail = mb_strtolower($email);
            $name = trim((string) $googleName);
            if ($name === '') {
                $name = mb_strstr($normalizedEmail, '@', true) ?: $normalizedEmail;
            }

            $customer = Customer::create([
                'company_id' => null,
                'full_name' => mb_substr($name, 0, 255),
                'email' => $normalizedEmail,
                'customer_type' => 'fleet_owner',
                'fleet_trial_started_at' => now(),
            ]);

            $baseUsername = Str::slug(mb_strstr($normalizedEmail, '@', true) ?: $name, '_');
            $baseUsername = mb_substr($baseUsername !== '' ? $baseUsername : 'cliente', 0, 50);
            $username = $baseUsername;
            $suffix = 1;

            while (User::query()->where('username', $username)->exists()) {
                $suffixText = '_'.$suffix;
                $username = mb_substr($baseUsername, 0, 50 - mb_strlen($suffixText)).$suffixText;
                $suffix++;
            }

            return User::create([
                'name' => mb_substr($name, 0, 255),
                'username' => $username,
                'email' => $normalizedEmail,
                'password' => Str::random(64),
                'role' => 'customer',
                'company_id' => null,
                'customer_id' => $customer->id,
                'is_active' => true,
                'account_status' => 'ACTIVE',
                'activated_at' => now(),
                'email_verified_at' => now(),
                'email_verification_required' => false,
            ]);
        });
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['google' => $message]);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\TurnstileRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Muestra la vista del formulario de login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Procesa la solicitud de inicio de sesión por username.
     */
    public function store(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'cf-turnstile-response' => [new TurnstileRule],
        ], [
            'cf-turnstile-response.required' => 'Por favor complete la verificación de seguridad (Captcha).',
        ]);

        $loginValue = $input['username'];
        $password = $input['password'];
        $remember = $request->boolean('remember');

        // Permitir autenticarse por username o por email (vital para clientes)
        $user = \App\Models\User::where('username', $loginValue)
            ->orWhere('email', $loginValue)
            ->first();

        if (! $user) {
            return back()->withErrors([
                'username' => 'Las credenciales proporcionadas no son válidas.',
            ])->onlyInput('username');
        }

        // Validar si el cliente aún no ha activado su cuenta
        if ($user->role === 'customer' && $user->account_status === 'INVITED') {
            return back()->withErrors([
                'username' => 'Tu cuenta aún no ha sido activada. Por favor, revisa el correo de invitación enviado a tu email para crear tu contraseña.',
            ])->onlyInput('username');
        }

        if (! $user->is_active || in_array($user->account_status, ['BLOCKED', 'DEACTIVATED'], true)) {
            return back()->withErrors([
                'username' => 'Tu cuenta se encuentra desactivada o dada de baja del sistema.',
            ])->onlyInput('username');
        }

        $credentials = [
            'id' => $user->id,
            'password' => $password,
        ];

        if (! Auth::attempt($credentials, $remember)) {
            return back()->withErrors([
                'username' => 'Las credenciales proporcionadas no son válidas.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();
        $user->update(['last_login_at' => now()]);

        // Redirigir según el rol
        if ($user->role === 'customer') {
            return redirect()->intended(route('portal.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Cierra la sesión del usuario autenticado.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Has cerrado sesión correctamente.');
    }
}

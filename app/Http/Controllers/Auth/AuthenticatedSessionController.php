<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt(array_merge($credentials, ['is_active' => true]), $remember)) {
            // Verificar si el usuario existe pero está inactivo para retroalimentación precisa
            $userExists = \App\Models\User::where('username', $credentials['username'])->first();
            if ($userExists && ! $userExists->is_active) {
                return back()->withErrors([
                    'username' => 'Tu cuenta se encuentra desactivada. Contacta al superadministrador.',
                ])->onlyInput('username');
            }

            return back()->withErrors([
                'username' => 'Las credenciales proporcionadas no son válidas.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();

        // Actualizar último inicio de sesión
        $request->user()?->update(['last_login_at' => now()]);

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

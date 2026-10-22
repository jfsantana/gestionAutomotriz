<?php

namespace App\Http\Controllers;

use App\Domain\Users\Models\ClientInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ClientActivationController extends Controller
{
    /**
     * Muestra la pantalla para que el cliente cree su contraseña y active su cuenta.
     */
    public function show(string $token): View|RedirectResponse
    {
        $hashedToken = hash('sha256', $token);

        $invitation = ClientInvitation::with(['customer', 'company', 'user'])
            ->where('token', $hashedToken)
            ->first();

        if (! $invitation || ! $invitation->isValid()) {
            return view('portal.auth.expired_token', [
                'isExpired' => $invitation && $invitation->isExpired(),
            ]);
        }

        return view('portal.auth.activate', compact('invitation', 'token'));
    }

    /**
     * Procesa la creación de la contraseña, activa la cuenta y loguea al cliente.
     */
    public function activate(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $hashedToken = hash('sha256', $token);

        $invitation = ClientInvitation::with(['user', 'customer'])
            ->where('token', $hashedToken)
            ->first();

        if (! $invitation || ! $invitation->isValid()) {
            return redirect()->route('login')->withErrors(['username' => 'El enlace de activación ha expirado o ya fue utilizado.']);
        }

        $user = $invitation->user;

        $user->update([
            'password' => Hash::make($request->password),
            'is_active' => true,
            'account_status' => 'ACTIVE',
            'activated_at' => now(),
        ]);

        $invitation->update(['used_at' => now()]);

        Auth::login($user);

        return redirect()->route('portal.dashboard')->with('success', '¡Bienvenido a AutoBitacora! Tu cuenta ha sido activada correctamente.');
    }
}

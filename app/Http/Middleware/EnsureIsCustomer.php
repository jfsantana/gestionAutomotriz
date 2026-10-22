<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsCustomer
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Si es super_admin, se le permite supervisar si entra expresamente
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        // Si no es rol customer, bloquear con 403
        if ($user->role !== 'customer') {
            abort(403, 'Acceso restringido únicamente para usuarios clientes.');
        }

        // Si el cliente no está activo o solicitó baja
        if ($user->account_status !== 'ACTIVE' || ! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Tu cuenta de cliente aún no ha sido activada o ha sido dada de baja del sistema.',
            ]);
        }

        return $next($request);
    }
}

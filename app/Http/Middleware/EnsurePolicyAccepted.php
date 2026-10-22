<?php

namespace App\Http\Middleware;

use App\Domain\Users\Models\PlatformPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePolicyAccepted
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si el usuario no está autenticado, continuar (login manejará)
        if (! $user) {
            return $next($request);
        }

        // Si la ruta actual es la de políticas o logout, permitir
        if ($request->routeIs('policy.*', 'logout')) {
            return $next($request);
        }

        $activePolicy = PlatformPolicy::activePolicy();

        // Si existe política vigente y el usuario no la ha aceptado, forzar redirección
        if ($activePolicy && ! $user->hasAcceptedCurrentPolicy()) {
            return redirect()->route('policy.show');
        }

        return $next($request);
    }
}

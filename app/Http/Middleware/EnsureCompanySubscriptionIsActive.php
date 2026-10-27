<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanySubscriptionIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Superadmin tiene bypass global
        if (! $user || $user->role === 'super_admin') {
            return $next($request);
        }

        // 2. Si no tiene empresa asociada, continuar
        if (! $user->company_id || ! $user->company) {
            return $next($request);
        }

        $subscription = $user->company->currentSubscription;

        // Una empresa sin una suscripción asignada no tiene acceso operativo.
        if (! $subscription) {
            return redirect()->route('subscription.status');
        }

        // 4. Si tiene suscripción pero no es operable (vencida/suspendida/cancelada)
        if (! $subscription->isOperable()) {
            return redirect()->route('subscription.status');
        }

        return $next($request);
    }
}

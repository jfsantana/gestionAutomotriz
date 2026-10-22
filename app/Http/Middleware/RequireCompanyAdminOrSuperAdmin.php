<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCompanyAdminOrSuperAdmin
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

        if ($user->role === 'super_admin') {
            return $next($request);
        }

        if ($user->role === 'company_admin' && $user->company_id) {
            return $next($request);
        }

        abort(403, 'Acceso restringido. Requiere privilegios de administrador de empresa.');
    }
}

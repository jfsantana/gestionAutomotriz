<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWorkshopUser
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->role === 'super_admin'
                || (in_array($user->role, ['company_admin', 'mechanic'], true) && $user->company_id && $user->company)),
            403,
            'Acceso restringido al personal de una empresa disponible.'
        );

        return $next($request);
    }
}

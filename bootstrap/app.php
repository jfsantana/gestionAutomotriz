<?php

use App\Http\Middleware\EnsureCompanySubscriptionIsActive;
use App\Http\Middleware\EnsureIsCustomer;
use App\Http\Middleware\EnsurePolicyAccepted;
use App\Http\Middleware\RequireCompanyAdminOrSuperAdmin;
use App\Http\Middleware\RequireSuperAdmin;
use App\Http\Middleware\RequireWorkshopUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'super_admin' => RequireSuperAdmin::class,
            'company_admin' => RequireCompanyAdminOrSuperAdmin::class,
            'workshop' => RequireWorkshopUser::class,
            'subscription.active' => EnsureCompanySubscriptionIsActive::class,
            'policy.accepted' => EnsurePolicyAccepted::class,
            'customer.only' => EnsureIsCustomer::class,
        ]);

        // Asegurar que EnsurePolicyAccepted se evalúe en todas las rutas web
        $middleware->web(append: [
            EnsurePolicyAccepted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

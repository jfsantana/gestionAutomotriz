<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySubscriptionStatusController extends Controller
{
    /**
     * Muestra la pantalla de estado de suscripción del taller (incluye pantalla de bloqueo si está vencido/suspendido).
     */
    public function show(Request $request): View
    {
        $currentUser = $request->user();
        $company = $currentUser->company;

        if (! $company) {
            abort(404, 'No tienes una empresa asignada.');
        }

        $subscription = $company->currentSubscription;
        $isOperable = $subscription && $subscription->isOperable();

        return view('company_subscriptions.status', compact('company', 'subscription', 'isOperable'));
    }
}

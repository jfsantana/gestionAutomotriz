<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Models\CompanyPaymentRequest;
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
        $companyAccessSuspended = $company->status === 'suspended';
        $isOperable = $subscription && $subscription->isOperable() && ! $companyAccessSuspended;
        $isInGracePeriod = ! $companyAccessSuspended && ($subscription?->isInGracePeriod() ?? false);
        $pendingPaymentRequest = CompanyPaymentRequest::query()
            ->where('company_id', $company->id)
            ->where('status', 'requested')
            ->latest('id')
            ->first();
        $canRequestPayment = $currentUser->role === 'company_admin'
            && ! $companyAccessSuspended
            && $subscription
            && ! in_array($subscription->status, ['suspended', 'cancelled'], true)
            && $subscription->plan
            && ((float) $subscription->plan->monthly_price > 0 || (float) $subscription->plan->yearly_price > 0);

        return view('company_subscriptions.status', compact(
            'company',
            'subscription',
            'companyAccessSuspended',
            'isOperable',
            'isInGracePeriod',
            'pendingPaymentRequest',
            'canRequestPayment',
        ));
    }
}

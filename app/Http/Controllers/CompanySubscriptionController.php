<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Companies\Models\Company;
use App\Http\Requests\CompanySubscriptions\StoreCompanySubscriptionRequest;
use App\Http\Requests\CompanySubscriptions\UpdateCompanySubscriptionRequest;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function index(): View
    {
        $companies = Company::query()
            ->with(['currentSubscription.plan'])
            ->latest('id')
            ->paginate(10);

        return view('company_subscriptions.index', compact('companies'));
    }

    public function create(Request $request): View
    {
        $companyId = $request->input('company_id');
        $company = $companyId ? Company::findOrFail($companyId) : null;

        $companies = Company::query()->orderBy('name')->get(['id', 'name', 'status']);
        $plans = SubscriptionPlan::query()->where('is_active', true)->orderBy('name')->get();

        $subscription = new CompanySubscription([
            'company_id' => $company?->id,
            'status' => 'trial',
            'billing_cycle' => 'monthly',
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(15)->toDateString(),
        ]);

        return view('company_subscriptions.create', compact('subscription', 'companies', 'plans', 'company'));
    }

    public function store(StoreCompanySubscriptionRequest $request): RedirectResponse
    {
        $subscription = $this->subscriptionService->assignSubscription(
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('company-subscriptions.index')
            ->with('success', "Suscripción asignada exitosamente a la empresa.");
    }

    public function edit(CompanySubscription $company_subscription): View
    {
        $company_subscription->load(['company', 'plan', 'logs.performer']);
        $plans = SubscriptionPlan::query()->where('is_active', true)->orderBy('name')->get();

        return view('company_subscriptions.edit', [
            'subscription' => $company_subscription,
            'plans' => $plans,
        ]);
    }

    public function update(UpdateCompanySubscriptionRequest $request, CompanySubscription $company_subscription): RedirectResponse
    {
        $this->subscriptionService->updateSubscription(
            $company_subscription,
            $request->validated(),
            $request->user()
        );

        return redirect()
            ->route('company-subscriptions.index')
            ->with('success', "Suscripción de la empresa actualizada.");
    }

    public function renew(Request $request, CompanySubscription $company_subscription): RedirectResponse
    {
        $months = (int) $request->input('months', 1);
        $startsAt = now();
        $endsAt = (clone $startsAt)->addMonths($months);

        $this->subscriptionService->updateSubscription($company_subscription, [
            'subscription_plan_id' => $company_subscription->subscription_plan_id,
            'status' => 'active',
            'billing_cycle' => $company_subscription->billing_cycle,
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => $endsAt->toDateString(),
            'notes' => "Renovación manual de {$months} mes(es).",
        ], $request->user());

        return back()->with('success', "Suscripción renovada por {$months} mes(es).");
    }

    public function toggleSuspension(Request $request, CompanySubscription $company_subscription): RedirectResponse
    {
        $isCurrentlySuspended = $company_subscription->status === 'suspended';
        $newStatus = $isCurrentlySuspended ? 'active' : 'suspended';

        $this->subscriptionService->updateSubscription($company_subscription, [
            'subscription_plan_id' => $company_subscription->subscription_plan_id,
            'status' => $newStatus,
            'billing_cycle' => $company_subscription->billing_cycle,
            'starts_at' => $company_subscription->starts_at->toDateString(),
            'ends_at' => $company_subscription->ends_at?->toDateString(),
            'notes' => $isCurrentlySuspended ? 'Reactivación manual de acceso.' : 'Suspensión manual de acceso por Superadmin.',
        ], $request->user());

        $msg = $isCurrentlySuspended ? 'Empresa reactivada con éxito.' : 'Empresa suspendida; su acceso operativo ha sido bloqueado.';

        return back()->with('success', $msg);
    }
}

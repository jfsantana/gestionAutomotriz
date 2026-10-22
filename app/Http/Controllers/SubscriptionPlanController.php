<?php

namespace App\Http\Controllers;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Http\Requests\SubscriptionPlans\StoreSubscriptionPlanRequest;
use App\Http\Requests\SubscriptionPlans\UpdateSubscriptionPlanRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::query()
            ->withCount('subscriptions')
            ->latest('id')
            ->paginate(10);

        return view('subscription_plans.index', compact('plans'));
    }

    public function create(): View
    {
        $plan = new SubscriptionPlan([
            'is_active' => true,
            'is_trial' => false,
            'monthly_price' => 0,
            'yearly_price' => 0,
        ]);

        return view('subscription_plans.create', compact('plan'));
    }

    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_trial'] = $request->boolean('is_trial', false);

        $plan = SubscriptionPlan::create($data);

        return redirect()
            ->route('subscription-plans.index')
            ->with('success', "Plan '{$plan->name}' creado exitosamente.");
    }

    public function edit(SubscriptionPlan $subscription_plan): View
    {
        return view('subscription_plans.edit', ['plan' => $subscription_plan]);
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscription_plan): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        $data['is_active'] = $request->boolean('is_active', false);
        $data['is_trial'] = $request->boolean('is_trial', false);

        $subscription_plan->update($data);

        return redirect()
            ->route('subscription-plans.index')
            ->with('success', "Plan '{$subscription_plan->name}' actualizado correctamente.");
    }

    public function toggleStatus(SubscriptionPlan $subscription_plan): RedirectResponse
    {
        $subscription_plan->is_active = ! $subscription_plan->is_active;
        $subscription_plan->save();

        $estado = $subscription_plan->is_active ? 'activado' : 'desactivado';

        return redirect()
            ->route('subscription-plans.index')
            ->with('success', "El plan '{$subscription_plan->name}' fue {$estado}.");
    }
}

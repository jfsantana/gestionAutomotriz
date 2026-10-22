<?php

namespace App\Http\Requests\SubscriptionPlans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'super_admin';
    }

    public function rules(): array
    {
        $planId = $this->route('subscription_plan')?->id ?? $this->route('subscription_plan');

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', 'alpha_dash', Rule::unique('subscription_plans', 'slug')->ignore($planId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'yearly_price' => ['nullable', 'numeric', 'min:0'],
            'max_internal_users' => ['nullable', 'integer', 'min:1'],
            'max_mechanics' => ['nullable', 'integer', 'min:1'],
            'max_maintenance_orders_per_month' => ['nullable', 'integer', 'min:1'],
            'max_vehicles' => ['nullable', 'integer', 'min:1'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'is_trial' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

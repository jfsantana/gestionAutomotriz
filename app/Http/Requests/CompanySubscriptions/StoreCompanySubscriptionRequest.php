<?php

namespace App\Http\Requests\CompanySubscriptions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanySubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'super_admin';
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'subscription_plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->whereNull('deleted_at')],
            'status' => ['required', 'string', Rule::in(['trial', 'active', 'expired', 'suspended', 'cancelled'])],
            'billing_cycle' => ['required', 'string', Rule::in(['monthly', 'yearly', 'trial', 'custom'])],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['super_admin', 'company_admin'], true);
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'maintenance_type' => ['required', 'string', Rule::in(['preventive', 'corrective'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_interval_days' => ['nullable', 'integer', 'min:1'],
            'default_interval_mileage' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($this->user()?->role === 'super_admin') {
            $rules['company_id'] = ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }
}

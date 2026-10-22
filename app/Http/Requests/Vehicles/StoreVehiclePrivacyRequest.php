<?php

namespace App\Http\Requests\Vehicles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehiclePrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['super_admin', 'company_admin'], true);
    }

    public function rules(): array
    {
        $currentUser = $this->user();
        $effectiveCompanyId = $currentUser->role === 'company_admin'
            ? $currentUser->company_id
            : $this->input('company_id');

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(function ($q) use ($effectiveCompanyId) {
                    if ($effectiveCompanyId) {
                        $q->where('company_id', $effectiveCompanyId);
                    }
                    $q->whereNull('deleted_at');
                }),
            ],
            'plate' => ['required', 'string', 'max:30'],
            'vin' => ['nullable', 'string', 'max:50'],
            'make' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 2)],
            'color' => ['nullable', 'string', 'max:50'],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

<?php

namespace App\Http\Requests\MaintenanceOrders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && in_array($user->role, ['super_admin', 'company_admin', 'mechanic'], true);
    }

    public function rules(): array
    {
        $order = $this->route('maintenance_order');
        $effectiveCompanyId = $order?->company_id;

        return [
            'mechanic_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) use ($effectiveCompanyId) {
                    if ($effectiveCompanyId) {
                        $q->where('company_id', $effectiveCompanyId);
                    }
                    $q->where('role', 'mechanic');
                }),
            ],
            'maintenance_type' => ['required', 'string', Rule::in(['preventive', 'corrective'])],
            'service_name_snapshot' => ['required', 'string', 'max:150'],
            'service_description_snapshot' => ['nullable', 'string', 'max:1000'],
            'service_date' => ['required', 'date'],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'technical_notes' => ['nullable', 'string', 'max:2000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'string', Rule::in(['open', 'in_progress', 'completed', 'cancelled'])],
            'next_due_date' => ['nullable', 'date'],
            'next_due_mileage' => ['nullable', 'integer', 'min:0'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
        ];
    }
}

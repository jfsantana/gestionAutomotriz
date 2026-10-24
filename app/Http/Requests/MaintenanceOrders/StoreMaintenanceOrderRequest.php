<?php

namespace App\Http\Requests\MaintenanceOrders;

use App\Domain\Companies\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && in_array($user->role, ['super_admin', 'company_admin', 'mechanic'], true);
    }

    public function rules(): array
    {
        $currentUser = $this->user();
        $effectiveCompanyId = $currentUser->role === 'super_admin'
            ? Customer::find($this->integer('customer_id'))?->company_id
            : $currentUser->company_id;

        return [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(function ($q) use ($effectiveCompanyId) {
                    $q->where('company_id', $effectiveCompanyId);
                    $q->whereNull('deleted_at');
                }),
            ],
            'vehicle_id' => [
                'required',
                'integer',
                Rule::exists('vehicles', 'id')->where(function ($q) use ($effectiveCompanyId) {
                    $q->where('company_id', $effectiveCompanyId)->where('customer_id', $this->integer('customer_id'));
                    $q->whereNull('deleted_at');
                }),
            ],
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->where(function ($q) use ($effectiveCompanyId) {
                    $q->where('company_id', $effectiveCompanyId);
                    $q->whereNull('deleted_at');
                }),
            ],
            'mechanic_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($q) use ($effectiveCompanyId) {
                    $q->where('company_id', $effectiveCompanyId);
                    $q->where('role', 'mechanic');
                }),
            ],
            'maintenance_type' => ['required', 'string', Rule::in(['preventive', 'corrective'])],
            'service_name_snapshot' => ['nullable', 'string', 'max:150'],
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
            'photos.*' => ['image', 'max:5120'], // Máximo 5MB por imagen
        ];
    }
}

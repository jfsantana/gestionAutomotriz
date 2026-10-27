<?php

namespace App\Http\Requests\Vehicles;

use App\Domain\Vehicles\Enums\VehicleType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && in_array($user->role, ['super_admin', 'company_admin'], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentUser = $this->user();

        // Determinar company_id efectivo
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
            'plate' => [
                'required',
                'string',
                'max:30',
                Rule::unique('vehicles', 'plate')->where(function ($q) use ($effectiveCompanyId) {
                    if ($effectiveCompanyId) {
                        $q->where('company_id', $effectiveCompanyId);
                    }
                    $q->whereNull('deleted_at');
                }),
            ],
            'vin' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['required', Rule::in(array_keys(VehicleType::options()))],
            'make' => [
                'required',
                'string',
                'max:80',
                Rule::exists('vehicle_brands', 'name')
                    ->where(fn ($query) => $query->where('vehicle_type', $this->input('vehicle_type'))),
            ],
            'model' => ['required', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 2)],
            'color' => ['nullable', 'string', 'max:50'],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}

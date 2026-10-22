<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'document_type' => ['nullable', 'string', 'max:30'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'customer_type' => ['required', 'string', Rule::in(['individual', 'fleet_owner'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->user()?->role === 'super_admin') {
            $rules['company_id'] = ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }
}

<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(['mechanic'])],
            'is_active' => ['nullable', 'boolean'],
        ];

        // Si es super_admin, se permite seleccionar la empresa; para company_admin se asigna su propio company_id
        if ($this->user()?->role === 'super_admin') {
            $rules['company_id'] = ['required', 'integer', Rule::exists('companies', 'id')->whereNull('deleted_at')];
        }

        return $rules;
    }
}

<?php

namespace App\Http\Requests\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyProfileRequest extends FormRequest
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
     * Reglas de validación: protege SLUG y TAX_ID (RNC) impidiendo su modificación.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:190'],
            'instagram_url' => ['nullable', 'url', 'max:190'],
            'facebook_url' => ['nullable', 'url', 'max:190'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'size:2'],
            'theme_palette' => ['required', 'string', Rule::in(['ford_blue', 'honda_red', 'workshop_classic'])],
            'logo' => ['nullable', 'image', 'max:4096'], // Máx 4MB
        ];
    }
}

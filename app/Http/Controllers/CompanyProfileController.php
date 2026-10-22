<?php

namespace App\Http\Controllers;

use App\Http\Requests\Companies\UpdateCompanyProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    /**
     * Muestra la pantalla para que la empresa configure sus datos, logo y paleta.
     */
    public function edit(Request $request): View
    {
        $company = $request->user()->company;

        if (! $company) {
            abort(404, 'No tienes una empresa asignada.');
        }

        return view('companies.profile', compact('company'));
    }

    /**
     * Actualiza la información permitida (sin tocar SLUG ni RNC/TAX_ID).
     */
    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $company = $request->user()->company;

        if (! $company) {
            abort(404, 'No tienes una empresa asignada.');
        }

        $data = $request->validated();

        // Procesar subida de logo si se adjuntó
        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existía
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $path = $request->file('logo')->store('company_logos', 'public');
            $data['logo_path'] = $path;
        }

        // Blindaje explícito: jamás alterar slug ni tax_id
        unset($data['slug'], $data['tax_id'], $data['status']);

        $company->update($data);

        return redirect()
            ->route('company.profile.edit')
            ->with('success', 'Perfil, logotipo e identidad visual del taller actualizados correctamente.');
    }
}

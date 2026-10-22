<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Company;
use App\Http\Requests\Companies\StoreCompanyRequest;
use App\Http\Requests\Companies\UpdateCompanyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    /**
     * Muestra el listado de empresas / talleres.
     */
    public function index(): View
    {
        $companies = Company::query()
            ->latest('id')
            ->paginate(10);

        return view('companies.index', compact('companies'));
    }

    /**
     * Muestra el formulario para registrar una nueva empresa.
     */
    public function create(): View
    {
        $company = new Company(['status' => 'trial', 'country' => 'DO']);

        return view('companies.create', compact('company'));
    }

    /**
     * Guarda una nueva empresa en la base de datos.
     */
    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Company::generateUniqueSlug($data['name']);
        }

        $company = Company::create($data);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', "La empresa '{$company->name}' se ha creado exitosamente.");
    }

    /**
     * Muestra el detalle de una empresa con el Administrador y el resumen de personal y vehículos.
     */
    public function show(Company $company): View
    {
        $company->load([
            'admins',
            'mechanics',
            'currentSubscription.plan',
        ]);

        $company->loadCount([
            'customers',
            'vehicles',
            'mechanics',
        ]);

        return view('companies.show', compact('company'));
    }

    /**
     * Muestra el formulario para editar una empresa existente.
     */
    public function edit(Company $company): View
    {
        return view('companies.edit', compact('company'));
    }

    /**
     * Actualiza los datos de la empresa.
     */
    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['slug'])) {
            $data['slug'] = Company::generateUniqueSlug($data['name'], $company->id);
        }

        $company->update($data);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', "La empresa '{$company->name}' fue actualizada correctamente.");
    }

    /**
     * Aplica eliminación lógica (soft delete) a la empresa.
     */
    public function destroy(Company $company): RedirectResponse
    {
        $name = $company->name;
        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', "La empresa '{$name}' ha sido eliminada.");
    }
}

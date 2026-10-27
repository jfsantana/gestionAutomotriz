<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Company;
use App\Http\Requests\Companies\StoreCompanyRequest;
use App\Http\Requests\Companies\UpdateCompanyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
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

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company_logos', 'public');
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

        // En edición, preservar el slug original inmutable
        unset($data['slug']);

        if ($data['status'] === 'suspended' && $company->status !== 'suspended' && $company->hasActiveFleetCustomers()) {
            return back()
                ->withInput()
                ->with('error', 'No se puede suspender este taller porque tiene clientes con una suscripción de flota vigente.');
        }

        if ($request->hasFile('logo')) {
            if ($company->logo_path && Storage::disk('public')->exists($company->logo_path)) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('company_logos', 'public');
        }

        $company->update($data);

        return redirect()
            ->route('companies.show', $company)
            ->with('success', "La empresa '{$company->name}' fue actualizada correctamente.");
    }

    /**
     * Suspende o reactiva virtualmente el acceso operativo de una empresa.
     */
    public function toggleAccess(Company $company): RedirectResponse
    {
        if ($company->status !== 'suspended') {
            if ($company->hasActiveFleetCustomers()) {
                return back()->with('error', 'No se puede suspender este taller porque tiene clientes con una suscripción de flota vigente.');
            }

            $company->update(['status' => 'suspended']);

            return back()->with('success', "El acceso de '{$company->name}' fue suspendido. Sus datos se conservaron.");
        }

        $subscription = $company->currentSubscription;
        if (! $subscription || ! $subscription->isOperable()) {
            return back()->with('error', 'No se puede reactivar el acceso hasta que la empresa tenga una suscripción vigente y operable.');
        }

        $company->update([
            'status' => $subscription->status === 'trial' ? 'trial' : 'active',
        ]);

        return back()->with('success', "El acceso de '{$company->name}' fue reactivado.");
    }
}

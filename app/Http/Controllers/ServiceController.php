<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Company;
use App\Domain\Maintenance\Models\Service;
use App\Http\Requests\Services\StoreServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(Request $request): View
    {
        $currentUser = $request->user();

        $query = Service::query()->with('company');

        if ($currentUser->role === 'company_admin') {
            $query->where('company_id', $currentUser->company_id);
        }

        $services = $query->latest('id')->paginate(10);

        return view('services.index', compact('services'));
    }

    public function create(Request $request): View
    {
        $currentUser = $request->user();
        $companies = collect();

        if ($currentUser->role === 'super_admin') {
            $companies = Company::query()->orderBy('name')->get(['id', 'name', 'slug']);
        }

        $service = new Service([
            'maintenance_type' => 'preventive',
            'is_active' => true,
            'company_id' => $currentUser->company_id,
        ]);

        return view('services.create', compact('service', 'companies'));
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $currentUser = $request->user();
        $data = $request->validated();

        if ($currentUser->role === 'company_admin') {
            $data['company_id'] = $currentUser->company_id;
        }

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);

        $service = Service::create($data);

        return redirect()
            ->route('services.index')
            ->with('success', "Servicio '{$service->name}' agregado al catálogo.");
    }

    public function edit(Request $request, Service $service): View
    {
        $this->authorizeServiceAccess($request->user(), $service);

        $companies = collect();
        if ($request->user()->role === 'super_admin') {
            $companies = Company::query()->orderBy('name')->get(['id', 'name', 'slug']);
        }

        return view('services.edit', compact('service', 'companies'));
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $this->authorizeServiceAccess($request->user(), $service);

        $data = $request->validated();

        if ($request->user()->role === 'company_admin') {
            unset($data['company_id']);
        }

        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = $request->boolean('is_active', false);

        $service->update($data);

        return redirect()
            ->route('services.index')
            ->with('success', "Servicio '{$service->name}' actualizado correctamente.");
    }

    public function toggleStatus(Request $request, Service $service): RedirectResponse
    {
        $this->authorizeServiceAccess($request->user(), $service);

        $service->is_active = ! $service->is_active;
        $service->save();

        $estado = $service->is_active ? 'activado' : 'desactivado';

        return redirect()
            ->route('services.index')
            ->with('success', "El servicio '{$service->name}' fue {$estado}.");
    }

    protected function authorizeServiceAccess($currentUser, Service $service): void
    {
        if ($currentUser->role === 'company_admin' && (int)$service->company_id !== (int)$currentUser->company_id) {
            abort(403, 'No tienes autorización para acceder a los servicios de otra empresa.');
        }
    }
}

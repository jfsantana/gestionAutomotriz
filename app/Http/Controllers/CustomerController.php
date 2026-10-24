<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Services\CustomerOwnershipService;
use App\Services\MaintenanceReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Listado de clientes según el ámbito de la empresa.
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();

        $query = Customer::query()
            ->with(['company'])
            ->withCount('vehicles');

        if ($currentUser->role === 'company_admin') {
            $query->where('company_id', $currentUser->company_id);
        }

        $customers = $query->latest('id')->paginate(10);

        return view('customers.index', compact('customers'));
    }

    /**
     * Formulario para registrar un nuevo cliente.
     */
    public function create(Request $request): View
    {
        $currentUser = $request->user();
        $companies = collect();

        if ($currentUser->role === 'super_admin') {
            $companies = Company::query()->orderBy('name')->get(['id', 'name', 'slug']);
        }

        $customer = new Customer([
            'customer_type' => 'individual',
            'company_id' => $currentUser->company_id,
        ]);

        return view('customers.create', compact('customer', 'companies'));
    }

    /**
     * Guarda el cliente y redirige a su ficha para que pueda registrar de inmediato sus vehículos.
     * Si se marcó 'enable_portal', genera e invita al cliente por correo electrónico.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $currentUser = $request->user();
        $data = $request->validated();
        $enablePortal = $request->boolean('enable_portal');
        unset($data['enable_portal']);

        if ($currentUser->role === 'company_admin') {
            $data['company_id'] = $currentUser->company_id;
        }

        $customer = Customer::create($data);

        // Si se solicitó acceso al portal y tiene email, disparar invitación
        if ($enablePortal && $customer->email) {
            app(CustomerInvitationController::class)->send($request, $customer);
        }

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Cliente '{$customer->full_name}' registrado exitosamente.".($enablePortal && $customer->email ? " Se ha enviado la invitación de acceso al portal a {$customer->email}." : ''));
    }

    /**
     * Muestra la ficha detallada del cliente con su listado de vehículos asociados.
     */
    public function show(Request $request, Customer $customer): View
    {
        $this->authorizeCustomerAccess($request->user(), $customer);

        $customer->load(['company', 'vehicles' => fn ($q) => $q->latest('id')]);

        $reminderService = app(MaintenanceReminderService::class);
        $vehicleReminders = [];
        $hasAlerts = false;

        foreach ($customer->vehicles as $veh) {
            $rem = $reminderService->getVehicleReminder($veh);
            if ($rem) {
                $vehicleReminders[$veh->id] = $rem;
                if (in_array($rem['evaluation']['status'], ['overdue', 'due_soon'], true)) {
                    $hasAlerts = true;
                }
            }
        }

        return view('customers.show', compact('customer', 'vehicleReminders', 'hasAlerts'));
    }

    /**
     * Formulario para editar un cliente.
     */
    public function edit(Request $request, Customer $customer): View
    {
        $this->authorizeCustomerAccess($request->user(), $customer);

        $companies = collect();
        if ($request->user()->role === 'super_admin') {
            $companies = Company::query()->orderBy('name')->get(['id', 'name', 'slug']);
        }

        return view('customers.edit', compact('customer', 'companies'));
    }

    /**
     * Actualiza los datos del cliente.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer, CustomerOwnershipService $ownership): RedirectResponse
    {
        $this->authorizeCustomerAccess($request->user(), $customer);

        $data = $request->validated();

        if ($request->user()->role === 'company_admin') {
            unset($data['company_id']);
        }

        $transferred = isset($data['company_id']) && (int) $data['company_id'] !== (int) $customer->company_id;
        $ownership->update($customer, $data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', $transferred
                ? 'Cliente trasladado con todos sus vehículos e historial. Se desvincularon las referencias a personal y servicios de la empresa anterior.'
                : "Datos del cliente '{$customer->full_name}' actualizados correctamente.");
    }

    /**
     * Eliminación lógica del cliente (Soft Delete).
     */
    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomerAccess($request->user(), $customer);

        $name = $customer->full_name;
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', "Cliente '{$name}' eliminado del sistema.");
    }

    /**
     * Valida de forma estricta que el usuario tenga acceso al cliente por empresa.
     */
    protected function authorizeCustomerAccess($currentUser, Customer $customer): void
    {
        if ($currentUser->role === 'company_admin' && (int) $customer->company_id !== (int) $currentUser->company_id) {
            abort(403, 'No tienes autorización para acceder a clientes de otra empresa.');
        }
    }
}

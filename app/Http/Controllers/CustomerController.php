<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Maintenance\Models\MaintenanceOrderPhoto;
use App\Domain\Vehicles\Models\Vehicle;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Services\CustomerOwnershipService;
use App\Services\MaintenanceReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            $rem = $reminderService->getVehicleReminder($veh, $customer->company_id);
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

        $ownership->update($customer, $data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Datos del cliente '{$customer->full_name}' actualizados correctamente.");
    }

    /**
     * Da de baja al cliente y elimina sus vehículos e historial si no tiene una flota premium vigente.
     */
    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeCustomerAccess($request->user(), $customer);

        if ($customer->isFleetPremiumActive()) {
            return back()->with('error', 'Este cliente tiene una suscripción de flota vigente. El taller no puede eliminar su cuenta, sus vehículos ni su historial; solo el cliente puede dar de baja su cuenta desde el portal.');
        }

        $name = $customer->full_name;
        $vehicleIds = Vehicle::withTrashed()
            ->where('customer_id', $customer->id)
            ->pluck('id');
        $orderIds = MaintenanceOrder::withTrashed()
            ->where('customer_id', $customer->id)
            ->pluck('id');
        $photoPaths = MaintenanceOrderPhoto::whereIn('maintenance_order_id', $orderIds)
            ->pluck('file_path')
            ->all();
        $vehiclePhotoPaths = Vehicle::withTrashed()
            ->whereIn('id', $vehicleIds)
            ->whereNotNull('photo_path')
            ->pluck('photo_path')
            ->all();
        $photoPaths = array_values(array_unique(array_merge($photoPaths, $vehiclePhotoPaths)));

        if ($photoPaths !== []) {
            if (! Storage::disk('public')->delete($photoPaths)) {
                return back()->with('error', 'No se pudieron eliminar todas las fotos del cliente; no se realizó la baja.');
            }
        }

        DB::transaction(function () use ($customer, $vehicleIds, $orderIds): void {
            MaintenanceOrderPhoto::whereIn('maintenance_order_id', $orderIds)->delete();
            MaintenanceOrder::withTrashed()->whereIn('id', $orderIds)->forceDelete();
            Vehicle::withTrashed()->whereIn('id', $vehicleIds)->forceDelete();

            $customer->user?->update([
                'account_status' => 'DEACTIVATED',
                'is_active' => false,
            ]);

            $customer->delete();
        });

        return redirect()
            ->route('customers.index')
            ->with('success', "Cliente '{$name}' dado de baja. Se eliminaron sus vehículos y su historial, y se desactivó su acceso al portal.");
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

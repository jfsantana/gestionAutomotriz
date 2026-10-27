<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Maintenance\Models\MaintenanceOrderPhoto;
use App\Domain\Vehicles\Enums\VehicleType;
use App\Domain\Vehicles\Models\Vehicle;
use App\Domain\Vehicles\Models\VehicleBrand;
use App\Http\Requests\Vehicles\StoreVehiclePrivacyRequest;
use App\Http\Requests\Vehicles\UpdateVehicleRequest;
use App\Services\MaintenanceReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VehicleController extends Controller
{
    /**
     * Listado general de vehículos de la empresa.
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();

        $query = Vehicle::query()
            ->withConsistentOwnership()
            ->with(['customer', 'company']);

        if ($currentUser->role === 'company_admin') {
            $query->where('company_id', $currentUser->company_id);
        }

        $vehicles = $query->latest('id')->paginate(10);

        return view('vehicles.index', compact('vehicles'));
    }

    /**
     * Formulario para registrar un vehículo en el contexto de un cliente específico.
     */
    public function createForCustomer(Request $request, Customer $customer): View
    {
        $this->authorizeCustomerAccess($request->user(), $customer);

        $vehicle = new Vehicle([
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'current_mileage' => 0,
        ]);
        $brands = VehicleBrand::query()->orderBy('name')->get(['name', 'vehicle_type']);
        $vehicleTypes = VehicleType::options();

        return view('vehicles.create', compact('vehicle', 'customer', 'brands', 'vehicleTypes'));
    }

    /**
     * Almacena el vehículo con estricta política de privacidad por placa.
     * Si la placa ya existe asignada a un cliente, se bloquea el registro con mensaje neutro.
     */
    public function store(StoreVehiclePrivacyRequest $request): RedirectResponse
    {
        $currentUser = $request->user();
        $data = $request->validated();

        $customer = Customer::findOrFail($data['customer_id']);
        $this->authorizeCustomerAccess($currentUser, $customer);

        // Validación estricta: La empresa del vehículo DEBE ser siempre la misma del cliente
        $companyId = $customer->company_id;
        $data['company_id'] = $companyId;
        $plate = strtoupper(trim($data['plate']));
        $data['plate'] = $plate;

        // Regla de privacidad: Verificar si la placa ya se encuentra asignada
        $existingVehicle = Vehicle::whereHas('customer', fn ($query) => $query->where('company_id', $companyId))
            ->where('plate', $plate)
            ->first();

        if ($existingVehicle) {
            return back()
                ->withErrors([
                    'plate' => 'Este vehículo ya se encuentra registrado en la plataforma. Por seguridad y privacidad, no puede ser reasignado directamente ni es posible consultar su información por placa. El usuario o cliente que actualmente lo tiene asignado debe darlo de baja desde su cuenta; al realizar la baja, todo el historial previo se eliminará permanentemente para permitir un nuevo registro limpio.',
                ])
                ->withInput();
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('vehicles/'.$companyId, 'public');
        }

        $vehicle = Vehicle::create($data);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', "Vehículo con placa '{$vehicle->plate}' registrado exitosamente para {$customer->full_name}.");
    }

    /**
     * Muestra el detalle del vehículo.
     */
    public function show(Request $request, Vehicle $vehicle): View
    {
        $this->authorizeVehicleAccess($request->user(), $vehicle);

        $vehicle->load(['customer', 'company', 'maintenanceOrders' => fn ($q) => $q->latest('service_date')]);

        $reminderService = app(MaintenanceReminderService::class);
        $nextMaintenance = $reminderService->getVehicleReminder($vehicle);

        return view('vehicles.show', compact('vehicle', 'nextMaintenance'));
    }

    /**
     * Formulario para editar un vehículo.
     */
    public function edit(Request $request, Vehicle $vehicle): View
    {
        $this->authorizeVehicleAccess($request->user(), $vehicle);

        $vehicle->load('customer');
        $brands = VehicleBrand::query()->orderBy('name')->get(['name', 'vehicle_type']);
        $vehicleTypes = VehicleType::options();

        return view('vehicles.edit', compact('vehicle', 'brands', 'vehicleTypes'));
    }

    /**
     * Actualiza los datos del vehículo.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicleAccess($request->user(), $vehicle);

        $data = $request->validated();

        // Evitar manipulación de llaves foráneas en actualización
        unset($data['customer_id']);

        // Asegurar sin excepción que la empresa del vehículo coincida con la del cliente asociado
        $customer = $vehicle->customer ?: Customer::findOrFail($vehicle->customer_id);
        $data['company_id'] = $customer->company_id;

        if ($request->hasFile('photo')) {
            if ($vehicle->photo_path && Storage::disk('public')->exists($vehicle->photo_path)) {
                Storage::disk('public')->delete($vehicle->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('vehicles/'.$customer->company_id, 'public');
        }

        $vehicle->update($data);

        return redirect()
            ->route('customers.show', $vehicle->customer_id)
            ->with('success', "Vehículo '{$vehicle->plate}' ({$vehicle->make} {$vehicle->model}) actualizado correctamente.");
    }

    /**
     * Da de baja un vehículo por parte del propietario/empresa actual.
     * En esta versión de privacidad, la baja elimina por completo el historial operativo
     * y las órdenes asociadas para permitir que la placa quede libre sin fuga de datos.
     */
    public function decommission(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicleAccess($request->user(), $vehicle);

        $plate = $vehicle->plate;
        $customerId = $vehicle->customer_id;

        DB::transaction(function () use ($vehicle) {
            // Eliminar evidencias fotográficas de sus órdenes
            $orderIds = $vehicle->maintenanceOrders()->pluck('id');
            if ($orderIds->isNotEmpty()) {
                MaintenanceOrderPhoto::whereIn('maintenance_order_id', $orderIds)->delete();
                MaintenanceOrder::whereIn('id', $orderIds)->forceDelete();
            }

            // Eliminar definitivamente el vehículo para que la placa quede libre para nuevo registro limpio
            $vehicle->forceDelete();
        });

        return redirect()
            ->route('customers.show', $customerId)
            ->with('info', "El vehículo con placa '{$plate}' fue dado de baja exitosamente. Todo su historial previo fue eliminado para garantizar privacidad y la placa ha quedado liberada para un nuevo registro.");
    }

    /**
     * Eliminación lógica del vehículo.
     */
    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicleAccess($request->user(), $vehicle);

        $plate = $vehicle->plate;
        $customerId = $vehicle->customer_id;
        $vehicle->delete();

        return redirect()
            ->route('customers.show', $customerId)
            ->with('success', "Vehículo con placa '{$plate}' eliminado.");
    }

    /**
     * Valida acceso por cliente.
     */
    protected function authorizeCustomerAccess($currentUser, Customer $customer): void
    {
        if ($currentUser->role === 'company_admin' && (int) $customer->company_id !== (int) $currentUser->company_id) {
            abort(403, 'No tienes autorización para gestionar vehículos de otra empresa.');
        }
    }

    /**
     * Valida acceso por vehículo.
     */
    protected function authorizeVehicleAccess($currentUser, Vehicle $vehicle): void
    {
        abort_unless(Vehicle::withConsistentOwnership()->whereKey($vehicle->id)->exists(), 403, 'El vehículo no está disponible en esta empresa.');

        if ($currentUser->role === 'company_admin' && (int) $vehicle->company_id !== (int) $currentUser->company_id) {
            abort(403, 'No tienes autorización para acceder a vehículos de otra empresa.');
        }
    }
}

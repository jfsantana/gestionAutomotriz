<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Customer;
use App\Domain\Vehicles\Models\Vehicle;
use App\Http\Requests\Vehicles\StoreVehicleRequest;
use App\Http\Requests\Vehicles\UpdateVehicleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('vehicles.create', compact('vehicle', 'customer'));
    }

    /**
     * Almacena el vehículo garantizando company_id y customer_id correspondientes.
     */
    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $currentUser = $request->user();
        $data = $request->validated();

        $customer = Customer::findOrFail($data['customer_id']);
        $this->authorizeCustomerAccess($currentUser, $customer);

        // Forzar company_id de la empresa del cliente/usuario
        $data['company_id'] = $customer->company_id;

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

        $vehicle->load(['customer', 'company']);

        return view('vehicles.show', compact('vehicle'));
    }

    /**
     * Formulario para editar un vehículo.
     */
    public function edit(Request $request, Vehicle $vehicle): View
    {
        $this->authorizeVehicleAccess($request->user(), $vehicle);

        $vehicle->load('customer');

        return view('vehicles.edit', compact('vehicle'));
    }

    /**
     * Actualiza los datos del vehículo.
     */
    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeVehicleAccess($request->user(), $vehicle);

        $data = $request->validated();

        // Evitar manipulación de llaves foráneas en actualización
        unset($data['company_id'], $data['customer_id']);

        $vehicle->update($data);

        return redirect()
            ->route('customers.show', $vehicle->customer_id)
            ->with('success', "Vehículo '{$vehicle->plate}' ({$vehicle->make} {$vehicle->model}) actualizado correctamente.");
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
        if ($currentUser->role === 'company_admin' && (int)$customer->company_id !== (int)$currentUser->company_id) {
            abort(403, 'No tienes autorización para gestionar vehículos de otra empresa.');
        }
    }

    /**
     * Valida acceso por vehículo.
     */
    protected function authorizeVehicleAccess($currentUser, Vehicle $vehicle): void
    {
        if ($currentUser->role === 'company_admin' && (int)$vehicle->company_id !== (int)$currentUser->company_id) {
            abort(403, 'No tienes autorización para acceder a vehículos de otra empresa.');
        }
    }
}

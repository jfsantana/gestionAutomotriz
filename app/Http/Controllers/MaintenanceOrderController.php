<?php

namespace App\Http\Controllers;

use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Maintenance\Models\MaintenanceOrderPhoto;
use App\Domain\Maintenance\Models\Service;
use App\Domain\Vehicles\Models\Vehicle;
use App\Http\Requests\MaintenanceOrders\StoreMaintenanceOrderRequest;
use App\Http\Requests\MaintenanceOrders\UpdateMaintenanceOrderRequest;
use App\Models\User;
use App\Services\MaintenanceOrderNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaintenanceOrderController extends Controller
{
    public function __construct(
        protected MaintenanceOrderNotificationService $notificationService,
    ) {}

    public function index(Request $request): View
    {
        $currentUser = $request->user();

        $query = MaintenanceOrder::query()
            ->withConsistentOwnership()
            ->with(['customer', 'vehicle', 'mechanic', 'company', 'registeredByUser']);

        if (in_array($currentUser->role, ['company_admin', 'mechanic'], true)) {
            $query->where('company_id', $currentUser->company_id);
        }

        $orders = $query->latest('service_date')->paginate(10);

        return view('maintenance_orders.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        $currentUser = $request->user();
        $companyId = $currentUser->company_id;

        $preselectedVehicleId = $request->input('vehicle_id');
        $preselectedVehicle = $preselectedVehicleId ? Vehicle::withConsistentOwnership()->findOrFail($preselectedVehicleId) : null;

        if ($preselectedVehicle && $currentUser->role !== 'super_admin' && (int) $preselectedVehicle->company_id !== (int) $companyId) {
            abort(403);
        }

        $customers = Customer::query()
            ->when($currentUser->role !== 'super_admin', fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $vehicles = Vehicle::query()
            ->withConsistentOwnership()
            ->when($currentUser->role !== 'super_admin', fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('plate')
            ->get(['id', 'customer_id', 'plate', 'make', 'model', 'current_mileage']);

        $services = Service::query()
            ->when($currentUser->role !== 'super_admin', fn ($q) => $q->where('company_id', $companyId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'maintenance_type', 'default_price', 'default_interval_days', 'default_interval_mileage', 'description']);

        $mechanics = User::query()
            ->when($currentUser->role !== 'super_admin', fn ($q) => $q->where('company_id', $companyId))
            ->where('role', 'mechanic')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        $order = new MaintenanceOrder([
            'service_date' => now()->toDateString(),
            'current_mileage' => $preselectedVehicle?->current_mileage ?? 0,
            'status' => 'open',
            'maintenance_type' => 'preventive',
            'customer_id' => $preselectedVehicle?->customer_id,
            'vehicle_id' => $preselectedVehicle?->id,
        ]);

        return view('maintenance_orders.create', compact('order', 'customers', 'vehicles', 'services', 'mechanics', 'preselectedVehicle'));
    }

    public function store(StoreMaintenanceOrderRequest $request): RedirectResponse
    {
        $currentUser = $request->user();
        $data = $request->validated();

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $customer = Customer::findOrFail($data['customer_id']);

        // Aislamiento y coherencia cliente - vehículo
        if ((int) $vehicle->customer_id !== (int) $customer->id) {
            return back()->withErrors(['vehicle_id' => 'El vehículo seleccionado no pertenece al cliente especificado.'])->withInput();
        }

        $companyId = $customer->company_id;

        if ((int) $vehicle->company_id !== (int) $companyId
            || ($currentUser->role !== 'super_admin' && (int) $companyId !== (int) $currentUser->company_id)) {
            abort(403, 'El vehículo pertenece a otra empresa.');
        }

        $data['company_id'] = $companyId;
        $data['order_number'] = MaintenanceOrder::generateOrderNumber($companyId);

        // Snapshot de servicio
        if (! empty($data['service_id'])) {
            $service = Service::find($data['service_id']);
            if ($service) {
                $data['service_name_snapshot'] = (! empty($data['service_name_snapshot'])) ? $data['service_name_snapshot'] : $service->name;
                $data['service_description_snapshot'] = $data['service_description_snapshot'] ?? $service->description;
                $data['maintenance_type'] = (! empty($data['maintenance_type'])) ? $data['maintenance_type'] : $service->maintenance_type;
            }
        }

        if (empty($data['service_name_snapshot'])) {
            $data['service_name_snapshot'] = 'Mantenimiento General';
        }

        if (! array_key_exists('service_description_snapshot', $data)) {
            $data['service_description_snapshot'] = null;
        }

        // Tiempos según estado
        if ($data['status'] === 'completed') {
            $data['completed_at'] = now();
        } elseif ($data['status'] === 'cancelled') {
            $data['cancelled_at'] = now();
        }

        DB::transaction(function () use (&$order, $data, $vehicle, $request) {
            $order = MaintenanceOrder::create($data);

            // Actualizar kilometraje del vehículo si es mayor
            if ($order->current_mileage > $vehicle->current_mileage) {
                $vehicle->update(['current_mileage' => $order->current_mileage]);
            }

            // Guardar fotos de evidencia si se adjuntaron
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('maintenance_photos/'.$order->company_id, 'public');
                    MaintenanceOrderPhoto::create([
                        'maintenance_order_id' => $order->id,
                        'file_path' => $path,
                        'original_name' => $photo->getClientOriginalName(),
                        'mime_type' => $photo->getClientMimeType(),
                        'file_size' => $photo->getSize(),
                    ]);
                }
            }
        });

        $warning = $this->notificationService->send($order);

        $response = redirect()
            ->route('maintenance-orders.show', $order)
            ->with('success', "Orden de mantenimiento {$order->order_number} registrada correctamente.");

        return $warning ? $response->with('warning', $warning) : $response;
    }

    public function show(Request $request, MaintenanceOrder $maintenance_order): View
    {
        $this->authorizeOrderAccess($request->user(), $maintenance_order);

        $maintenance_order->load(['customer', 'vehicle', 'service', 'mechanic', 'photos', 'company', 'registeredByUser']);

        return view('maintenance_orders.show', ['order' => $maintenance_order]);
    }

    public function edit(Request $request, MaintenanceOrder $maintenance_order): View
    {
        $this->authorizeOrderAccess($request->user(), $maintenance_order);

        $currentUser = $request->user();
        $companyId = $maintenance_order->company_id;

        $mechanics = User::query()
            ->where('company_id', $companyId)
            ->where('role', 'mechanic')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        return view('maintenance_orders.edit', [
            'order' => $maintenance_order,
            'mechanics' => $mechanics,
        ]);
    }

    public function update(UpdateMaintenanceOrderRequest $request, MaintenanceOrder $maintenance_order): RedirectResponse
    {
        $this->authorizeOrderAccess($request->user(), $maintenance_order);

        $data = $request->validated();
        $previousStatus = $maintenance_order->status;

        if ($data['status'] === 'completed' && ! $maintenance_order->completed_at) {
            $data['completed_at'] = now();
        } elseif ($data['status'] === 'cancelled' && ! $maintenance_order->cancelled_at) {
            $data['cancelled_at'] = now();
        }

        DB::transaction(function () use ($maintenance_order, $data, $request) {
            $maintenance_order->update($data);

            // Actualizar odómetro del vehículo
            if ($maintenance_order->vehicle && $maintenance_order->current_mileage > $maintenance_order->vehicle->current_mileage) {
                $maintenance_order->vehicle->update(['current_mileage' => $maintenance_order->current_mileage]);
            }

            // Fotos adicionales
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('maintenance_photos/'.$maintenance_order->company_id, 'public');
                    MaintenanceOrderPhoto::create([
                        'maintenance_order_id' => $maintenance_order->id,
                        'file_path' => $path,
                        'original_name' => $photo->getClientOriginalName(),
                        'mime_type' => $photo->getClientMimeType(),
                        'file_size' => $photo->getSize(),
                    ]);
                }
            }
        });

        $warning = $maintenance_order->wasChanged('status')
            ? $this->notificationService->send($maintenance_order, $previousStatus)
            : null;

        $response = redirect()
            ->route('maintenance-orders.show', $maintenance_order)
            ->with('success', "Orden {$maintenance_order->order_number} actualizada correctamente.");

        return $warning ? $response->with('warning', $warning) : $response;
    }

    protected function authorizeOrderAccess($currentUser, MaintenanceOrder $order): void
    {
        abort_unless(
            in_array($currentUser->role, ['super_admin', 'company_admin', 'mechanic'], true)
            && ($currentUser->role === 'super_admin' || ($currentUser->company_id && (int) $order->company_id === (int) $currentUser->company_id))
            && MaintenanceOrder::withConsistentOwnership()->whereKey($order->id)->exists(),
            403,
            'No tienes autorización para acceder a esta orden.'
        );
    }
}

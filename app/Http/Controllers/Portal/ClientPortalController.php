<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Vehicles\Models\Vehicle;
use App\Http\Controllers\Controller;
use App\Services\MaintenanceReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ClientPortalController extends Controller
{
    public function __construct(
        protected MaintenanceReminderService $reminderService
    ) {}

    /**
     * 1. Panel de Inicio del Cliente: Resumen, alertas y últimos mantenimientos.
     */
    public function dashboard(Request $request): View
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 404, 'No se encontró la ficha del cliente asociada a tu cuenta.');

        $vehicles = $customer->vehicles()->withCount('maintenanceOrders')->get();

        // Próximos mantenimientos para sus vehículos
        $upcomingReminders = [];
        foreach ($vehicles as $veh) {
            $rem = $this->reminderService->getVehicleReminder($veh);
            if ($rem && in_array($rem['evaluation']['status'], ['overdue', 'due_soon'], true)) {
                $upcomingReminders[] = [
                    'vehicle' => $veh,
                    'reminder' => $rem,
                ];
            }
        }

        // Últimos 5 mantenimientos de sus vehículos
        $recentOrders = MaintenanceOrder::where('customer_id', $customer->id)
            ->with(['vehicle'])
            ->latest('service_date')
            ->take(5)
            ->get();

        return view('portal.dashboard', compact('customer', 'vehicles', 'upcomingReminders', 'recentOrders'));
    }

    /**
     * 2. Mis Vehículos: Listado de vehículos vinculados a su cuenta.
     */
    public function vehicles(Request $request): View
    {
        $customer = $request->user()->customer;
        $vehicles = $customer->vehicles()->withCount('maintenanceOrders')->latest('id')->get();

        return view('portal.vehicles.index', compact('customer', 'vehicles'));
    }

    /**
     * Detalle de un vehículo propio con su historial.
     */
    public function vehicleShow(Request $request, Vehicle $vehicle): View
    {
        $this->authorizeCustomerVehicle($request->user(), $vehicle);

        $vehicle->load(['maintenanceOrders' => fn ($q) => $q->latest('service_date')]);
        $reminder = $this->reminderService->getVehicleReminder($vehicle);

        return view('portal.vehicles.show', compact('vehicle', 'reminder'));
    }

    /**
     * Baja de vehículo por el cliente: Desvincula y elimina el historial para nuevo registro limpio.
     */
    public function decommissionVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeCustomerVehicle($request->user(), $vehicle);

        $plate = $vehicle->plate;

        // Desvinculación y baja lógica del vehículo
        $vehicle->update(['customer_status' => 'DECOMMISSIONED']);

        // Se marcan/limpian las órdenes operativas para que el futuro comprador no herede datos privados
        $vehicle->maintenanceOrders()->delete();
        $vehicle->delete(); // Soft delete del vehículo

        return redirect()->route('portal.vehicles.index')
            ->with('info', "El vehículo con placa '{$plate}' fue dado de baja de tu cuenta. Ha quedado liberado de tu perfil sin transferir tu historial.");
    }

    /**
     * 3. Historial de Mantenimiento: Separando órdenes de taller de registros personales.
     */
    public function maintenanceHistory(Request $request): View
    {
        $customer = $request->user()->customer;
        $tab = $request->input('tab', 'all'); // all, workshop, personal

        $query = MaintenanceOrder::where('customer_id', $customer->id)
            ->with(['vehicle', 'service']);

        if ($tab === 'workshop') {
            $query->where('origin', 'workshop');
        } elseif ($tab === 'personal') {
            $query->where('origin', 'customer');
        }

        $orders = $query->latest('service_date')->paginate(10);

        return view('portal.maintenance.index', compact('customer', 'orders', 'tab'));
    }

    /**
     * 4. Registrar Mantenimiento Propio / Personal.
     */
    public function createPersonalMaintenance(Request $request): View
    {
        $customer = $request->user()->customer;
        $vehicles = $customer->vehicles()->get(['id', 'plate', 'make', 'model', 'current_mileage']);

        return view('portal.maintenance.create_personal', compact('customer', 'vehicles'));
    }

    public function storePersonalMaintenance(Request $request): RedirectResponse
    {
        $customer = $request->user()->customer;

        $data = $request->validate([
            'vehicle_id' => ['required', 'integer'],
            'service_name_snapshot' => ['required', 'string', 'max:150'],
            'maintenance_type' => ['required', 'in:preventive,corrective'],
            'service_date' => ['required', 'date'],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        $this->authorizeCustomerVehicle($request->user(), $vehicle);

        $order = MaintenanceOrder::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'registered_by_user_id' => $request->user()->id,
            'service_name_snapshot' => $data['service_name_snapshot'],
            'maintenance_type' => $data['maintenance_type'],
            'service_date' => $data['service_date'],
            'current_mileage' => $data['current_mileage'],
            'cost' => $data['cost'],
            'customer_notes' => $data['customer_notes'],
            'status' => 'completed',
            'origin' => 'customer',       // Origen: Cliente
            'is_verified' => false,        // No verificado por taller
            'completed_at' => now(),
        ]);

        if ($data['current_mileage'] > $vehicle->current_mileage) {
            $vehicle->update(['current_mileage' => $data['current_mileage']]);
        }

        return redirect()->route('portal.maintenance.index')
            ->with('success', 'Registro personal de mantenimiento guardado exitosamente.');
    }

    /**
     * 5. Mi Cuenta y Seguridad.
     */
    public function account(Request $request): View
    {
        $user = $request->user();
        $customer = $user->customer;

        return view('portal.account.index', compact('user', 'customer'));
    }

    public function updateAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update(['name' => $data['name']]);
        $customer?->update([
            'full_name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ]);

        return back()->with('success', 'Datos personales actualizados correctamente.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }

    /**
     * Baja voluntaria de la cuenta del cliente (Soft delete / Deactivación).
     */
    public function deactivateAccount(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->update([
            'account_status' => 'DEACTIVATED',
            'is_active' => false,
        ]);

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Tu cuenta ha sido dada de baja del sistema.');
    }

    protected function authorizeCustomerVehicle($user, Vehicle $vehicle): void
    {
        if ((int)$vehicle->customer_id !== (int)$user->customer_id) {
            abort(403, 'No tienes autorización para consultar este vehículo.');
        }
    }
}

<?php

namespace App\Http\Controllers\Portal;

use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Vehicles\Enums\VehicleType;
use App\Domain\Vehicles\Models\Vehicle;
use App\Domain\Vehicles\Models\VehicleBrand;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MaintenanceReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClientPortalController extends Controller
{
    public function __construct(
        protected MaintenanceReminderService $reminderService
    ) {}

    /**
     * 1. Panel de Inicio del Cliente: Resumen, alertas y últimos mantenimientos.
     */
    public function dashboard(Request $request): View|RedirectResponse
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 404, 'No se encontró la ficha del cliente asociada a tu cuenta.');
        $accessRedirect = $this->redirectIfFleetAccessExpired($customer);
        if ($accessRedirect) {
            return $accessRedirect;
        }

        $vehicleQuery = $customer->vehicles()->withCount('maintenanceOrders')->latest('id');
        if (! $customer->isFleetPremiumActive()) {
            $vehicleQuery->limit(3);
        }
        $vehicles = $vehicleQuery->get();

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
            ->withConsistentOwnership()
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
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
        $user = $request->user();
        $customer = $user->customer;
        abort_unless($customer, 404, 'No se encontró la ficha del cliente asociada a tu cuenta.');

        $vehicleQuery = $customer->vehicles()
            ->withCount('maintenanceOrders')
            ->latest('id');
        $totalVehiclesCount = $customer->vehicles()->count();

        if ($user->role === 'customer' && ! $customer->isFleetPremiumActive()) {
            $vehicleQuery->limit($customer->hasFleetTrialActive() ? 3 : 0);
        }

        $vehicles = $vehicleQuery->get();
        $hidden_vehicles_count = max(0, $totalVehiclesCount - $vehicles->count());
        $canAddVehicle = $customer->hasFleetPortalAccess()
            && ($customer->isFleetPremiumActive()
                || $totalVehiclesCount < ($customer->vehicle_limit ?? 3));
        $fleetPayment = $hidden_vehicles_count > 0
            ? $customer->payments()->first()
            : null;

        return view('portal.vehicles.index', compact(
            'customer',
            'vehicles',
            'hidden_vehicles_count',
            'canAddVehicle',
            'fleetPayment',
        ));
    }

    public function createVehicle(Request $request): View|RedirectResponse
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 404);
        $accessRedirect = $this->redirectIfFleetAccessExpired($customer);
        if ($accessRedirect) {
            return $accessRedirect;
        }

        if (! $customer->isFleetPremiumActive()
            && $customer->vehicles()->count() >= ($customer->vehicle_limit ?? 3)) {
            return redirect()->route('portal.vehicles.index')
                ->with('error', 'Alcanzaste el límite de vehículos de tu plan actual.');
        }

        $brands = VehicleBrand::query()->orderBy('vehicle_type')->orderBy('name')->get(['name', 'vehicle_type']);
        $vehicleTypes = VehicleType::options();

        return view('portal.vehicles.create', compact('customer', 'brands', 'vehicleTypes'));
    }

    public function storeVehicle(Request $request): RedirectResponse
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 404);
        $accessRedirect = $this->redirectIfFleetAccessExpired($customer);
        if ($accessRedirect) {
            return $accessRedirect;
        }

        $request->merge(['plate' => mb_strtoupper(trim((string) $request->input('plate')))]);
        $data = $request->validate([
            'plate' => ['required', 'string', 'max:30'],
            'vin' => ['nullable', 'string', 'max:50'],
            'vehicle_type' => ['required', Rule::in(array_keys(VehicleType::options()))],
            'make' => [
                'required',
                'string',
                'max:80',
                Rule::exists('vehicle_brands', 'name')
                    ->where(fn ($query) => $query->where('vehicle_type', $request->input('vehicle_type'))),
            ],
            'model' => ['required', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 2)],
            'color' => ['nullable', 'string', 'max:50'],
            'current_mileage' => ['required', 'integer', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:5120'], // Máximo 5MB
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')
                ->store('vehicles/'.($customer->company_id ?? 'independientes'), 'public');
        }

        $vehicle = DB::transaction(function () use ($customer, $data): Vehicle {
            $lockedCustomer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $activeVehiclesCount = $lockedCustomer->vehicles()->count();

            if (! $lockedCustomer->isFleetPremiumActive()
                && $activeVehiclesCount >= ($lockedCustomer->vehicle_limit ?? 3)) {
                throw ValidationException::withMessages([
                    'vehicle_limit' => 'Alcanzaste el límite de vehículos de tu plan actual.',
                ]);
            }

            if (Vehicle::withTrashed()->where('plate', $data['plate'])->exists()) {
                throw ValidationException::withMessages([
                    'plate' => 'Este vehículo ya se encuentra registrado en la plataforma. Verifica los datos antes de continuar.',
                ]);
            }

            return Vehicle::create([
                ...$data,
                'company_id' => $lockedCustomer->company_id,
                'customer_id' => $lockedCustomer->id,
            ]);
        });

        return redirect()->route('portal.vehicles.show', $vehicle)
            ->with('success', 'Vehículo registrado correctamente en tu cuenta.');
    }

    /**
     * Detalle de un vehículo propio con su historial.
     */
    public function vehicleShow(Request $request, Vehicle $vehicle): View|RedirectResponse
    {
        $accessRedirect = $this->redirectIfFleetAccessExpired($request->user()->customer);
        if ($accessRedirect) {
            return $accessRedirect;
        }

        $this->authorizeCustomerVehicle($request->user(), $vehicle);

        $vehicle->load([
            'maintenanceOrders' => fn ($query) => $query
                ->where('customer_id', $vehicle->customer_id)
                ->latest('service_date'),
        ]);
        $reminder = $this->reminderService->getVehicleReminder($vehicle);

        return view('portal.vehicles.show', compact('vehicle', 'reminder'));
    }

    /**
     * Baja de vehículo por el cliente: Desvincula y elimina el historial para nuevo registro limpio.
     */
    public function decommissionVehicle(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $accessRedirect = $this->redirectIfFleetAccessExpired($request->user()->customer);
        if ($accessRedirect) {
            return $accessRedirect;
        }

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
    public function maintenanceHistory(Request $request): View|RedirectResponse
    {
        $customer = $request->user()->customer;
        $accessRedirect = $this->redirectIfFleetAccessExpired($customer);
        if ($accessRedirect) {
            return $accessRedirect;
        }

        $tab = $request->input('tab', 'all'); // all, workshop, personal

        $query = MaintenanceOrder::where('customer_id', $customer->id)
            ->withConsistentOwnership()
            ->whereIn('vehicle_id', $this->visibleVehicleIds($customer))
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
    public function createPersonalMaintenance(Request $request): View|RedirectResponse
    {
        $customer = $request->user()->customer;
        $accessRedirect = $this->redirectIfFleetAccessExpired($customer);
        if ($accessRedirect) {
            return $accessRedirect;
        }

        $vehicles = $customer->vehicles()
            ->whereIn('id', $this->visibleVehicleIds($customer))
            ->get(['id', 'plate', 'make', 'model', 'current_mileage']);

        return view('portal.maintenance.create_personal', compact('customer', 'vehicles'));
    }

    public function storePersonalMaintenance(Request $request): RedirectResponse
    {
        $customer = $request->user()->customer;
        $accessRedirect = $this->redirectIfFleetAccessExpired($customer);
        if ($accessRedirect) {
            return $accessRedirect;
        }

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
            'company_id' => null,
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

    protected function authorizeCustomerVehicle(User $user, Vehicle $vehicle): void
    {
        if ((int) $vehicle->customer_id !== (int) $user->customer_id
            || ! Vehicle::withConsistentOwnership()->whereKey($vehicle->id)->exists()) {
            abort(403, 'No tienes autorización para consultar este vehículo.');
        }

        $customer = $user->customer;
        if (
            ! $customer->isFleetPremiumActive()
            && $customer->hasFleetTrialActive()
            && ! $this->visibleVehicleIds($customer)->contains((int) $vehicle->id)
        ) {
            abort(404);
        }
    }

    /**
     * @return Collection<int, int>
     */
    private function visibleVehicleIds(Customer $customer): Collection
    {
        $query = $customer->vehicles()->latest('id');
        if (! $customer->isFleetPremiumActive()) {
            $query->limit(3);
        }

        return $query->pluck('id');
    }

    private function redirectIfFleetAccessExpired(?Customer $customer): ?RedirectResponse
    {
        if ($customer && ! $customer->hasFleetPortalAccess()) {
            return redirect()
                ->route('portal.vehicles.index')
                ->with('info', 'Tu período gratuito de dos meses terminó. Registra una solicitud de pago para recuperar el acceso a tus vehículos, historiales y servicios.');
        }

        return null;
    }
}

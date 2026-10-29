<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Maintenance\Models\MaintenanceOrderPhoto;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PremiumCustomerDeactivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_cannot_decommission_vehicle_of_active_fleet_customer(): void
    {
        $context = $this->createCompanyContext();
        $customer = $this->createCustomer($context['company'], true);
        $vehicle = $this->createVehicle($customer);
        $order = $this->createMaintenanceOrder($customer, $vehicle);

        $this->actingAs($context['companyAdmin'])
            ->post(route('vehicles.decommission', $vehicle))
            ->assertRedirect()
            ->assertSessionHas('error', 'Este cliente tiene una suscripción de flota vigente. El taller no puede dar de baja su vehículo ni borrar su historial; solo el cliente puede hacerlo desde su portal.');

        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseHas('maintenance_orders', ['id' => $order->id]);
    }

    public function test_workshop_cannot_soft_delete_vehicle_of_active_fleet_customer(): void
    {
        $context = $this->createCompanyContext();
        $customer = $this->createCustomer($context['company'], true);
        $vehicle = $this->createVehicle($customer);

        $this->actingAs($context['companyAdmin'])
            ->delete(route('vehicles.destroy', $vehicle))
            ->assertRedirect()
            ->assertSessionHas('error', 'Este cliente tiene una suscripción de flota vigente. El taller no puede eliminar su vehículo; solo el cliente puede hacerlo desde su portal.');

        $this->assertDatabaseHas('vehicles', [
            'id' => $vehicle->id,
            'deleted_at' => null,
        ]);
    }

    public function test_workshop_cannot_delete_active_fleet_customer_or_portal_access(): void
    {
        $context = $this->createCompanyContext();
        $customer = $this->createCustomer($context['company'], true);
        $vehicle = $this->createVehicle($customer);
        $order = $this->createMaintenanceOrder($customer, $vehicle);
        $portalUser = $this->createPortalUser($context['company'], $customer);

        $this->actingAs($context['companyAdmin'])
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect()
            ->assertSessionHas('error', 'Este cliente tiene una suscripción de flota vigente. El taller no puede eliminar su cuenta, sus vehículos ni su historial; solo el cliente puede dar de baja su cuenta desde el portal.');

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('maintenance_orders', ['id' => $order->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('users', ['id' => $portalUser->id, 'is_active' => true]);
    }

    public function test_workshop_sees_owner_only_deactivation_notice_for_premium_customer_and_vehicle(): void
    {
        $context = $this->createCompanyContext();
        $customer = $this->createCustomer($context['company'], true);
        $vehicle = $this->createVehicle($customer);

        $this->actingAs($context['companyAdmin'])
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Solo el cliente puede dar de baja su cuenta desde su portal.');

        $this->actingAs($context['companyAdmin'])
            ->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertSee('Solo el cliente puede darlo de baja desde su portal.');
    }

    public function test_workshop_deleting_non_premium_customer_removes_vehicles_history_and_portal_access(): void
    {
        Storage::fake('public');
        $context = $this->createCompanyContext();
        $customer = $this->createCustomer($context['company']);
        $vehicle = $this->createVehicle($customer, 'vehicles/client-photo.jpg');
        $order = $this->createMaintenanceOrder($customer, $vehicle);
        $photoPath = 'maintenance_photos/order-photo.jpg';
        Storage::disk('public')->put($photoPath, 'photo');
        MaintenanceOrderPhoto::create([
            'maintenance_order_id' => $order->id,
            'file_path' => $photoPath,
        ]);
        $portalUser = $this->createPortalUser($context['company'], $customer);

        $this->actingAs($context['companyAdmin'])
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertDatabaseMissing('vehicles', ['customer_id' => $customer->id]);
        $this->assertDatabaseMissing('maintenance_orders', ['customer_id' => $customer->id]);
        $this->assertDatabaseMissing('maintenance_order_photos', ['maintenance_order_id' => $order->id]);
        $this->assertDatabaseHas('users', [
            'id' => $portalUser->id,
            'is_active' => false,
            'account_status' => 'DEACTIVATED',
        ]);
        Storage::disk('public')->assertMissing('vehicles/client-photo.jpg');
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_active_fleet_customer_can_decommission_own_vehicle_from_portal(): void
    {
        $context = $this->createCompanyContext();
        $customer = $this->createCustomer($context['company'], true);
        $vehicle = $this->createVehicle($customer);
        $portalUser = $this->createPortalUser($context['company'], $customer);

        $this->actingAs($portalUser)
            ->post(route('portal.vehicles.decommission', $vehicle))
            ->assertRedirect(route('portal.vehicles.index'))
            ->assertSessionHas('info');

        // La baja elimina definitivamente el vehículo para liberar la placa para un nuevo registro
        $this->assertDatabaseMissing('vehicles', ['id' => $vehicle->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'deleted_at' => null]);
    }

    /**
     * @return array{company: Company, companyAdmin: User}
     */
    private function createCompanyContext(): array
    {
        $company = Company::create([
            'name' => 'Taller Flota',
            'slug' => 'taller-flota',
            'status' => 'active',
        ]);
        $companyAdmin = User::factory()->create([
            'role' => 'company_admin',
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Plan Flota',
            'slug' => 'plan-flota',
            'monthly_price' => 50,
            'yearly_price' => 500,
            'is_active' => true,
        ]);
        CompanySubscription::create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => today(),
            'ends_at' => today()->addMonth(),
        ]);

        return compact('company', 'companyAdmin');
    }

    private function createCustomer(Company $company, bool $isPremium = false): Customer
    {
        return Customer::create([
            'company_id' => $company->id,
            'full_name' => 'Propietario de Flota',
            'is_fleet_premium' => $isPremium,
            'vehicle_limit' => $isPremium ? null : 3,
            'fleet_expires_at' => $isPremium ? now()->addMonth() : null,
        ]);
    }

    private function createVehicle(Customer $customer, ?string $photoPath = null): Vehicle
    {
        if ($photoPath) {
            Storage::disk('public')->put($photoPath, 'photo');
        }

        return Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'TEST-'.$customer->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'photo_path' => $photoPath,
        ]);
    }

    private function createMaintenanceOrder(Customer $customer, Vehicle $vehicle): MaintenanceOrder
    {
        return MaintenanceOrder::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'service_name_snapshot' => 'Mantenimiento de prueba',
            'service_date' => today(),
            'current_mileage' => 1000,
            'status' => 'completed',
            'maintenance_type' => 'preventive',
        ]);
    }

    private function createPortalUser(Company $company, Customer $customer): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'account_status' => 'ACTIVE',
            'is_active' => true,
        ]);
    }
}

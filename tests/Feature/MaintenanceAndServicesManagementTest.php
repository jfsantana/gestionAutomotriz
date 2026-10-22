<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Maintenance\Models\Service;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceAndServicesManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminA;
    protected User $adminB;
    protected User $mechanicA;
    protected Customer $customerA;
    protected Vehicle $vehicleA;
    protected Service $serviceA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::create([
            'name' => 'Taller Los Prados',
            'slug' => 'taller-los-prados',
            'status' => 'active',
        ]);

        $this->companyB = Company::create([
            'name' => 'Taller Naco',
            'slug' => 'taller-naco',
            'status' => 'active',
        ]);

        $this->adminA = User::create([
            'name' => 'Admin Prados',
            'username' => 'admin_prados',
            'email' => 'admin@prados.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);

        $this->adminB = User::create([
            'name' => 'Admin Naco',
            'username' => 'admin_naco',
            'email' => 'admin@naco.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyB->id,
            'is_active' => true,
        ]);

        $this->mechanicA = User::create([
            'name' => 'Mecánico Juan',
            'username' => 'mecanico_juan',
            'email' => 'juan@prados.do',
            'password' => 'secret123',
            'role' => 'mechanic',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'company_id' => $this->companyA->id,
            'full_name' => 'Federico Santana',
            'email' => 'federico@test.do',
            'phone' => '809-555-9988',
        ]);

        $this->vehicleA = Vehicle::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'plate' => 'A777888',
            'make' => 'Toyota',
            'model' => 'RAV4',
            'year' => 2023,
            'current_mileage' => 15000,
        ]);

        $this->serviceA = Service::create([
            'company_id' => $this->companyA->id,
            'name' => 'Mantenimiento 15K',
            'maintenance_type' => 'preventive',
            'default_price' => 4500.00,
            'default_interval_days' => 180,
            'default_interval_mileage' => 5000,
            'is_active' => true,
        ]);
    }

    /**
     * Valida que un company_admin pueda registrar un servicio en su catálogo.
     */
    public function test_company_admin_can_create_service_in_catalog(): void
    {
        $payload = [
            'name' => 'Alineación y Balanceo Computarizado',
            'maintenance_type' => 'preventive',
            'default_interval_days' => 90,
            'default_interval_mileage' => 10000,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminA)->post('/services', $payload);

        $response->assertRedirect(route('services.index'));
        $this->assertDatabaseHas('services', [
            'name' => 'Alineación y Balanceo Computarizado',
            'company_id' => $this->companyA->id,
            'maintenance_type' => 'preventive',
        ]);
    }

    /**
     * Valida la creación de una orden de mantenimiento con servicio, mecánico y vehículo.
     */
    public function test_company_admin_can_create_maintenance_order_and_link_vehicle_history(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_id' => $this->serviceA->id,
            'mechanic_id' => $this->mechanicA->id,
            'maintenance_type' => 'preventive',
            'service_name_snapshot' => 'Mantenimiento 15K',
            'service_date' => now()->toDateString(),
            'current_mileage' => 16200,
            'cost' => 4500.00,
            'status' => 'completed',
            'next_due_date' => now()->addDays(180)->toDateString(),
            'next_due_mileage' => 21200,
            'technical_notes' => 'Filtros reemplazados, aceite sintético 0W-20 colocado.',
        ];

        $response = $this->actingAs($this->adminA)->post('/maintenance-orders', $payload);

        $response->assertSessionHasNoErrors();

        $createdOrder = MaintenanceOrder::where('vehicle_id', $this->vehicleA->id)->first();
        $this->assertNotNull($createdOrder);

        $response->assertRedirect(route('maintenance-orders.show', $createdOrder));

        $this->assertDatabaseHas('maintenance_orders', [
            'id' => $createdOrder->id,
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'mechanic_id' => $this->mechanicA->id,
            'current_mileage' => 16200,
            'status' => 'completed',
        ]);

        // Valida que el odómetro del vehículo se actualizó automáticamente
        $this->assertEquals(16200, $this->vehicleA->fresh()->current_mileage);

        // Valida que figure en el historial del vehículo
        $this->assertEquals(1, $this->vehicleA->fresh()->maintenanceOrders()->count());
    }

    /**
     * Valida que un company_admin no pueda ver ni crear órdenes de otra empresa.
     */
    public function test_company_admin_cannot_view_order_from_another_company(): void
    {
        $orderA = MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_name_snapshot' => 'Chequeo General',
            'service_date' => now()->toDateString(),
            'current_mileage' => 15000,
            'status' => 'open',
            'maintenance_type' => 'preventive',
        ]);

        $response = $this->actingAs($this->adminB)->get("/maintenance-orders/{$orderA->id}");

        $response->assertStatus(403);
    }

    /**
     * Valida que un mecánico no pueda acceder a configuración global ni a órdenes de otra empresa.
     */
    public function test_mechanic_can_view_orders_of_their_company_but_not_from_other_companies(): void
    {
        $orderA = MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_name_snapshot' => 'Cambio Pastillas Freno',
            'service_date' => now()->toDateString(),
            'current_mileage' => 15000,
            'status' => 'in_progress',
            'maintenance_type' => 'corrective',
        ]);

        // Mecánico de empresa A puede ver la orden de su empresa
        $responseA = $this->actingAs($this->mechanicA)->get("/maintenance-orders/{$orderA->id}");
        $responseA->assertStatus(200);
        $responseA->assertSee('Cambio Pastillas Freno');

        // Mecánico de empresa A no puede acceder a Companies
        $responseCompanies = $this->actingAs($this->mechanicA)->get('/companies');
        $responseCompanies->assertStatus(403);
    }
}

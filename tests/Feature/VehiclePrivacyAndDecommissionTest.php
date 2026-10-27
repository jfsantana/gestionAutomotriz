<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehiclePrivacyAndDecommissionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;

    protected Company $companyB;

    protected User $adminA;

    protected User $adminB;

    protected Customer $customerOld;

    protected Customer $customerNew;

    protected Vehicle $vehicle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::create([
            'name' => 'Taller Privacidad A',
            'slug' => 'taller-privacidad-a',
            'status' => 'active',
        ]);

        $this->companyB = Company::create([
            'name' => 'Taller Privacidad B',
            'slug' => 'taller-privacidad-b',
            'status' => 'active',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Plan de Pruebas de Privacidad',
            'slug' => 'plan-pruebas-privacidad',
            'monthly_price' => 50,
            'yearly_price' => 500,
            'is_active' => true,
        ]);
        foreach ([$this->companyA, $this->companyB] as $company) {
            CompanySubscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'starts_at' => today(),
                'ends_at' => today()->addMonth(),
            ]);
        }

        $this->adminA = User::create([
            'name' => 'Admin Taller A',
            'username' => 'admin_priv_a',
            'email' => 'admin@priv-a.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);

        $this->adminB = User::create([
            'name' => 'Admin Taller B',
            'username' => 'admin_priv_b',
            'email' => 'admin@priv-b.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyB->id,
            'is_active' => true,
        ]);

        $this->customerOld = Customer::create([
            'company_id' => $this->companyA->id,
            'full_name' => 'Dueño Anterior',
            'phone' => '809-555-1111',
        ]);

        $this->customerNew = Customer::create([
            'company_id' => $this->companyA->id,
            'full_name' => 'Dueño Nuevo',
            'phone' => '809-555-2222',
        ]);

        $this->vehicle = Vehicle::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerOld->id,
            'plate' => 'A777888',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'current_mileage' => 50000,
        ]);
    }

    /**
     * Valida que al intentar registrar una placa existente, se bloquee con mensaje neutro sin revelar datos ni historial.
     */
    public function test_cannot_register_existing_plate_and_shows_privacy_error(): void
    {
        $payload = [
            'customer_id' => $this->customerNew->id,
            'plate' => 'A777888',
            'vehicle_type' => 'carro',
            'make' => 'Toyota',
            'model' => 'Corolla',
        ];

        $response = $this->actingAs($this->adminA)->post('/vehicles', $payload);

        $response->assertSessionHasErrors('plate');
        // El vehículo no cambió de propietario
        $this->assertEquals($this->customerOld->id, $this->vehicle->fresh()->customer_id);
    }

    /**
     * Valida que un usuario de otra empresa no pueda ver vehículos de la empresa A (Aislamiento total).
     */
    public function test_user_cannot_view_vehicle_from_another_company(): void
    {
        $response = $this->actingAs($this->adminB)->get("/vehicles/{$this->vehicle->id}");

        $response->assertNotFound();
    }

    /**
     * Valida que la baja del vehículo borre el historial y libere la placa para un nuevo registro limpio.
     */
    public function test_decommissioning_vehicle_deletes_history_and_frees_plate_for_clean_registration(): void
    {
        // 1. Crear orden de mantenimiento
        MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerOld->id,
            'vehicle_id' => $this->vehicle->id,
            'service_name_snapshot' => 'Cambio de Frenos 50K',
            'service_date' => now()->toDateString(),
            'current_mileage' => 50000,
            'status' => 'completed',
            'maintenance_type' => 'preventive',
        ]);

        $this->assertEquals(1, $this->vehicle->maintenanceOrders()->count());

        // 2. Dar de baja el vehículo por parte del propietario/taller actual
        $responseDecommission = $this->actingAs($this->adminA)->post("/vehicles/{$this->vehicle->id}/decommission");
        $responseDecommission->assertRedirect(route('customers.show', $this->customerOld));

        // 3. Comprobar que el vehículo y el historial fueron eliminados
        $this->assertDatabaseMissing('vehicles', ['id' => $this->vehicle->id]);
        $this->assertDatabaseMissing('maintenance_orders', ['vehicle_id' => $this->vehicle->id]);

        // 4. Ahora el nuevo cliente puede registrar la placa limpiamente sin historial previo
        $payloadNew = [
            'customer_id' => $this->customerNew->id,
            'plate' => 'A777888',
            'vehicle_type' => 'carro',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'current_mileage' => 50000,
        ];

        $responseNew = $this->actingAs($this->adminA)->post('/vehicles', $payloadNew);
        $responseNew->assertRedirect();

        $newRegisteredVehicle = Vehicle::where('plate', 'A777888')->first();
        $this->assertNotNull($newRegisteredVehicle);
        $this->assertEquals($this->customerNew->id, $newRegisteredVehicle->customer_id);

        // El nuevo registro arranca con cero historial
        $this->assertEquals(0, $newRegisteredVehicle->maintenanceOrders()->count());
    }
}

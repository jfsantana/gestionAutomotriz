<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAndVehicleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminA;
    protected User $adminB;
    protected Customer $customerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::create([
            'name' => 'Taller AutoFix Santo Domingo',
            'slug' => 'taller-autofix-sd',
            'status' => 'active',
        ]);

        $this->companyB = Company::create([
            'name' => 'Concesionario Cibao',
            'slug' => 'concesionario-cibao',
            'status' => 'active',
        ]);

        $this->adminA = User::create([
            'name' => 'Admin Empresa A',
            'username' => 'admin_a',
            'email' => 'admin_a@autofix.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);

        $this->adminB = User::create([
            'name' => 'Admin Empresa B',
            'username' => 'admin_b',
            'email' => 'admin_b@cibao.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyB->id,
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'company_id' => $this->companyA->id,
            'full_name' => 'Mario Rossi',
            'email' => 'mario@rossi.com',
            'phone' => '809-555-1111',
            'document_type' => 'cedula',
            'document_number' => '001-0000001-1',
            'customer_type' => 'individual',
        ]);
    }

    /**
     * Valida que un company_admin pueda registrar un cliente y sea redirigido a su ficha.
     */
    public function test_company_admin_can_create_customer_and_is_redirected_to_show(): void
    {
        $payload = [
            'full_name' => 'Alejandro Morales',
            'email' => 'amorales@test.do',
            'phone' => '809-555-2222',
            'document_type' => 'cedula',
            'document_number' => '001-0000002-2',
            'customer_type' => 'individual',
        ];

        $response = $this->actingAs($this->adminA)->post('/customers', $payload);

        $created = Customer::where('document_number', '001-0000002-2')->first();
        $this->assertNotNull($created);
        $this->assertEquals($this->companyA->id, $created->company_id);

        $response->assertRedirect(route('customers.show', $created));
    }

    /**
     * Valida que desde la ficha del cliente se registre un vehículo asociado a ese cliente y empresa.
     */
    public function test_company_admin_can_add_vehicle_to_customer(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'plate' => 'A987654',
            'vin' => '1HGCR2F83HA123456',
            'make' => 'Honda',
            'model' => 'Accord',
            'year' => 2021,
            'color' => 'Negro',
            'current_mileage' => 35000,
            'fuel_type' => 'gasolina',
        ];

        $response = $this->actingAs($this->adminA)->post('/vehicles', $payload);

        $response->assertRedirect(route('customers.show', $this->customerA));

        $this->assertDatabaseHas('vehicles', [
            'plate' => 'A987654',
            'customer_id' => $this->customerA->id,
            'company_id' => $this->companyA->id,
            'make' => 'Honda',
            'model' => 'Accord',
        ]);

        $this->assertEquals(1, $this->customerA->fresh()->vehicles()->count());
    }

    /**
     * Valida el aislamiento: company_admin B no puede ver la ficha de un cliente de la empresa A.
     */
    public function test_company_admin_cannot_view_customer_from_another_company(): void
    {
        $response = $this->actingAs($this->adminB)->get("/customers/{$this->customerA->id}");

        $response->assertStatus(403);
    }

    /**
     * Valida el aislamiento: company_admin B no puede agregar un vehículo a un cliente de la empresa A.
     */
    public function test_company_admin_cannot_add_vehicle_to_customer_from_another_company(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'plate' => 'B123456',
            'make' => 'Toyota',
            'model' => 'Corolla',
        ];

        $response = $this->actingAs($this->adminB)->post('/vehicles', $payload);

        // La validación en StoreVehicleRequest o el abort rechazan la petición
        $this->assertTrue(in_array($response->getStatusCode(), [302, 403]));
        $this->assertDatabaseMissing('vehicles', ['plate' => 'B123456']);
    }

    /**
     * Valida que no se puedan duplicar placas dentro de la misma empresa.
     */
    public function test_vehicle_plate_must_be_unique_within_same_company(): void
    {
        Vehicle::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'plate' => 'A111111',
            'make' => 'Hyundai',
            'model' => 'Sonata',
        ]);

        $payload = [
            'customer_id' => $this->customerA->id,
            'plate' => 'A111111',
            'make' => 'Kia',
            'model' => 'K5',
        ];

        $response = $this->actingAs($this->adminA)->post('/vehicles', $payload);

        $response->assertSessionHasErrors('plate');
    }

    /**
     * Valida que un vehículo siempre herede y mantenga estrictamente
     * la misma empresa (company_id) del cliente asociado, tanto en creación como en actualización.
     */
    public function test_vehicle_always_matches_customer_company_id_on_store_and_update(): void
    {
        // 1. En creación: aunque intenten mandar otro company_id o ninguno, se asigna el del cliente
        $payload = [
            'customer_id' => $this->customerA->id,
            'company_id' => $this->companyB->id, // Intento malicioso de forzar otra empresa
            'plate' => 'C999888',
            'make' => 'Mazda',
            'model' => 'CX-5',
        ];

        $response = $this->actingAs($this->adminA)->post('/vehicles', $payload);
        $response->assertRedirect(route('customers.show', $this->customerA));

        $vehicle = Vehicle::where('plate', 'C999888')->first();
        $this->assertNotNull($vehicle);
        $this->assertEquals($this->customerA->company_id, $vehicle->company_id);
        $this->assertEquals($this->companyA->id, $vehicle->company_id);

        // 2. En actualización: se garantiza que conserve la misma empresa que su cliente
        $updatePayload = [
            'company_id' => $this->companyB->id, // Intento de alterar la empresa
            'plate' => 'C999888',
            'make' => 'Mazda',
            'model' => 'CX-5 Modificado',
        ];

        $updateResponse = $this->actingAs($this->adminA)->put("/vehicles/{$vehicle->id}", $updatePayload);
        $updateResponse->assertRedirect(route('customers.show', $this->customerA));

        $vehicle->refresh();
        $this->assertEquals('CX-5 Modificado', $vehicle->model);
        $this->assertEquals($this->customerA->company_id, $vehicle->company_id);
        $this->assertEquals($this->companyA->id, $vehicle->company_id);
    }
}


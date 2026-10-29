<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
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

        $plan = SubscriptionPlan::create([
            'name' => 'Plan de Pruebas de Vehículos',
            'slug' => 'plan-pruebas-vehiculos',
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
     * Regresión: crear un cliente habilitando el portal debe enviar la invitación
     * y redirigir a la ficha del cliente sin error 500.
     */
    public function test_company_admin_creates_customer_with_portal_invitation_and_is_redirected_to_show(): void
    {
        $response = $this->actingAs($this->adminA)->post('/customers', [
            'full_name' => 'Cliente Invitado Portal',
            'email' => 'invitado.portal@test.do',
            'customer_type' => 'individual',
            'enable_portal' => '1',
        ]);

        $created = Customer::where('email', 'invitado.portal@test.do')->first();
        $this->assertNotNull($created);
        $this->assertEquals($this->companyA->id, $created->company_id);

        $response->assertRedirect(route('customers.show', $created));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'customer_id' => $created->id,
            'email' => 'invitado.portal@test.do',
            'role' => 'customer',
        ]);
        $this->assertDatabaseHas('client_invitations', ['customer_id' => $created->id]);
    }

    /**
     * Regresión: si el transporte de correo falla al enviar la invitación, el cliente
     * y su usuario deben quedar registrados y la respuesta no debe ser un error 500.
     */
    public function test_company_admin_creates_customer_with_portal_invitation_even_if_mail_transport_fails(): void
    {
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('SMTP unavailable'));

        $response = $this->actingAs($this->adminA)->post('/customers', [
            'full_name' => 'Cliente Invitado Sin Correo',
            'email' => 'sin.correo@test.do',
            'customer_type' => 'individual',
            'enable_portal' => '1',
        ]);

        $created = Customer::where('email', 'sin.correo@test.do')->first();
        $this->assertNotNull($created);

        $response->assertRedirect(route('customers.show', $created));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'customer_id' => $created->id,
            'email' => 'sin.correo@test.do',
            'role' => 'customer',
        ]);
        $this->assertDatabaseHas('client_invitations', ['customer_id' => $created->id]);
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
            'vehicle_type' => 'carro',
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

    public function test_new_vehicle_form_displays_brands_from_the_catalog(): void
    {
        $response = $this->actingAs($this->adminA)
            ->get(route('customers.vehicles.create', $this->customerA));

        $response->assertOk()
            ->assertSee('value="Toyota"', false)
            ->assertSee('value="Honda"', false)
            ->assertSee('Chuto / Tractocamión')
            ->assertSee('data-vehicle-type="camion"', false);
    }

    public function test_company_admin_can_register_a_truck_with_a_truck_brand(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'plate' => 'TRK123',
            'vehicle_type' => 'camion',
            'make' => 'Isuzu',
            'model' => 'NPR',
        ];

        $response = $this->actingAs($this->adminA)->post(route('vehicles.store'), $payload);

        $response->assertRedirect(route('customers.show', $this->customerA));
        $this->assertDatabaseHas('vehicles', [
            'plate' => 'TRK123',
            'vehicle_type' => 'camion',
            'make' => 'Isuzu',
            'model' => 'NPR',
        ]);
    }

    public function test_vehicle_brand_must_belong_to_the_selected_vehicle_type(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'plate' => 'TRK456',
            'vehicle_type' => 'camion',
            'make' => 'Toyota',
            'model' => 'NPR',
        ];

        $response = $this->actingAs($this->adminA)->post(route('vehicles.store'), $payload);

        $response->assertSessionHasErrors(['make']);
        $this->assertDatabaseMissing('vehicles', ['plate' => 'TRK456']);
    }

    public function test_vehicle_cannot_be_created_with_a_brand_outside_the_catalog(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'plate' => 'Z123456',
            'vehicle_type' => 'carro',
            'make' => 'Marca inventada',
            'model' => 'Modelo de prueba',
        ];

        $response = $this->actingAs($this->adminA)->post(route('vehicles.store'), $payload);

        $response->assertSessionHasErrors(['make']);
        $this->assertDatabaseMissing('vehicles', ['plate' => 'Z123456']);
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
            'vehicle_type' => 'carro',
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
            'vehicle_type' => 'carro',
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
            'vehicle_type' => 'carro',
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
            'vehicle_type' => 'carro',
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

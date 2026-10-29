<?php

namespace Tests\Feature;

use App\Domain\Billing\Models\CompanySubscription;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Companies\Models\CustomerWorkshopRelationship;
use App\Domain\Companies\Models\WorkshopAssociationInvitation;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Vehicles\Models\Vehicle;
use App\Mail\CustomerWorkshopRelationshipEndedMail;
use App\Mail\WorkshopAssociationInvitationMail;
use App\Mail\WorkshopCustomerRelationshipEndedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerWorkshopRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_can_close_relationship_without_deleting_history_and_new_workshop_cannot_view_it(): void
    {
        [$oldCompany, $oldAdmin] = $this->createWorkshop('Taller Anterior', 'taller-anterior');
        [$newCompany, $newAdmin] = $this->createWorkshop('Taller Nuevo', 'taller-nuevo');
        [$customer, $client, $vehicle] = $this->createCustomer($oldCompany);
        $oldOrder = $this->createWorkshopOrder($oldCompany, $oldAdmin, $customer, $vehicle, 'Servicio privado anterior', 475);
        $personalOrder = MaintenanceOrder::create([
            'company_id' => null,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'registered_by_user_id' => $client->id,
            'service_name_snapshot' => 'Registro personal privado',
            'service_date' => today(),
            'current_mileage' => 120,
            'cost' => 321,
            'status' => 'completed',
            'origin' => 'customer',
        ]);

        Mail::fake();

        $this->actingAs($client)
            ->get(route('portal.maintenance.index'))
            ->assertOk()
            ->assertSee('Servicio privado anterior')
            ->assertSee('Registro personal privado')
            ->assertDontSee('475.00')
            ->assertDontSee('321.00');

        $this->actingAs($oldAdmin)
            ->post(route('customers.workshop-relationship.end', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');

        $this->assertNull($customer->fresh()->company_id);
        $this->assertNull($vehicle->fresh()->company_id);
        $this->assertDatabaseHas('maintenance_orders', [
            'id' => $oldOrder->id,
            'company_id' => $oldCompany->id,
            'cost' => null,
        ]);
        $this->assertDatabaseHas('maintenance_orders', [
            'id' => $personalOrder->id,
            'company_id' => null,
            'cost' => null,
        ]);
        $this->assertDatabaseHas('customer_workshop_relationships', [
            'customer_id' => $customer->id,
            'company_id' => $oldCompany->id,
            'ended_by_role' => 'company_admin',
        ]);
        Mail::assertSent(CustomerWorkshopRelationshipEndedMail::class);

        $this->actingAs($newAdmin)
            ->post(route('workshop-associations.store'), ['email' => $client->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(WorkshopAssociationInvitationMail::class);
        $invitation = WorkshopAssociationInvitation::query()->where('customer_id', $customer->id)->firstOrFail();
        $sentInvitation = Mail::sent(WorkshopAssociationInvitationMail::class)->first();
        $token = basename((string) parse_url($sentInvitation->acceptanceUrl, PHP_URL_PATH));

        $this->actingAs($client)
            ->get(route('workshop-associations.show', ['token' => $token]))
            ->assertOk()
            ->assertSee($newCompany->name);

        $this->actingAs($client)
            ->post(route('workshop-associations.accept', ['token' => $token]))
            ->assertRedirect(route('portal.account'))
            ->assertSessionHas('success');

        $this->assertSame($newCompany->id, $customer->fresh()->company_id);
        $this->assertSame($newCompany->id, $vehicle->fresh()->company_id);
        $this->assertSame($oldCompany->id, $oldOrder->fresh()->company_id);
        $this->assertSame($invitation->id, $invitation->fresh()->id);

        $this->actingAs($newAdmin)
            ->get(route('maintenance-orders.index'))
            ->assertOk()
            ->assertDontSee('Servicio privado anterior')
            ->assertDontSee('Registro personal privado');

        $this->actingAs($newAdmin)
            ->get(route('maintenance-orders.show', $oldOrder))
            ->assertNotFound();

        $this->actingAs($newAdmin)
            ->get(route('vehicles.show', $vehicle))
            ->assertOk()
            ->assertDontSee('Servicio privado anterior')
            ->assertDontSee('Registro personal privado');

        $this->actingAs($client)
            ->get(route('portal.maintenance.index'))
            ->assertOk()
            ->assertSee('Servicio privado anterior')
            ->assertSee('Registro personal privado')
            ->assertDontSee('475.00')
            ->assertDontSee('321.00');
    }

    public function test_customer_needs_a_current_paid_fleet_subscription_to_end_the_relationship(): void
    {
        [$company] = $this->createWorkshop('Taller Vigente', 'taller-vigente');
        [$customer, $client] = $this->createCustomer($company);
        Mail::fake();

        $this->from(route('portal.account'))
            ->actingAs($client)
            ->post(route('portal.account.workshop-relationship.end'))
            ->assertRedirect(route('portal.account'))
            ->assertSessionHasErrors('relationship');

        $this->assertSame($company->id, $customer->fresh()->company_id);
        $this->assertSame(0, CustomerWorkshopRelationship::query()
            ->where('customer_id', $customer->id)
            ->whereNotNull('ended_at')
            ->count());
        Mail::assertNothingSent();
    }

    public function test_premium_customer_can_end_relationship_and_the_workshop_is_notified(): void
    {
        [$company, $admin] = $this->createWorkshop('Taller Notificado', 'taller-notificado');
        [$customer, $client, $vehicle] = $this->createCustomer($company, true);
        $order = $this->createWorkshopOrder($company, $admin, $customer, $vehicle, 'Servicio del cliente', 800);
        Mail::fake();

        $this->actingAs($client)
            ->post(route('portal.account.workshop-relationship.end'))
            ->assertRedirect(route('portal.account'))
            ->assertSessionHas('success');

        $this->assertNull($customer->fresh()->company_id);
        $this->assertNull($vehicle->fresh()->company_id);
        $this->assertNull($order->fresh()->cost);
        Mail::assertSent(WorkshopCustomerRelationshipEndedMail::class);
    }

    public function test_super_admin_can_end_workshop_customer_relationship_and_customer_is_notified(): void
    {
        [$company, $admin] = $this->createWorkshop('Taller Supervisado', 'taller-supervisado');
        [$customer, , $vehicle] = $this->createCustomer($company);
        $order = $this->createWorkshopOrder($company, $admin, $customer, $vehicle, 'Mantenimiento auditado', 250);
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'company_id' => null,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        Mail::fake();

        $this->actingAs($superAdmin)
            ->post(route('customers.workshop-relationship.end', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');

        $this->assertNull($customer->fresh()->company_id);
        $this->assertNull($vehicle->fresh()->company_id);
        $this->assertNull($order->fresh()->cost);
        $this->assertDatabaseHas('customer_workshop_relationships', [
            'customer_id' => $customer->id,
            'company_id' => $company->id,
            'ended_by_user_id' => $superAdmin->id,
            'ended_by_role' => 'super_admin',
        ]);
        Mail::assertSent(CustomerWorkshopRelationshipEndedMail::class);
    }

    public function test_workshop_cannot_see_the_personal_vehicle_the_customer_registered_on_his_own(): void
    {
        [$company, $admin] = $this->createWorkshop('Taller Principal', 'taller-principal');
        [$customer, $client, $workshopVehicle] = $this->createCustomer($company);

        // El cliente registra un segundo vehículo de manera particular desde su portal
        $this->actingAs($client)
            ->post(route('portal.vehicles.store'), [
                'plate' => 'PRIVADO1',
                'vehicle_type' => 'carro',
                'make' => 'Honda',
                'model' => 'Civic',
                'current_mileage' => 50,
            ])
            ->assertRedirect();

        $personalVehicle = Vehicle::where('plate', 'PRIVADO1')->sole();
        $this->assertNull($personalVehicle->company_id);

        // El taller no ve el vehículo particular en la ficha del cliente
        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('TEST123')
            ->assertDontSee('PRIVADO1');

        // El taller no ve el vehículo particular en el listado general de vehículos
        $this->actingAs($admin)
            ->get(route('vehicles.index'))
            ->assertOk()
            ->assertSee('TEST123')
            ->assertDontSee('PRIVADO1');

        // El taller no puede abrir la ficha del vehículo particular
        $this->actingAs($admin)
            ->get(route('vehicles.show', $personalVehicle))
            ->assertNotFound();

        // El taller no puede registrarle una orden de mantenimiento
        $this->actingAs($admin)
            ->post(route('maintenance-orders.store'), [
                'customer_id' => $customer->id,
                'vehicle_id' => $personalVehicle->id,
                'service_name_snapshot' => 'Servicio no autorizado',
                'maintenance_type' => 'preventive',
                'service_date' => today()->toDateString(),
                'current_mileage' => 60,
                'status' => 'open',
            ])
            ->assertSessionHasErrors('vehicle_id');

        $this->assertDatabaseMissing('maintenance_orders', [
            'vehicle_id' => $personalVehicle->id,
        ]);

        // El cliente sí ve ambos vehículos en su portal
        $this->actingAs($client)
            ->get(route('portal.vehicles.index'))
            ->assertOk()
            ->assertSee('TEST123')
            ->assertSee('PRIVADO1');
    }

    /**
     * @return array{Company, User}
     */
    private function createWorkshop(string $name, string $slug): array
    {
        $company = Company::create([
            'name' => $name,
            'slug' => $slug,
            'email' => "{$slug}@example.test",
            'status' => 'active',
        ]);
        $plan = SubscriptionPlan::create([
            'name' => "{$name} Plan",
            'slug' => "{$slug}-plan",
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
        $admin = User::create([
            'name' => "{$name} Admin",
            'username' => $slug,
            'email' => "{$slug}-admin@example.test",
            'password' => 'secure-password',
            'role' => 'company_admin',
            'company_id' => $company->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);

        return [$company, $admin];
    }

    /**
     * @return array{Customer, User, Vehicle}
     */
    private function createCustomer(Company $company, bool $premium = false): array
    {
        $customer = Customer::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente de Prueba',
            'email' => 'cliente@example.test',
            'customer_type' => 'individual',
            'is_fleet_premium' => $premium,
            'fleet_expires_at' => $premium ? now()->addMonth() : null,
            'fleet_trial_started_at' => now(),
        ]);
        $user = User::create([
            'name' => $customer->full_name,
            'username' => 'cliente',
            'email' => $customer->email,
            'password' => 'secure-password',
            'role' => 'customer',
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'is_active' => true,
            'account_status' => 'ACTIVE',
        ]);
        $user->markEmailAsVerified();
        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'company_id' => $company->id,
            'plate' => 'TEST123',
            'vehicle_type' => 'carro',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'current_mileage' => 100,
        ]);

        return [$customer, $user, $vehicle];
    }

    private function createWorkshopOrder(
        Company $company,
        User $admin,
        Customer $customer,
        Vehicle $vehicle,
        string $serviceName,
        float $cost,
    ): MaintenanceOrder {
        return MaintenanceOrder::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'registered_by_user_id' => $admin->id,
            'service_name_snapshot' => $serviceName,
            'service_date' => today(),
            'current_mileage' => 100,
            'cost' => $cost,
            'status' => 'completed',
            'origin' => 'workshop',
        ]);
    }
}

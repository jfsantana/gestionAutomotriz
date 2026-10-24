<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Maintenance\Models\Service;
use App\Domain\Users\Models\ClientInvitation;
use App\Domain\Vehicles\Models\Vehicle;
use App\Mail\ClientPortalInvitationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CompanyOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;

    private Company $companyB;

    private Customer $customerA;

    private Customer $customerB;

    private Vehicle $vehicleA;

    private Vehicle $vehicleB;

    private User $adminA;

    private User $adminB;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-24 12:00:00', 'UTC'));
        $this->companyA = Company::create(['name' => 'Taller A', 'slug' => 'taller-a', 'status' => 'active']);
        $this->companyB = Company::create(['name' => 'Taller B', 'slug' => 'taller-b', 'status' => 'active']);
        $this->customerA = Customer::create(['company_id' => $this->companyA->id, 'full_name' => 'Cliente A', 'email' => 'clientea@example.com']);
        $this->customerB = Customer::create(['company_id' => $this->companyB->id, 'full_name' => 'Cliente B', 'email' => 'clienteb@example.com']);
        $this->vehicleA = Vehicle::create(['customer_id' => $this->customerA->id, 'plate' => 'AUTO-A', 'make' => 'Toyota', 'model' => 'Corolla']);
        $this->vehicleB = Vehicle::create(['customer_id' => $this->customerB->id, 'plate' => 'AUTO-B', 'make' => 'Nissan', 'model' => 'Sentra']);
        $this->adminA = User::factory()->create(['role' => 'company_admin', 'company_id' => $this->companyA->id, 'is_active' => true]);
        $this->adminB = User::factory()->create(['role' => 'company_admin', 'company_id' => $this->companyB->id, 'is_active' => true]);
        $this->superAdmin = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'is_active' => true]);
    }

    public function test_repair_restores_order_to_correct_company_instead_of_hiding_it_everywhere(): void
    {
        Mail::fake();
        $order = $this->order($this->vehicleB);
        $service = Service::create(['company_id' => $this->companyA->id, 'name' => 'Catálogo privado A', 'maintenance_type' => 'preventive']);
        $mechanic = User::factory()->create(['company_id' => $this->companyA->id, 'role' => 'mechanic', 'name' => 'Mecánico privado A']);
        DB::table('maintenance_orders')->where('id', $order->id)->update([
            'company_id' => $this->companyA->id, 'service_id' => $service->id, 'mechanic_id' => $mechanic->id,
        ]);
        $order->refresh();
        $original = $order->only(['service_name_snapshot', 'technical_notes', 'cost', 'next_due_date', 'vehicle_id', 'customer_id']);
        $photo = $order->photos()->create(['file_path' => 'maintenance_photos/example.jpg', 'original_name' => 'example.jpg']);

        $this->artisan('ownership:reconcile-customer', ['customer' => $this->customerB->id])->assertSuccessful();
        $this->assertSame($this->companyA->id, $order->fresh()->company_id);
        $this->artisan('ownership:reconcile-customer', ['customer' => $this->customerB->id, '--apply' => true])->assertSuccessful();

        $repaired = $order->fresh();
        $this->assertSame($this->companyB->id, $repaired->company_id);
        $this->assertNull($repaired->service_id);
        $this->assertNull($repaired->mechanic_id);
        $this->assertEquals($original, $repaired->only(array_keys($original)));
        $this->assertModelExists($photo);
        foreach (['maintenance-orders.index', 'reminders.index'] as $route) {
            $this->actingAs($this->adminA)->get(route($route))->assertOk()->assertDontSee('Cliente B')->assertDontSee('AUTO-B');
            $this->actingAs($this->adminB)->get(route($route))->assertOk()->assertSee('Cliente B')->assertSee('AUTO-B');
        }
        $this->actingAs($this->adminA)->get(route('maintenance-orders.show', $order))->assertForbidden();
        $this->actingAs($this->adminB)->get(route('maintenance-orders.show', $order))->assertOk()
            ->assertDontSee('Catálogo privado A')->assertDontSee('Mecánico privado A');
        $this->get(route('company.dashboard'))->assertOk()->assertViewHas('serviceAlertsCount', 1);
        Mail::assertNothingSent();
    }

    public function test_super_admin_moves_all_vehicles_orders_and_portal_account_together(): void
    {
        Mail::fake();
        $secondVehicle = Vehicle::create(['customer_id' => $this->customerA->id, 'plate' => 'AUTO-A2', 'make' => 'Toyota', 'model' => 'Yaris']);
        $order = $this->order($this->vehicleA, ['registered_by_user_id' => $this->adminA->id]);
        $historical = $this->order($secondVehicle);
        $historical->delete();
        $secondVehicle->delete();
        $portalUser = User::factory()->create([
            'role' => 'customer', 'company_id' => $this->companyA->id, 'customer_id' => $this->customerA->id,
            'is_active' => true, 'account_status' => 'ACTIVE',
        ]);
        $invitation = ClientInvitation::create([
            'user_id' => $portalUser->id, 'customer_id' => $this->customerA->id, 'company_id' => $this->companyA->id,
            'token' => str_repeat('a', 64), 'expires_at' => now()->addDay(), 'created_by' => $this->adminA->id,
        ]);

        $this->actingAs($this->superAdmin)->put(route('customers.update', $this->customerA), [
            'full_name' => 'Cliente A', 'customer_type' => 'individual', 'company_id' => $this->companyB->id,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame($this->companyB->id, $this->customerA->fresh()->company_id);
        $this->assertSame($this->companyB->id, $this->vehicleA->fresh()->company_id);
        $this->assertSame($this->companyB->id, Vehicle::withTrashed()->findOrFail($secondVehicle->id)->company_id);
        $this->assertSame($this->companyB->id, $order->fresh()->company_id);
        $this->assertNull($order->fresh()->registered_by_user_id);
        $this->assertSame($this->companyB->id, MaintenanceOrder::withTrashed()->findOrFail($historical->id)->company_id);
        $this->assertSame($this->companyB->id, $portalUser->fresh()->company_id);
        $this->assertSame($this->companyB->id, $invitation->fresh()->company_id);
        $this->assertSoftDeleted($secondVehicle);
        $this->assertSoftDeleted($historical);
        foreach (['customers.index', 'vehicles.index', 'maintenance-orders.index', 'reminders.index'] as $route) {
            $this->actingAs($this->adminA)->get(route($route))->assertOk()->assertDontSee('Cliente A')->assertDontSee('AUTO-A');
            $this->actingAs($this->adminB)->get(route($route))->assertOk()->assertSee('Cliente A');
        }
        $this->actingAs($this->adminA)->get(route('maintenance-orders.edit', $order))->assertForbidden();
        $this->put(route('maintenance-orders.update', $order), $this->payload($this->vehicleA))->assertForbidden();
        $this->actingAs($portalUser->fresh())->get(route('portal.maintenance.index'))->assertOk()->assertSee('AUTO-A');
        Mail::assertNothingSent();
    }

    public function test_plate_conflict_rolls_back_entire_customer_transfer(): void
    {
        Mail::fake();
        $order = $this->order($this->vehicleA);
        Vehicle::create(['customer_id' => $this->customerB->id, 'plate' => 'AUTO-A', 'make' => 'Toyota', 'model' => 'Yaris']);

        $this->actingAs($this->superAdmin)->put(route('customers.update', $this->customerA), [
            'full_name' => 'No debe guardarse', 'customer_type' => 'individual', 'company_id' => $this->companyB->id,
        ])->assertSessionHasErrors('company_id');

        $this->assertSame('Cliente A', $this->customerA->fresh()->full_name);
        $this->assertSame($this->companyA->id, $this->customerA->fresh()->company_id);
        $this->assertSame($this->companyA->id, $this->vehicleA->fresh()->company_id);
        $this->assertSame($this->companyA->id, $order->fresh()->company_id);
        Mail::assertNothingSent();
    }

    public function test_ambiguous_vehicle_customer_links_are_not_repaired_by_guessing(): void
    {
        $order = $this->order($this->vehicleA);
        DB::table('maintenance_orders')->where('id', $order->id)->update(['vehicle_id' => $this->vehicleB->id]);

        $this->artisan('ownership:reconcile-customer', ['customer' => $this->customerA->id, '--apply' => true])->assertFailed();

        $this->assertSame($this->companyA->id, $order->fresh()->company_id);
        $this->assertSame($this->vehicleB->id, $order->fresh()->vehicle_id);
        $this->assertSame($this->companyA->id, $this->customerA->fresh()->company_id);
    }

    public function test_workshop_cannot_move_its_customer_to_another_company(): void
    {
        $order = $this->order($this->vehicleA);

        $this->actingAs($this->adminA)->put(route('customers.update', $this->customerA), [
            'full_name' => 'Cliente A', 'customer_type' => 'individual', 'company_id' => $this->companyB->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->companyA->id, $this->customerA->fresh()->company_id);
        $this->assertSame($this->companyA->id, $order->fresh()->company_id);
    }

    public function test_workshop_creates_customer_and_multiple_vehicles_only_in_its_company(): void
    {
        Mail::fake();
        $this->actingAs($this->adminA)->post(route('customers.store'), [
            'full_name' => 'Cliente nuevo', 'customer_type' => 'individual', 'company_id' => $this->companyB->id,
        ])->assertSessionHasNoErrors();
        $customer = Customer::where('full_name', 'Cliente nuevo')->sole();
        $this->assertSame($this->companyA->id, $customer->company_id);
        foreach (['NUEVO1', 'NUEVO2', 'NUEVO3'] as $plate) {
            $this->post(route('vehicles.store'), [
                'customer_id' => $customer->id, 'company_id' => $this->companyB->id,
                'plate' => $plate, 'make' => 'Toyota', 'model' => 'Corolla',
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(3, $customer->vehicles()->count());
        $this->assertSame(0, $customer->vehicles()->where('company_id', '!=', $this->companyA->id)->count());
        $this->actingAs($this->adminB)->get(route('customers.show', $customer))->assertForbidden();
        $this->get(route('vehicles.index'))->assertOk()->assertDontSee('NUEVO1')->assertDontSee('Cliente nuevo');
        Mail::assertNothingSent();
    }

    #[TestWith(['customer'])]
    #[TestWith(['company_admin'])]
    #[TestWith(['mechanic'])]
    public function test_customer_and_staff_without_company_cannot_access_operational_modules(string $role): void
    {
        $user = User::factory()->create([
            'role' => $role, 'company_id' => $role === 'customer' ? $this->companyA->id : null, 'is_active' => true,
        ]);
        $this->actingAs($user);

        foreach (['customers.index', 'vehicles.index', 'maintenance-orders.index', 'reminders.index', 'dashboard', 'company.dashboard'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->post(route('maintenance-orders.store'), $this->payload($this->vehicleA))->assertForbidden();
    }

    public function test_workshop_cannot_create_or_edit_orders_for_foreign_customer(): void
    {
        Mail::fake();
        $foreignOrder = $this->order($this->vehicleB);
        $this->actingAs($this->adminA);

        $this->post(route('maintenance-orders.store'), $this->payload($this->vehicleB))->assertSessionHasErrors(['customer_id', 'vehicle_id']);
        $this->get(route('maintenance-orders.show', $foreignOrder))->assertForbidden();
        $this->get(route('maintenance-orders.edit', $foreignOrder))->assertForbidden();
        $this->put(route('maintenance-orders.update', $foreignOrder), $this->payload($this->vehicleB))->assertForbidden();
        $this->assertDatabaseCount('maintenance_orders', 1);
        Mail::assertNothingSent();
    }

    public function test_super_admin_uses_customer_company_and_rejects_foreign_catalog_and_mechanic(): void
    {
        Mail::fake();
        $service = Service::create(['company_id' => $this->companyA->id, 'name' => 'Servicio A', 'maintenance_type' => 'preventive']);
        $mechanic = User::factory()->create(['company_id' => $this->companyA->id, 'role' => 'mechanic']);
        $this->actingAs($this->superAdmin)->post(route('maintenance-orders.store'), [
            ...$this->payload($this->vehicleB), 'service_id' => $service->id, 'mechanic_id' => $mechanic->id,
        ])->assertSessionHasErrors(['service_id', 'mechanic_id']);
        $this->assertDatabaseCount('maintenance_orders', 0);

        $this->post(route('maintenance-orders.store'), [
            ...$this->payload($this->vehicleB), 'company_id' => $this->companyA->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame($this->companyB->id, MaintenanceOrder::sole()->company_id);
        Mail::assertSentCount(1);
    }

    public function test_model_derives_order_company_from_customer(): void
    {
        $order = $this->order($this->vehicleB, ['company_id' => $this->companyA->id]);

        $this->assertSame($this->companyB->id, $order->company_id);
    }

    public function test_model_rejects_order_for_vehicle_of_another_customer(): void
    {
        try {
            $this->order($this->vehicleB, ['customer_id' => $this->customerA->id]);
            $this->fail('Se aceptó un vehículo ajeno al cliente.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('vehicle_id', $exception->errors());
        }
        $this->assertDatabaseCount('maintenance_orders', 0);
    }

    public function test_invitation_does_not_take_over_an_account_from_another_company(): void
    {
        Mail::fake();
        $foreignUser = User::factory()->create([
            'email' => $this->customerA->email, 'role' => 'customer',
            'customer_id' => $this->customerB->id, 'company_id' => $this->companyB->id,
        ]);

        $this->actingAs($this->adminA)->post(route('customers.invite', $this->customerA))->assertSessionHasErrors('email');

        $this->assertSame($this->customerB->id, $foreignUser->fresh()->customer_id);
        $this->assertSame($this->companyB->id, $foreignUser->fresh()->company_id);
        $this->assertDatabaseCount('client_invitations', 0);
        Mail::assertNothingSent();
    }

    public function test_invitation_can_be_resent_to_the_same_customer_account(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => $this->customerA->email, 'role' => 'customer',
            'customer_id' => $this->customerA->id, 'company_id' => $this->companyA->id,
        ]);

        $this->actingAs($this->adminA)->post(route('customers.invite', $this->customerA))
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('client_invitations', ['user_id' => $user->id, 'customer_id' => $this->customerA->id, 'company_id' => $this->companyA->id]);
        Mail::assertSent(ClientPortalInvitationMail::class, fn ($mail) => $mail->hasTo($this->customerA->email));
    }

    private function order(Vehicle $vehicle, array $attributes = []): MaintenanceOrder
    {
        return MaintenanceOrder::create([
            ...$this->payload($vehicle), 'company_id' => $vehicle->company_id, 'cost' => 150,
            'technical_notes' => 'Conservar historial', 'origin' => 'workshop', ...$attributes,
        ]);
    }

    private function payload(Vehicle $vehicle): array
    {
        return [
            'customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id,
            'service_name_snapshot' => 'Servicio histórico', 'maintenance_type' => 'preventive',
            'service_date' => '2026-09-24', 'current_mileage' => 10000, 'status' => 'completed', 'next_due_date' => '2026-10-26',
        ];
    }
}

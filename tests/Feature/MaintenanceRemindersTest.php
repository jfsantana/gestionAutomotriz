<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Maintenance\Models\MaintenanceOrder;
use App\Domain\Vehicles\Models\Vehicle;
use App\Models\User;
use App\Services\MaintenanceReminderService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MaintenanceRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;

    protected Company $companyB;

    protected User $adminA;

    protected User $adminB;

    protected User $mechanicA;

    protected Customer $customerA;

    protected Vehicle $vehicleA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::create([
            'name' => 'Taller Bella Vista',
            'slug' => 'taller-bella-vista',
            'status' => 'active',
        ]);

        $this->companyB = Company::create([
            'name' => 'Taller Piantini',
            'slug' => 'taller-piantini',
            'status' => 'active',
        ]);

        $this->adminA = User::create([
            'name' => 'Admin Bella Vista',
            'username' => 'admin_bv',
            'email' => 'admin@bv.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);

        $this->adminB = User::create([
            'name' => 'Admin Piantini',
            'username' => 'admin_piantini',
            'email' => 'admin@piantini.do',
            'password' => 'secret123',
            'role' => 'company_admin',
            'company_id' => $this->companyB->id,
            'is_active' => true,
        ]);

        $this->mechanicA = User::create([
            'name' => 'Mecánico Pedro',
            'username' => 'mecanico_pedro',
            'email' => 'pedro@bv.do',
            'password' => 'secret123',
            'role' => 'mechanic',
            'company_id' => $this->companyA->id,
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'company_id' => $this->companyA->id,
            'full_name' => 'Carlos Duarte',
            'email' => 'carlos@duarte.do',
            'phone' => '809-555-4433',
        ]);

        $this->vehicleA = Vehicle::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'plate' => 'A123999',
            'make' => 'Nissan',
            'model' => 'X-Trail',
            'year' => 2021,
            'current_mileage' => 40000,
        ]);
    }

    /**
     * Valida que una orden con fecha vencida se calcule como overdue.
     */
    public function test_order_with_past_date_is_evaluated_as_overdue(): void
    {
        $service = app(MaintenanceReminderService::class);

        $order = MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_name_snapshot' => 'Cambio de Aceite 40K',
            'service_date' => Carbon::now()->subMonths(6),
            'current_mileage' => 40000,
            'next_due_date' => Carbon::now()->subDays(5), // Vencida hace 5 días
            'status' => 'completed',
            'maintenance_type' => 'preventive',
        ]);

        $eval = $service->evaluateOrder($order);

        $this->assertEquals(MaintenanceReminderService::STATUS_OVERDUE, $eval['status']);
        $this->assertEquals('danger', $eval['badge_class']);
    }

    /**
     * Valida que una orden con fecha dentro de los 30 días sea due_soon.
     */
    public function test_order_with_date_within_30_days_is_due_soon(): void
    {
        $service = app(MaintenanceReminderService::class);

        $order = MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_name_snapshot' => 'Revisión Frenos',
            'service_date' => Carbon::now()->subMonths(3),
            'current_mileage' => 40000,
            'next_due_date' => Carbon::now()->addDays(15), // Vence en 15 días
            'status' => 'completed',
            'maintenance_type' => 'preventive',
        ]);

        $eval = $service->evaluateOrder($order);

        $this->assertEquals(MaintenanceReminderService::STATUS_DUE_SOON, $eval['status']);
    }

    /**
     * Valida que una orden por kilometraje pase a due_soon si faltan 1000 km o menos.
     */
    public function test_order_with_mileage_difference_below_1000_is_due_soon(): void
    {
        $service = app(MaintenanceReminderService::class);

        $order = MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_name_snapshot' => 'Cambio Bujías',
            'service_date' => Carbon::now()->subMonth(),
            'current_mileage' => 40000,
            'next_due_mileage' => 40800, // Vehículo en 40000 km => Faltan 800 km
            'status' => 'completed',
            'maintenance_type' => 'preventive',
        ]);

        $eval = $service->evaluateOrder($order, $this->vehicleA->current_mileage);

        $this->assertEquals(MaintenanceReminderService::STATUS_DUE_SOON, $eval['status']);
    }

    /**
     * Valida que si el kilometraje del vehículo superó el próximo vencimiento, sea overdue.
     */
    public function test_order_becomes_overdue_when_vehicle_mileage_exceeds_target(): void
    {
        $service = app(MaintenanceReminderService::class);

        $order = MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_name_snapshot' => 'Mantenimiento Mayor',
            'service_date' => Carbon::now()->subMonths(2),
            'current_mileage' => 40000,
            'next_due_mileage' => 42000,
            'status' => 'completed',
            'maintenance_type' => 'preventive',
        ]);

        // Si el vehículo ahora tiene 42500 km
        $eval = $service->evaluateOrder($order, 42500);

        $this->assertEquals(MaintenanceReminderService::STATUS_OVERDUE, $eval['status']);
    }

    /**
     * Valida el aislamiento multiempresa: company_admin solo ve recordatorios de su taller.
     */
    public function test_company_admin_only_sees_reminders_from_their_own_company(): void
    {
        MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_name_snapshot' => 'Servicio Taller A',
            'service_date' => Carbon::now()->subMonths(3),
            'current_mileage' => 40000,
            'next_due_date' => Carbon::now()->subDays(2),
            'status' => 'completed',
            'maintenance_type' => 'preventive',
        ]);

        // Admin A ve el servicio de su empresa
        $responseA = $this->actingAs($this->adminA)->get('/reminders');
        $responseA->assertStatus(200);
        $responseA->assertSee('Servicio Taller A');

        // Admin B no ve el servicio de la empresa A
        $responseB = $this->actingAs($this->adminB)->get('/reminders');
        $responseB->assertStatus(200);
        $responseB->assertDontSee('Servicio Taller A');
    }

    /**
     * Valida que el comando artisan evalúe los recordatorios correctamente.
     */
    public function test_artisan_command_processes_reminders(): void
    {
        MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_name_snapshot' => 'Alineación',
            'service_date' => Carbon::now()->subMonths(3),
            'current_mileage' => 40000,
            'next_due_date' => Carbon::now()->addDays(5),
            'status' => 'completed',
            'maintenance_type' => 'preventive',
        ]);

        $this->artisan('maintenance:process-reminders')
            ->expectsOutputToContain('Evaluando recordatorios de mantenimiento...')
            ->assertExitCode(0);
    }

    #[TestWith(['company_admin'])]
    #[TestWith(['mechanic'])]
    public function test_reminder_list_and_counts_exclude_cross_company_associations(string $role): void
    {
        $this->freezeTime();
        $ownOrders = [];
        for ($i = 1; $i <= 3; $i++) {
            $customer = Customer::create([
                'company_id' => $this->companyA->id,
                'full_name' => "Cliente propio {$i}",
                'email' => "propio{$i}@example.com",
            ]);
            $vehicle = Vehicle::create([
                'customer_id' => $customer->id,
                'plate' => "LOCAL{$i}",
                'make' => 'Toyota',
                'model' => 'Corolla',
                'current_mileage' => 10000,
            ]);
            $ownOrders[] = $this->scheduledOrder([
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
            ])->id;
        }
        $foreignCustomer = Customer::create([
            'company_id' => $this->companyB->id,
            'full_name' => 'Cliente confidencial externo',
            'email' => 'privado@externo.example',
            'phone' => '999-444-3333',
        ]);
        $foreignVehicle = Vehicle::create([
            'customer_id' => $foreignCustomer->id,
            'plate' => 'EXTERNO999',
            'make' => 'Marca externa',
            'model' => 'Modelo externo',
            'current_mileage' => 10000,
        ]);
        foreach ([
            ['customer_id' => $foreignCustomer->id, 'vehicle_id' => $foreignVehicle->id],
            ['customer_id' => $foreignCustomer->id],
            ['vehicle_id' => $foreignVehicle->id],
            ['vehicle_id' => $vehicle->id],
            ['company_id' => $this->companyB->id],
            ['company_id' => $this->companyB->id, 'customer_id' => $foreignCustomer->id, 'vehicle_id' => $foreignVehicle->id],
        ] as $attributes) {
            $this->scheduledOrder([...$attributes, 'service_name_snapshot' => 'Servicio confidencial externo']);
        }
        $user = $role === 'company_admin' ? $this->adminA : $this->mechanicA;

        $response = $this->actingAs($user)->get(route('reminders.index'));

        $response->assertOk()->assertViewHas('reminders', function ($reminders) use ($ownOrders): bool {
            $this->assertSame(3, $reminders->total());
            $this->assertEqualsCanonicalizing($ownOrders, $reminders->getCollection()->modelKeys());

            return true;
        })->assertViewHas('summary', [
            'overdue' => 0, 'due_soon' => 3, 'upcoming' => 0, 'total_scheduled' => 3,
        ]);
        foreach (['Cliente confidencial externo', 'privado@externo.example', '999-444-3333', 'EXTERNO999', 'Servicio confidencial externo'] as $privateData) {
            $response->assertDontSee($privateData);
        }
        foreach (['customer' => $foreignCustomer->full_name, 'plate' => $foreignVehicle->plate, 'company_id' => $this->companyB->id] as $filter => $value) {
            $this->get(route('reminders.index', [$filter => $value]))
                ->assertOk()
                ->assertViewHas('reminders', fn ($reminders) => $reminders->total() === ($filter === 'company_id' ? 3 : 0));
        }
    }

    public function test_company_dashboard_and_vehicle_reminders_exclude_mismatched_customers(): void
    {
        $this->freezeTime();
        $valid = $this->scheduledOrder(['service_date' => now()->subMonth()]);
        $foreignCustomer = Customer::create([
            'company_id' => $this->companyB->id,
            'full_name' => 'Cliente de otra empresa',
        ]);
        $this->scheduledOrder([
            'customer_id' => $foreignCustomer->id,
            'service_date' => now(),
            'service_name_snapshot' => 'Servicio externo privado',
        ]);

        $this->actingAs($this->adminA)->get(route('company.dashboard'))
            ->assertOk()->assertDontSee('Cliente de otra empresa')->assertDontSee('Servicio externo privado')
            ->assertViewHas('serviceAlertsCount', 1)
            ->assertViewHas('upcomingReminders', fn ($reminders) => $reminders->total() === 1);
        $reminder = app(MaintenanceReminderService::class)->getVehicleReminder($this->vehicleA);
        $this->assertNotNull($reminder);
        $this->assertSame($valid->id, $reminder['order']->id);
    }

    #[TestWith(['customer', true])]
    #[TestWith(['company_admin', false])]
    #[TestWith(['mechanic', false])]
    public function test_reminders_deny_customers_and_staff_without_company(string $role, bool $withCompany): void
    {
        $user = User::factory()->create([
            'role' => $role,
            'company_id' => $withCompany ? $this->companyA->id : null,
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('reminders.index'))->assertForbidden();
    }

    public function test_super_admin_global_reminders_still_exclude_inconsistent_associations(): void
    {
        $this->scheduledOrder();
        $foreignCustomer = Customer::create(['company_id' => $this->companyB->id, 'full_name' => 'Cliente B']);
        $foreignVehicle = Vehicle::create([
            'customer_id' => $foreignCustomer->id, 'plate' => 'EMPRESAB', 'make' => 'Toyota', 'model' => 'Yaris',
        ]);
        $this->scheduledOrder([
            'company_id' => $this->companyB->id,
            'customer_id' => $foreignCustomer->id,
            'vehicle_id' => $foreignVehicle->id,
        ]);
        $this->scheduledOrder(['customer_id' => $foreignCustomer->id]);
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'company_id' => null]);

        $this->actingAs($superAdmin)->get(route('reminders.index'))->assertOk()
            ->assertViewHas('reminders', fn ($reminders) => $reminders->total() === 2)
            ->assertViewHas('summary', fn ($summary) => $summary['total_scheduled'] === 2);
    }

    #[TestWith(['0'])]
    #[TestWith(['invalid'])]
    public function test_processing_command_rejects_invalid_company_instead_of_using_global_scope(string $company): void
    {
        $this->artisan('maintenance:process-reminders', ['--company' => $company])
            ->expectsOutput('El ID de empresa debe ser un entero positivo.')
            ->assertFailed();
    }

    private function scheduledOrder(array $attributes = []): MaintenanceOrder
    {
        // Estas filas simulan datos históricos inconsistentes, anteriores a las validaciones del modelo.
        return MaintenanceOrder::withoutEvents(fn () => MaintenanceOrder::create([
            'company_id' => $this->companyA->id,
            'customer_id' => $this->customerA->id,
            'vehicle_id' => $this->vehicleA->id,
            'service_name_snapshot' => 'Servicio propio',
            'service_date' => now()->subDays(30),
            'current_mileage' => 10000,
            'next_due_date' => now()->addDays(2),
            'status' => 'completed',
            'maintenance_type' => 'preventive',
            ...$attributes,
        ]));
    }
}

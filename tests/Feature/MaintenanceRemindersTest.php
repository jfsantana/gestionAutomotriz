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
}

<?php

namespace Tests\Feature;

use App\Domain\Companies\Models\Company;
use App\Domain\Companies\Models\Customer;
use App\Domain\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerVehicleLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_defaults_to_non_premium_with_a_vehicle_limit_of_three(): void
    {
        $customer = $this->createCustomer();
        $customer->refresh();

        $this->assertSame(false, $customer->is_fleet_premium);
        $this->assertSame(3, $customer->vehicle_limit);
        $this->assertSame(false, $customer->hasReachedVehicleLimit());
    }

    public function test_premium_customer_uses_its_configured_vehicle_limit(): void
    {
        $customer = $this->createCustomer([
            'is_fleet_premium' => true,
            'vehicle_limit' => 1,
        ]);
        $this->createVehicles($customer, 1);

        $this->assertSame(true, $customer->is_fleet_premium);
        $this->assertSame(1, $customer->vehicle_limit);
        $this->assertSame(false, $customer->hasReachedVehicleLimit());

        $this->createVehicles($customer, 1);

        $this->assertSame(true, $customer->hasReachedVehicleLimit());
    }

    public function test_customer_has_not_exceeded_vehicle_limit_at_exact_limit(): void
    {
        $customer = $this->createCustomer();
        $this->createVehicles($customer, 3);

        $this->assertSame(false, $customer->hasReachedVehicleLimit());
    }

    public function test_customer_has_exceeded_vehicle_limit_when_associated_vehicle_count_is_greater(): void
    {
        $customer = $this->createCustomer();
        $this->createVehicles($customer, 4);

        $this->assertSame(true, $customer->hasReachedVehicleLimit());
    }

    public function test_premium_customer_with_an_unlimited_limit_has_not_reached_the_limit(): void
    {
        $customer = $this->createCustomer([
            'is_fleet_premium' => true,
            'vehicle_limit' => null,
        ]);
        $this->createVehicles($customer, 5);

        $this->assertSame(false, $customer->hasReachedVehicleLimit());
    }

    public function test_expired_premium_customer_uses_the_default_vehicle_limit(): void
    {
        $customer = $this->createCustomer([
            'is_fleet_premium' => true,
            'vehicle_limit' => null,
            'fleet_expires_at' => now()->subMinute(),
        ]);
        $this->createVehicles($customer, 4);

        $this->assertSame(false, $customer->isFleetPremiumActive());
        $this->assertSame(true, $customer->hasReachedVehicleLimit());
    }

    private function createCustomer(array $attributes = []): Customer
    {
        $company = Company::create([
            'name' => 'Taller de Prueba',
            'slug' => 'taller-de-prueba',
            'status' => 'active',
        ]);

        return Customer::create([
            'company_id' => $company->id,
            'full_name' => 'Cliente de Prueba',
            ...$attributes,
        ]);
    }

    private function createVehicles(Customer $customer, int $count): void
    {
        $existingVehicleCount = $customer->allVehicles()->count();

        for ($index = 1; $index <= $count; $index++) {
            Vehicle::create([
                'customer_id' => $customer->id,
                'plate' => "TEST{$customer->id}-".($existingVehicleCount + $index),
                'make' => 'Toyota',
                'model' => 'Corolla',
            ]);
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_subscription_rates', function (Blueprint $table) {
            $table->id();
            $table->string('customer_type', 30);
            $table->string('vehicle_type', 50);
            $table->decimal('monthly_price', 10, 2);
            $table->timestamps();

            $table->unique(['customer_type', 'vehicle_type']);
        });

        $rates = [
            'individual' => [
                'motocicleta' => '3.00',
                'carro' => '3.00',
                'camioneta' => '3.00',
                'van' => '4.00',
                'autobus' => '4.00',
                'camion' => '5.00',
                'chuto' => '5.00',
                'maquinaria_pesada' => '5.00',
            ],
            'fleet_owner' => [
                'motocicleta' => '3.00',
                'carro' => '4.00',
                'camioneta' => '4.00',
                'van' => '5.00',
                'autobus' => '5.00',
                'camion' => '5.00',
                'chuto' => '5.00',
                'maquinaria_pesada' => '5.00',
            ],
        ];
        $now = now();
        $rows = [];

        foreach ($rates as $customerType => $vehicleRates) {
            foreach ($vehicleRates as $vehicleType => $monthlyPrice) {
                $rows[] = [
                    'customer_type' => $customerType,
                    'vehicle_type' => $vehicleType,
                    'monthly_price' => $monthlyPrice,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('customer_subscription_rates')->insert($rows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_subscription_rates');
    }
};

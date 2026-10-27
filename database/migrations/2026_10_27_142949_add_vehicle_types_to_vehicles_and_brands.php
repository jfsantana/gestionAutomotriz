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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('vehicle_type')->nullable()->after('fuel_type');
        });

        Schema::table('vehicle_brands', function (Blueprint $table) {
            $table->string('vehicle_type')->default('carro')->after('name');
            $table->dropUnique('vehicle_brands_name_unique');
            $table->unique(['vehicle_type', 'name']);
        });

        $brandsByType = [
            'motocicleta' => [
                'Aprilia', 'Bajaj', 'Benelli', 'BMW', 'CF Moto', 'Ducati', 'Harley-Davidson',
                'Haojue', 'Hero', 'Honda', 'Kawasaki', 'KTM', 'Kymco', 'Italika', 'Royal Enfield',
                'Suzuki', 'SYM', 'Triumph', 'TVS', 'Vespa', 'Yamaha', 'Zontes',
            ],
            'camioneta' => [
                'BAIC', 'Changan', 'Chevrolet', 'Dongfeng', 'Fiat', 'Foton', 'Ford', 'Great Wall',
                'Haval', 'Honda', 'Hyundai', 'Isuzu', 'JAC', 'Jeep', 'Kia', 'Mazda', 'Mercedes-Benz',
                'Mitsubishi', 'Nissan', 'Peugeot', 'RAM', 'Renault', 'Subaru', 'Suzuki', 'Toyota',
                'Volkswagen',
            ],
            'camion' => [
                'Ashok Leyland', 'BYD', 'Chevrolet', 'DAF', 'Dongfeng', 'FAW', 'Foton', 'Freightliner',
                'Ford', 'Hino', 'Hyundai', 'International', 'Isuzu', 'Iveco', 'JAC', 'JMC', 'Kia',
                'Kenworth', 'Mack', 'Mercedes-Benz', 'Mitsubishi Fuso', 'Peterbilt', 'Renault Trucks',
                'Scania', 'Shacman', 'Sinotruk', 'Tata', 'UD Trucks', 'Volvo',
            ],
            'chuto' => [
                'DAF', 'Freightliner', 'International', 'Iveco', 'Kenworth', 'Mack', 'Mercedes-Benz',
                'Peterbilt', 'Renault Trucks', 'Scania', 'Volvo',
            ],
            'autobus' => [
                'BYD', 'Foton', 'Higer', 'Hino', 'Hyundai', 'Isuzu', 'Iveco', 'JAC', 'King Long',
                'Mercedes-Benz', 'Scania', 'Toyota', 'Volkswagen', 'Volvo', 'Yutong',
            ],
            'van' => [
                'Chevrolet', 'Fiat', 'Foton', 'Ford', 'Hyundai', 'Isuzu', 'JAC', 'Kia',
                'Mercedes-Benz', 'Mitsubishi', 'Nissan', 'Peugeot', 'Renault', 'Toyota', 'Volkswagen',
            ],
            'maquinaria_pesada' => [
                'Bobcat', 'CASE', 'Caterpillar', 'Doosan', 'Hitachi', 'Hyundai', 'JCB', 'John Deere',
                'Komatsu', 'Kubota', 'LiuGong', 'New Holland', 'SANY', 'SDLG', 'Shantui', 'Volvo CE',
                'XCMG',
            ],
        ];

        $now = now();
        $brandRows = [];

        foreach ($brandsByType as $vehicleType => $brands) {
            foreach ($brands as $brand) {
                $brandRows[] = [
                    'name' => $brand,
                    'vehicle_type' => $vehicleType,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('vehicle_brands')->insertOrIgnore($brandRows);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('vehicle_brands')->where('vehicle_type', '<>', 'carro')->delete();

        Schema::table('vehicle_brands', function (Blueprint $table) {
            $table->dropUnique(['vehicle_type', 'name']);
            $table->dropColumn('vehicle_type');
            $table->unique('name');
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('vehicle_type');
        });
    }
};

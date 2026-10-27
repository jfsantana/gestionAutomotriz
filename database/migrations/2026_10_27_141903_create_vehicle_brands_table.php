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
        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $brands = [
            'Acura',
            'Alfa Romeo',
            'Audi',
            'BAIC',
            'Bentley',
            'BMW',
            'Buick',
            'BYD',
            'Cadillac',
            'Changan',
            'Chery',
            'Chevrolet',
            'Chrysler',
            'Citroen',
            'Daewoo',
            'Daihatsu',
            'Dodge',
            'Dongfeng',
            'Fiat',
            'Ford',
            'Foton',
            'Geely',
            'Genesis',
            'GMC',
            'Great Wall',
            'Haval',
            'Honda',
            'Hyundai',
            'Infiniti',
            'Isuzu',
            'JAC',
            'Jaguar',
            'Jeep',
            'Jetour',
            'Kia',
            'Lada',
            'Land Rover',
            'Lexus',
            'Lincoln',
            'Maserati',
            'Mazda',
            'Mercedes-Benz',
            'MG',
            'MINI',
            'Mitsubishi',
            'Nissan',
            'Opel',
            'Peugeot',
            'Porsche',
            'RAM',
            'Renault',
            'SEAT',
            'Skoda',
            'Subaru',
            'Suzuki',
            'Tesla',
            'Toyota',
            'Volkswagen',
            'Volvo',
        ];

        $existingBrands = DB::table('vehicles')
            ->whereNotNull('make')
            ->where('make', '<>', '')
            ->distinct()
            ->pluck('make')
            ->all();

        $brandNames = array_unique([...$brands, ...$existingBrands]);
        $now = now();

        DB::table('vehicle_brands')->insertOrIgnore(array_map(
            fn (string $name): array => [
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $brandNames,
        ));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_brands');
    }
};

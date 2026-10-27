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
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedInteger('vehicle_limit')->nullable()->default(3)->change();
            $table->timestamp('fleet_expires_at')->nullable()->after('vehicle_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('customers')
            ->whereNull('vehicle_limit')
            ->update(['vehicle_limit' => 3]);

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('fleet_expires_at');
            $table->unsignedInteger('vehicle_limit')->default(3)->nullable(false)->change();
        });
    }
};

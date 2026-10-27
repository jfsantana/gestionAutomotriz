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
            $table->timestamp('fleet_trial_started_at')->nullable()->after('fleet_expires_at');
        });

        DB::table('pagos_clientes')
            ->where('status', 'pending')
            ->update(['status' => 'requested']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pagos_clientes')
            ->where('status', 'requested')
            ->update(['status' => 'pending']);

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('fleet_trial_started_at');
        });
    }
};

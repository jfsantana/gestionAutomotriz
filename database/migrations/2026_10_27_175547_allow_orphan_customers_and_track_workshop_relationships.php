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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('email_verification_required')->default(false)->after('email_verified_at');
        });

        foreach (['customers', 'vehicles', 'maintenance_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['company_id']);
                $table->unsignedBigInteger('company_id')->nullable()->change();
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }

        Schema::create('customer_workshop_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('ended_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ended_by_role', 30)->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'ended_at']);
            $table->index(['company_id', 'ended_at']);
        });

        Schema::create('workshop_association_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['customer_id', 'expires_at']);
        });

        DB::table('customers')
            ->whereNotNull('company_id')
            ->orderBy('id')
            ->chunkById(500, function ($customers): void {
                $now = now();
                $relationships = $customers->map(fn (object $customer): array => [
                    'customer_id' => $customer->id,
                    'company_id' => $customer->company_id,
                    'started_at' => $customer->created_at ?? $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('customer_workshop_relationships')->insert($relationships);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('customers')->whereNull('company_id')->exists()
            || DB::table('vehicles')->whereNull('company_id')->exists()
            || DB::table('maintenance_orders')->whereNull('company_id')->exists()) {
            throw new RuntimeException('No se puede revertir la migración mientras existan clientes, vehículos u órdenes sin taller.');
        }

        Schema::dropIfExists('workshop_association_invitations');
        Schema::dropIfExists('customer_workshop_relationships');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('email_verification_required');
        });

        foreach (['customers', 'vehicles', 'maintenance_orders'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['company_id']);
                $table->unsignedBigInteger('company_id')->nullable(false)->change();
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->default(0);
            $table->unsignedInteger('max_internal_users')->nullable();
            $table->unsignedInteger('max_mechanics')->nullable();
            $table->unsignedInteger('max_maintenance_orders_per_month')->nullable();
            $table->unsignedInteger('max_vehicles')->nullable();
            $table->unsignedInteger('duration_days')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('status', 30)->default('trial')->index(); // trial, active, expired, suspended, cancelled
            $table->string('billing_cycle', 30)->default('monthly'); // monthly, yearly, trial, custom
            $table->date('starts_at');
            $table->date('ends_at')->nullable()->index();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('renewed_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });

        Schema::create('company_subscription_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_subscription_id')->constrained('company_subscriptions')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('previous_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->string('action', 50); // created, renewed, suspended, expired, cancelled, reactivated, plan_changed
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscription_logs');
        Schema::dropIfExists('company_subscriptions');
        Schema::dropIfExists('subscription_plans');
    }
};

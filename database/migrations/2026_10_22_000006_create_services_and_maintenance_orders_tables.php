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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('slug', 150)->nullable();
            $table->string('maintenance_type', 30)->default('preventive'); // preventive, corrective
            $table->text('description')->nullable();
            $table->decimal('default_price', 10, 2)->nullable();
            $table->unsignedInteger('default_interval_days')->nullable();
            $table->unsignedInteger('default_interval_mileage')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'maintenance_type']);
        });

        Schema::create('maintenance_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->foreignId('mechanic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number', 50)->nullable()->index();
            $table->string('maintenance_type', 30)->default('preventive'); // preventive, corrective
            $table->string('service_name_snapshot', 150);
            $table->text('service_description_snapshot')->nullable();
            $table->date('service_date');
            $table->unsignedInteger('current_mileage');
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('technical_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->string('status', 30)->default('open')->index(); // open, in_progress, completed, cancelled
            $table->date('next_due_date')->nullable();
            $table->unsignedInteger('next_due_mileage')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['vehicle_id', 'service_date']);
        });

        Schema::create('maintenance_order_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_order_id')->constrained('maintenance_orders')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 50)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_order_photos');
        Schema::dropIfExists('maintenance_orders');
        Schema::dropIfExists('services');
    }
};

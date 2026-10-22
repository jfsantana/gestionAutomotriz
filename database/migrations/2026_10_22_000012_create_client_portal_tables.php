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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('company_id')->constrained('customers')->cascadeOnDelete();
            $table->string('account_status', 30)->default('ACTIVE')->after('is_active')->index(); // INVITED, ACTIVE, BLOCKED, DEACTIVATED
            $table->timestamp('activated_at')->nullable()->after('last_login_at');
        });

        Schema::create('client_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('customer_status', 30)->default('ACTIVE')->after('transfer_status')->index(); // ACTIVE, DECOMMISSIONED
        });

        Schema::table('maintenance_orders', function (Blueprint $table) {
            $table->string('origin', 30)->default('workshop')->after('status')->index(); // workshop, customer
            $table->boolean('is_verified')->default(true)->after('origin');
            $table->foreignId('registered_by_user_id')->nullable()->after('mechanic_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_orders', function (Blueprint $table) {
            $table->dropForeign(['registered_by_user_id']);
            $table->dropColumn(['origin', 'is_verified', 'registered_by_user_id']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('customer_status');
        });

        Schema::dropIfExists('client_invitations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'account_status', 'activated_at']);
        });
    }
};

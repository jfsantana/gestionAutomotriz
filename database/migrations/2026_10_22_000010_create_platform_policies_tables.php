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
        Schema::create('platform_policies', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->unique(); // e.g. 1.0, 1.1
            $table->string('title', 191);
            $table->longText('content');
            $table->date('effective_date');
            $table->boolean('is_active')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('user_policy_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('platform_policy_id')->constrained('platform_policies')->cascadeOnDelete();
            $table->string('policy_version', 20)->index();
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'platform_policy_id']);
            $table->index(['user_id', 'policy_version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_policy_acceptances');
        Schema::dropIfExists('platform_policies');
    }
};

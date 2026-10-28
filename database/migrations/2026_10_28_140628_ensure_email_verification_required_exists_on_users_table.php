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
        if (! Schema::hasColumn('users', 'email_verification_required')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('email_verification_required')->default(false)->after('email_verified_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The original migration owns this column; rolling back the repair must preserve its data.
    }
};

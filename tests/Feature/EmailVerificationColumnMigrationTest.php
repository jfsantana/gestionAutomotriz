<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailVerificationColumnMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_verification_column_is_restored_for_existing_users(): void
    {
        $user = User::factory()->create();
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('email_verification_required');
        });

        $migration = require database_path('migrations/2026_10_28_140628_ensure_email_verification_required_exists_on_users_table.php');
        $migration->up();

        $this->assertFalse($user->fresh()->email_verification_required);
        $user->forceFill(['email_verification_required' => true])->save();
        $this->assertTrue($user->fresh()->email_verification_required);
    }

    public function test_existing_verification_column_and_values_are_preserved(): void
    {
        $user = User::factory()->create(['email_verification_required' => true]);
        $migration = require database_path('migrations/2026_10_28_140628_ensure_email_verification_required_exists_on_users_table.php');

        $migration->up();
        $migration->down();

        $this->assertTrue($user->fresh()->email_verification_required);
    }
}

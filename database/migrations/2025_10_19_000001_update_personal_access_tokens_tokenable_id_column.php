<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure Sanctum tokenable_id supports string-based primary keys.
     */
    public function up(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            return;
        }

        DB::statement('ALTER TABLE personal_access_tokens MODIFY tokenable_id VARCHAR(255) NOT NULL');
    }

    /**
     * Revert the tokenable_id column to the original type.
     */
    public function down(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            return;
        }

        DB::statement('ALTER TABLE personal_access_tokens MODIFY tokenable_id BIGINT UNSIGNED NOT NULL');
    }
};

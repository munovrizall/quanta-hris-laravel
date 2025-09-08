<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cuti', function (Blueprint $table) {
            // Rename approved_by to approver_id
            $table->renameColumn('approved_by', 'approver_id');
            // Rename approved_at to processed_at
            $table->renameColumn('approved_at', 'processed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cuti', function (Blueprint $table) {
            // Rollback: rename back to original names
            $table->renameColumn('approver_id', 'approved_by');
            $table->renameColumn('processed_at', 'approved_at');
        });
    }
};
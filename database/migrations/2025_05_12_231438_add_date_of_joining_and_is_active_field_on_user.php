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
        // First, add the new columns
        Schema::table('users', function (Blueprint $table) {
            $table->date('date_of_joining')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('date_of_joining');
        });
        
        // Now rearrange the timestamp columns to be at the end
        Schema::table('users', function (Blueprint $table) {
            // Drop existing timestamp columns (IF they exist)
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
            if (Schema::hasColumn('users', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
            if (Schema::hasColumn('users', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('users', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
        
        // Re-add them at the end
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->timestamps(); // This adds both created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['date_of_joining', 'is_active']);
            // We don't need to undo the timestamp rearrangement
        });
    }
};
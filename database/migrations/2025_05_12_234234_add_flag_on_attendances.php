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
        // First, add the new columns
        Schema::table('attendances', function (Blueprint $table) {
            $table->double('hours_worked')->default(0)->after('latlon_out');
            $table->boolean('is_late')->default(false);
            $table->boolean('is_overtime')->default(false);
        });

        // Now rearrange the timestamp columns to be at the end
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('attendances', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });

        // Re-add them at the end
        Schema::table('attendances', function (Blueprint $table) {
            $table->timestamps(); // This adds both created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            //
        });
    }
};

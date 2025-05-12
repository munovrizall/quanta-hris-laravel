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
        Schema::create('overtimes', function (Blueprint $table) {
            $table->increments('overtime_id');
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->time('overtime_start_time');
            $table->time('overtime_end_time');
            $table->integer('overtime_hours');
            $table->enum('approval_status', ['approved', 'pending', 'rejected']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};

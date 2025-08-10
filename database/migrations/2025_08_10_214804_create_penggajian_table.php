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
        Schema::create('penggajian', function (Blueprint $table) {
            $table->string('penggajian_id', 6)->primary();
            $table->integer('periode_bulan');
            $table->integer('periode_tahun');
            $table->enum('status_penggajian', ['Draf', 'Diverifikasi', 'Disetujui', 'Ditolak'])->default('Draf');
            $table->string('verified_by', 5)->nullable();
            $table->string('approved_by', 5)->nullable();
            $table->string('processed_by', 5)->nullable();
            $table->text('catatan_penolakan_draf')->nullable();
            $table->timestamps();

            $table->foreign('verified_by')->references('karyawan_id')->on('karyawan');
            $table->foreign('approved_by')->references('karyawan_id')->on('karyawan');
            $table->foreign('processed_by')->references('karyawan_id')->on('karyawan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggajian');
    }
};

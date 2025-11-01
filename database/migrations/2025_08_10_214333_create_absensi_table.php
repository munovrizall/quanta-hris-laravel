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
        Schema::create('absensi', function (Blueprint $table) {
            $table->string('absensi_id', 8)->primary();
            $table->string('karyawan_id', 5);
            $table->string('cabang_id', 5);
            $table->date('tanggal');
            $table->dateTime('waktu_masuk');
            $table->dateTime('waktu_pulang')->nullable();
            $table->enum('status_masuk', ['Tepat Waktu', 'Telat'])->nullable();
            $table->enum('status_pulang', ['Tepat Waktu', 'Pulang Cepat'])->nullable();
            $table->time('durasi_telat')->nullable();
            $table->time('durasi_pulang_cepat')->nullable();
            $table->string('koordinat_masuk', 50);
            $table->string('koordinat_pulang', 50)->nullable();
            $table->string('foto_masuk', 100);
            $table->string('foto_pulang', 100)->nullable();
            $table->enum('status_absensi', ['Hadir', 'Tidak Tepat', 'Alfa', 'Izin', 'Cuti']);
            $table->timestamps();

            $table->foreign('karyawan_id')->references('karyawan_id')->on('karyawan')->onDelete('cascade');
            $table->foreign('cabang_id')->references('cabang_id')->on('cabang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};

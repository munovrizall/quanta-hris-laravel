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
        Schema::create('lembur', function (Blueprint $table) {
            $table->string('lembur_id', 6)->primary();
            $table->string('karyawan_id', 5);
            $table->string('absensi_id', 6);
            $table->date('tanggal_lembur');
            $table->time('durasi_lembur');
            $table->text('deskripsi_pekerjaan');
            $table->string('dokumen_pendukung', 255)->nullable();
            $table->enum('status_lembur', ['Diajukan', 'Disetujui', 'Ditolak'])->default('Diajukan');
            $table->text('alasan_penolakan')->nullable();
            $table->string('approved_by', 5)->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('karyawan_id')->references('karyawan_id')->on('karyawan')->onDelete('cascade');
            $table->foreign('absensi_id')->references('absensi_id')->on('absensi')->onDelete('cascade');
            $table->foreign('approved_by')->references('karyawan_id')->on('karyawan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lembur');
    }
};

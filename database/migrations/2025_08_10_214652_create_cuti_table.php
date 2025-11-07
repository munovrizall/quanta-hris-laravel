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
        Schema::create('cuti', function (Blueprint $table) {
            $table->string('cuti_id', 6)->primary();
            $table->string('karyawan_id', 5);
            $table->string('jenis_cuti', 50);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('keterangan');
            $table->string('dokumen_pendukung', 100)->nullable();
            $table->enum('status_cuti', ['Diajukan', 'Disetujui', 'Ditolak'])->default('Diajukan');
            $table->text('alasan_penolakan')->nullable();
            $table->string('approved_by', 5)->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('karyawan_id')->references('karyawan_id')->on('karyawan')->onDelete('cascade');
            $table->foreign('approved_by')->references('karyawan_id')->on('karyawan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuti');
    }
};

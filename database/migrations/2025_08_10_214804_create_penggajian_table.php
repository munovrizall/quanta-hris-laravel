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
            $table->string('penggajian_id', 10)->primary();

            $table->integer('periode_bulan');
            $table->integer('periode_tahun');
            $table->enum('status_penggajian', ['Draf', 'Diverifikasi', 'Disetujui', 'Ditolak'])->default('Draf');
            $table->text('catatan_penolakan_draf')->nullable();

            $table->string('karyawan_id', 5);
            $table->boolean('sudah_diproses')->default(false);
            $table->double('gaji_pokok')->default(0);
            $table->double('total_tunjangan')->default(0);
            $table->double('total_lembur')->default(0);
            $table->double('penghasilan_bruto')->default(0);
            $table->double('potongan_alfa')->default(0);
            $table->double('potongan_terlambat')->default(0);
            $table->double('potongan_bpjs')->default(0);
            $table->double('potongan_pph21')->default(0);
            $table->double('penyesuaian')->default(0);
            $table->text('catatan_penyesuaian')->nullable();
            $table->double('total_potongan')->default(0);
            $table->double('gaji_bersih')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('karyawan_id')->references('karyawan_id')->on('karyawan')->cascadeOnDelete();

            $table->index(['periode_bulan', 'periode_tahun']);
            $table->index('karyawan_id');
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
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
            $table->string('tabel_id', 10)->primary();

            $table->integer('periode_bulan');
            $table->integer('periode_tahun');
            $table->enum('status_penggajian', ['Draf', 'Diverifikasi', 'Disetujui', 'Ditolak'])->default('Draf');
            $table->string('verified_by', 5)->nullable();
            $table->string('approved_by', 5)->nullable();
            $table->string('processed_by', 5)->nullable();
            $table->text('catatan_penolakan_draf')->nullable();

            $table->string('karyawan_id', 5);
            $table->boolean('sudah_diproses')->default(false);
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('total_tunjangan', 15, 2)->default(0);
            $table->decimal('total_lembur', 15, 2)->default(0);
            $table->decimal('penghasilan_bruto', 15, 2)->default(0);
            $table->decimal('potongan_alfa', 15, 2)->default(0);
            $table->decimal('potongan_terlambat', 15, 2)->default(0);
            $table->decimal('potongan_bpjs', 15, 2)->default(0);
            $table->decimal('potongan_pph21', 15, 2)->default(0);
            $table->decimal('penyesuaian', 15, 2)->default(0);
            $table->text('catatan_penyesuaian')->nullable();
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('gaji_bersih', 15, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('verified_by')->references('karyawan_id')->on('karyawan');
            $table->foreign('approved_by')->references('karyawan_id')->on('karyawan');
            $table->foreign('processed_by')->references('karyawan_id')->on('karyawan');
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

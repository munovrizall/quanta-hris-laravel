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
        Schema::create('detail_penggajian', function (Blueprint $table) {
            $table->id(); // Primary Key auto-increment

            // --- Relasi ke tabel lain ---
            // Foreign Key ke tabel penggajian utama
            $table->string('penggajian_id');
            $table->foreign('penggajian_id')->references('penggajian_id')->on('penggajian')->onDelete('cascade');

            // Foreign Key ke tabel karyawan
            $table->string('karyawan_id');
            $table->foreign('karyawan_id')->references('karyawan_id')->on('karyawan')->onDelete('cascade');
            
            $table->boolean('sudah_diproses')->default(false);

            // --- Komponen Pendapatan ---
            // DECIMAL digunakan untuk presisi angka keuangan
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('total_tunjangan', 15, 2)->default(0);
            $table->decimal('total_lembur', 15, 2)->default(0);
            $table->decimal('penghasilan_bruto', 15, 2)->default(0);

            // --- Komponen Potongan ---
            $table->decimal('potongan_alfa', 15, 2)->default(0);
            $table->decimal('potongan_terlambat', 15, 2)->default(0);
            $table->decimal('potongan_bpjs', 15, 2)->default(0);
            $table->decimal('potongan_pph21', 15, 2)->default(0);

            // --- Kolom Kunci untuk Penyesuaian Manual ---
            // Bisa bernilai positif (bonus) atau negatif (potongan lain-lain)
            $table->decimal('penyesuaian', 15, 2)->signed()->default(0);
            $table->text('catatan_penyesuaian')->nullable();

            // --- Hasil Akhir ---
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('gaji_bersih', 15, 2)->default(0);

            // Timestamps standar & Soft Deletes untuk keamanan data
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_penggajian');
    }
};
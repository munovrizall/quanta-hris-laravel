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
        Schema::create('slip_gaji', function (Blueprint $table) {
            $table->string('slip_gaji_id', 6)->primary();
            $table->string('penggajian_id', 6);
            $table->string('karyawan_id', 5);
            $table->decimal('gaji_pokok', 15, 2);
            $table->decimal('total_tunjangan', 15, 2)->default(0);
            $table->decimal('total_insentif_lembur', 15, 2)->default(0);
            $table->decimal('total_potongan_pph21', 15, 2)->default(0);
            $table->decimal('total_potongan_bpjs', 15, 2)->default(0);
            $table->decimal('total_potongan_penalty', 15, 2)->default(0);
            $table->decimal('pendapatan_bersih', 15, 2);
            $table->timestamps();

            $table->foreign('penggajian_id')->references('penggajian_id')->on('penggajian')->onDelete('cascade');
            $table->foreign('karyawan_id')->references('karyawan_id')->on('karyawan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slip_gaji');
    }
};

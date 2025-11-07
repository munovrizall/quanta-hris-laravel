<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('golongan_ptkp', function (Blueprint $table) {
            // Diubah: Primary key menjadi string kustom (G01, G02, dst.)
            $table->string('golongan_ptkp_id', 3)->primary();
            $table->string('nama_golongan_ptkp', 10)->unique(); // Nama asli kembali digunakan
            $table->string('deskripsi', 30);
            $table->bigInteger('ptkp_tahunan');

            // Diubah: Foreign key merujuk ke primary key baru
            $table->string('kategori_ter_id', 3);
            $table->foreign('kategori_ter_id')->references('kategori_ter_id')->on('kategori_ter');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golongan_ptkp');
    }
};
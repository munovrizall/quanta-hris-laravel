<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tarif_ter', function (Blueprint $table) {
            // Diubah: Primary key menjadi string kustom (B001, B002, dst.)
            $table->string('tarif_ter_id', 4)->primary();

            // Diubah: Foreign key merujuk ke primary key baru
            $table->string('kategori_ter_id', 3);
            $table->foreign('kategori_ter_id')->references('kategori_ter_id')->on('kategori_ter');

            $table->bigInteger('batas_bawah');
            $table->bigInteger('batas_atas');
            $table->double('tarif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_ter');
    }
};
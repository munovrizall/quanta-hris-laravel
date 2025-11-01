<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kategori_ter', function (Blueprint $table) {
            // Diubah: Primary key menjadi string kustom (T01, T02, dst.)
            $table->string('kategori_ter_id', 3)->primary();
            $table->string('nama_kategori', 20);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_ter');
    }
};
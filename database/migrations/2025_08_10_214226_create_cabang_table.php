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
        Schema::create('cabang', function (Blueprint $table) {
            $table->string('cabang_id', 5)->primary();
            $table->string('perusahaan_id', 5);
            $table->string('nama_cabang', 255);
            $table->text('alamat');
            $table->double('latitude');
            $table->double('longitude');
            $table->integer('radius_lokasi')->comment('Dalam satuan meter');
            $table->timestamps();

            $table->foreign('perusahaan_id')->references('perusahaan_id')->on('perusahaan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cabang');
    }
};

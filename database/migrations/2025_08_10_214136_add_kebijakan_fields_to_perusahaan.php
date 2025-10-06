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
        Schema::table('perusahaan', function (Blueprint $table) {
            // Kebijakan Keterlambatan (sesuai aturan baru)
            $table->double('potongan_keterlambatan')->default(50000)->after('jam_pulang');

            // Konfigurasi Potongan BPJS Karyawan
            $table->double('persen_bpjs_kesehatan')->default(0.0100)->comment('Default 1%')->after('potongan_keterlambatan');
            $table->double('persen_bpjs_jht')->default(0.0200)->comment('Default 2%')->after('persen_bpjs_kesehatan');
            $table->double('persen_bpjs_jp')->default(0.0100)->comment('Default 1%')->after('persen_bpjs_jht');
            $table->bigInteger('batas_gaji_bpjs_kesehatan')->default(12000000)->after('persen_bpjs_jp');
            $table->bigInteger('batas_gaji_bpjs_pensiun')->default(10547400)->after('batas_gaji_bpjs_kesehatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropColumn([
                'potongan_keterlambatan',
                'persen_bpjs_kesehatan',
                'persen_bpjs_jht',
                'persen_bpjs_jp',
                'batas_gaji_bpjs_kesehatan',
                'batas_gaji_bpjs_pensiun',
            ]);
        });
    }
};
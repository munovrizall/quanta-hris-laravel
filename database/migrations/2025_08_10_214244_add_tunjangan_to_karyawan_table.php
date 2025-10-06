<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->double('tunjangan_jabatan')->default(0)->after('gaji_pokok');
            $table->double('tunjangan_makan_bulanan')->default(0)->after('tunjangan_jabatan');
            $table->double('tunjangan_transport_bulanan')->default(0)->after('tunjangan_makan_bulanan');
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropColumn(['tunjangan_jabatan', 'tunjangan_makan_bulanan', 'tunjangan_transport_bulanan']);
        });
    }
};
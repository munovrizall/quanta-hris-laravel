<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->decimal('tunjangan_jabatan', 15, 2)->default(0)->after('gaji_pokok');
            $table->decimal('tunjangan_makan_bulanan', 15, 2)->default(0)->after('tunjangan_jabatan');
            $table->decimal('tunjangan_transport_bulanan', 15, 2)->default(0)->after('tunjangan_makan_bulanan');
        });
    }

    public function down(): void
    {
        Schema::table('karyawan', function (Blueprint $table) {
            $table->dropColumn(['tunjangan_jabatan', 'tunjangan_makan_bulanan', 'tunjangan_transport_bulanan']);
        });
    }
};
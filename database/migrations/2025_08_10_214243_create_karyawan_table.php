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
        Schema::create('karyawan', function (Blueprint $table) {
            $table->string('karyawan_id', 5)->primary();
            $table->string('role_id', 3);
            $table->string('perusahaan_id', 5);
            $table->string('golongan_ptkp_id', 3);
            $table->string('nik', 20)->unique();
            $table->string('nama_lengkap', 255);
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->text('alamat');
            $table->string('nomor_telepon', 20);
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->string('jabatan', 100);
            $table->string('departemen', 100);
            $table->enum('status_kepegawaian', [
                'Tetap',
                'Kontrak',
                'Magang',
                'Freelance'
            ]);
            $table->date('tanggal_mulai_bekerja');
            $table->decimal('gaji_pokok', 15, 2);
            $table->string('nomor_rekening', 50);
            $table->string('nama_pemilik_rekening', 255);
            $table->string('nomor_bpjs_kesehatan', 50)->nullable();
            $table->text('face_embedding')->nullable();
            $table->timestamps();

            $table->foreign('role_id')->references('role_id')->on('roles');
            $table->foreign('perusahaan_id')->references('perusahaan_id')->on('perusahaan');
            $table->foreign('golongan_ptkp_id')->references('golongan_ptkp_id')->on('golongan_ptkp');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();

            // UBAH baris foreignId() menjadi string() yang sesuai dengan karyawan_id
            $table->string('user_id', 5)->nullable()->index();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();

            // Opsional tetapi sangat direkomendasikan: Tambahkan foreign key constraint
            $table->foreign('user_id')->references('karyawan_id')->on('karyawan')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawan');
    }
};

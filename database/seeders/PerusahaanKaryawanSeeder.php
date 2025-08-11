<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Perusahaan;
use App\Models\Cabang;
use App\Models\Karyawan;
use App\Models\Role;
use App\Models\GolonganPtkp;
use Illuminate\Support\Facades\Hash; // Jika Anda akan membuat User login

class PerusahaanKaryawanSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Perusahaan
        $perusahaan = Perusahaan::create([
            'perusahaan_id' => 'P0001',
            'nama_perusahaan' => 'PT. Teknologi Nusantara',
            'email' => 'contact@nusantara.tech',
            'nomor_telepon' => '0215550123',
            'jam_masuk' => '08:00:00',
            'jam_pulang' => '17:00:00',
        ]);

        // 2. Buat Cabang untuk Perusahaan tersebut
        $cabangUtama = Cabang::create([
            'cabang_id' => 'C0001',
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'nama_cabang' => 'Kantor Pusat Jakarta',
            'alamat' => 'Jl. Jend. Sudirman Kav. 52-53, Jakarta Selatan',
            'latitude' => -6.2245,
            'longitude' => 106.809,
            'radius_lokasi' => 100, // 100 meter
        ]);

        $cabangBandung = Cabang::create([
            'cabang_id' => 'C0002',
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'nama_cabang' => 'Kantor Cabang Bandung',
            'alamat' => 'Jl. Asia Afrika No.1, Bandung',
            'latitude' => -6.9218,
            'longitude' => 107.607,
            'radius_lokasi' => 150, // 150 meter
        ]);

        // 3. Ambil data master yang sudah ada=
        $ptkp = GolonganPtkp::first();

        // 4. Buat 1 Karyawan Manajer
        Karyawan::create([
            'karyawan_id' => 'K0001',
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'golongan_ptkp_id' => $ptkp->golongan_ptkp_id,
            'nik' => '3171010101800001',
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'admin@email.com', 
            'password' => 'admin', 
            'tanggal_lahir' => '1980-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Kebagusan Raya No. 10',
            'nomor_telepon' => '081234567890',
            'jabatan' => 'Manager HRD',
            'departemen' => 'Human Resources',
            'status_kepegawaian' => 'Tetap',
            'tanggal_mulai_bekerja' => '2015-05-10',
            'gaji_pokok' => 15000000,
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Budi Santoso',
        ]);

        // 5. Buat 20 Karyawan Staff secara dinamis
        for ($i = 2; $i <= 21; $i++) {
            Karyawan::factory()->create([
                'karyawan_id' => 'K' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'perusahaan_id' => $perusahaan->perusahaan_id,
                'golongan_ptkp_id' => GolonganPtkp::all()->random()->golongan_ptkp_id,
                // 'cabang_id' -> Asumsi karyawan bisa berada di salah satu cabang
                // Factory akan mengisi sisanya
            ]);
        }
    }
}
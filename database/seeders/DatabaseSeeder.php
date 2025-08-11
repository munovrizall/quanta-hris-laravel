<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Urutan pemanggilan sangat penting untuk menjaga integritas relasi
        $this->call([
            // 1. Buat data master yang tidak memiliki dependensi
            GolonganPtkpSeeder::class,
            
            // 2. Buat data perusahaan, cabang, dan semua karyawan terkait
            PerusahaanKaryawanSeeder::class,
            
            // 3. Buat data transaksional yang bergantung pada karyawan
            // TransaksiSeeder::class,
        ]);
    }
}
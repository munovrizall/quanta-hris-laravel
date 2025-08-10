<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Cuti;
use App\Models\Izin;
use Carbon\Carbon;
// Hapus 'use Illuminate\Support\Str;' karena tidak lagi digunakan

class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $staffKaryawan = Karyawan::where('role_id', 'STF')->get();
        $manager = Karyawan::where('role_id', 'MGR')->first();
        
        if ($staffKaryawan->isEmpty() || !$manager) {
            $this->command->info('Tidak ada karyawan staff atau manager, seeder transaksi dilewati.');
            return;
        }

        // Inisialisasi counter untuk ID sekuensial
        $absensiCounter = 1;
        $cutiCounter = 1;
        $izinCounter = 1;

        // Buat data absensi untuk 5 karyawan selama 10 hari terakhir
        foreach ($staffKaryawan->take(5) as $karyawan) {
            for ($i = 0; $i < 10; $i++) {
                Absensi::factory()->create([
                    // Gunakan counter untuk membuat ID sekuensial (e.g., AB0001, AB0002)
                    'absensi_id' => 'AB' . str_pad($absensiCounter, 4, '0', STR_PAD_LEFT),
                    'karyawan_id' => $karyawan->karyawan_id,
                    'cabang_id' => 'C0001', // Asumsi absensi di kantor pusat
                    'tanggal' => Carbon::now()->subDays($i)->toDateString(),
                ]);
                $absensiCounter++; // Naikkan counter setiap kali data dibuat
            }
        }

        // Buat 1 data Cuti yang disetujui
        Cuti::factory()->create([
            // Gunakan counter untuk membuat ID sekuensial (e.g., CT0001)
            'cuti_id' => 'CT' . str_pad($cutiCounter, 4, '0', STR_PAD_LEFT),
            'karyawan_id' => $staffKaryawan->random()->karyawan_id,
            'status_cuti' => 'Disetujui',
            'approved_by' => $manager->karyawan_id,
            'approved_at' => Carbon::now(),
        ]);
        $cutiCounter++;

        // Buat 1 data Izin yang masih diajukan
        Izin::factory()->create([
            // Gunakan counter untuk membuat ID sekuensial (e.g., IZ0001)
            'izin_id' => 'IZ' . str_pad($izinCounter, 4, '0', STR_PAD_LEFT),
            'karyawan_id' => $staffKaryawan->random()->karyawan_id,
            'status_izin' => 'Diajukan',
        ]);
        $izinCounter++;
    }
}
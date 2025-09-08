<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Lembur;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;
use Faker\Factory as Faker;

class LemburSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // Ambil semua karyawan
        $karyawans = Karyawan::all();

        // Ambil data absensi yang ada
        $absensis = Absensi::where('status_absensi', 'Hadir')
            ->whereTime('waktu_pulang', '>', '17:30:00') // Yang pulang lebih dari jam 17:30
            ->get();

        // Ambil approver (Manager HRD)
        $approver = Karyawan::whereHas('role', function ($query) {
            $query->where('name', 'Manager HRD');
        })->first();

        if ($karyawans->isEmpty() || $absensis->isEmpty()) {
            $this->command->error('Tidak ada data karyawan atau absensi yang memadai. Jalankan seeder lain terlebih dahulu.');
            return;
        }

        if (!$approver) {
            $this->command->error('Tidak ada Manager HRD sebagai approver. Pastikan data karyawan dengan role Manager HRD tersedia.');
            return;
        }

        $lemburData = [];
        $counter = 1;

        // Daftar deskripsi pekerjaan lembur yang realistis
        $deskripsiPekerjaan = [
            'Menyelesaikan laporan bulanan yang harus diserahkan besok pagi',
            'Menghadiri meeting dengan klien dari luar kota via video conference',
            'Menyelesaikan presentasi untuk tender proyek besar',
            'Backup dan maintenance sistem database perusahaan',
            'Menyelesaikan audit internal yang deadline hari ini',
            'Koordinasi dengan tim vendor untuk implementasi sistem baru',
            'Menyelesaikan rekonsiliasi keuangan bulan ini',
            'Preparing dokumen untuk audit eksternal minggu depan',
            'Mengatasi issue urgent dari customer yang tidak bisa ditunda',
            'Menyelesaikan training material untuk karyawan baru',
            'Mengerjakan proposal bisnis untuk ekspansi cabang baru',
            'Troubleshooting masalah jaringan yang tiba-tiba down',
            'Menyelesaikan laporan pajak yang deadline hari ini',
            'Koordinasi dengan tim marketing untuk campaign product launch',
            'Mengerjakan kontrak kerjasama dengan partner strategis',
            'Menyelesaikan inventory count untuk closing bulanan',
            'Preparing untuk presentation ke board of directors besok',
            'Mengatasi komplain customer VIP yang membutuhkan handling khusus',
            'Menyelesaikan payroll processing yang harus selesai malam ini',
            'Mengerjakan business continuity plan untuk quarter depan'
        ];

        // Generate data lembur (100 data)
        foreach ($absensis->take(120) as $index => $absensi) {
            if ($counter > 100)
                break;

            // 80% kemungkinan lembur disetujui, 15% ditolak, 5% masih diajukan
            $statusProbability = $faker->numberBetween(1, 100);

            if ($statusProbability <= 80) {
                $statusLembur = 'Disetujui';
                $processedAt = $absensi->waktu_pulang->addMinutes($faker->numberBetween(60, 180)); // 1-3 jam setelah pulang
                $alasanPenolakan = null;
            } elseif ($statusProbability <= 95) {
                $statusLembur = 'Ditolak';
                $processedAt = $absensi->waktu_pulang->addMinutes($faker->numberBetween(60, 180));
                $alasanPenolakan = $faker->randomElement([
                    'Pekerjaan tersebut tidak termasuk kategori urgent dan dapat dikerjakan pada jam kerja normal',
                    'Budget lembur bulan ini sudah habis, mohon koordinasi untuk bulan depan',
                    'Tidak ada approval dari atasan langsung untuk pekerjaan ini',
                    'Pekerjaan dapat didelegasikan ke tim yang bertugas shift malam',
                    'Kurang detail dalam menjelaskan urgensi pekerjaan yang dilakukan'
                ]);
            } else {
                $statusLembur = 'Diajukan';
                $processedAt = null;
                $alasanPenolakan = null;
            }

            // Hitung durasi lembur berdasarkan waktu pulang
            $jamPulangStandar = Carbon::createFromFormat('H:i:s', '17:00:00');
            $waktuPulangAktual = Carbon::createFromFormat('H:i:s', $absensi->waktu_pulang->format('H:i:s'));
            $durasiLemburMenit = $jamPulangStandar->diffInMinutes($waktuPulangAktual);

            // Convert ke format time untuk database
            $durasiJam = intval($durasiLemburMenit / 60);
            $sisaMenit = $durasiLemburMenit % 60;
            $durasiLembur = sprintf('%02d:%02d:00', $durasiJam, $sisaMenit);

            // Pilih dokumen pendukung secara random (60% ada dokumen)
            $dokumenPendukung = null;
            if ($faker->boolean(60)) {
                $dokumenPendukung = 'lembur/dokumen_' . $faker->randomNumber(6) . '.pdf';
            }

            $lemburData[] = [
                'lembur_id' => 'LB' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                'karyawan_id' => $absensi->karyawan_id,
                'absensi_id' => $absensi->absensi_id,
                'tanggal_lembur' => $absensi->tanggal,
                'durasi_lembur' => $durasiLembur,
                'deskripsi_pekerjaan' => $faker->randomElement($deskripsiPekerjaan),
                'dokumen_pendukung' => $dokumenPendukung,
                'status_lembur' => $statusLembur,
                'alasan_penolakan' => $alasanPenolakan,
                'approver_id' => $statusLembur !== 'Diajukan' ? $approver->karyawan_id : null,
                'processed_at' => $processedAt,
                'created_at' => $absensi->waktu_pulang->addMinutes($faker->numberBetween(5, 30)), // Dibuat 5-30 menit setelah pulang
                'updated_at' => $processedAt ?? $absensi->waktu_pulang->addMinutes($faker->numberBetween(5, 30)),
            ];

            $counter++;
        }

        // Insert data dalam batch
        foreach (array_chunk($lemburData, 50) as $chunk) {
            Lembur::insert($chunk);
        }

        $this->command->info('Berhasil membuat ' . count($lemburData) . ' data lembur');
        $this->command->info('Status: Disetujui (~80%), Ditolak (~15%), Diajukan (~5%)');
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cuti;
use App\Models\Karyawan;
use Carbon\Carbon;
use Faker\Factory as Faker;

class CutiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // Ambil semua karyawan
        $karyawans = Karyawan::all();

        // Ambil approver (Manager HRD)
        $approver = Karyawan::whereHas('role', function ($query) {
            $query->where('name', 'Manager HRD');
        })->first();

        if ($karyawans->isEmpty()) {
            $this->command->error('Tidak ada data karyawan. Jalankan PerusahaanKaryawanSeeder terlebih dahulu.');
            return;
        }

        if (!$approver) {
            $this->command->error('Tidak ada Manager HRD sebagai approver. Pastikan data karyawan dengan role Manager HRD tersedia.');
            return;
        }

        // ✅ CLEAR existing cuti data untuk menghindari duplikasi
        $this->command->info('Clearing existing cuti data...');
        Cuti::truncate();

        // ✅ UPDATE: Sesuaikan dengan periode yang sama dengan AbsensiSeeder
        $startDate = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $cutiData = [];
        $counter = 1;

        // Daftar jenis cuti dengan probabilitas
        $jenisCutiProbability = [
            'Cuti Tahunan' => 40,      // 40%
            'Cuti Sakit' => 25,        // 25%
            'Cuti Menikah' => 5,       // 5%
            'Cuti Melahirkan' => 5,    // 5%
            'Cuti Besar' => 10,        // 10%
            'Cuti Khusus' => 10,       // 10%
            'Cuti Tanpa Gaji' => 5,    // 5%
        ];

        // Keterangan yang realistis berdasarkan jenis cuti
        $keteranganPerJenis = [
            // ...existing code... (sama seperti sebelumnya)
            'Cuti Tahunan' => [
                'Berlibur bersama keluarga ke Bali selama 5 hari',
                'Mengambil cuti tahunan untuk istirahat dan refreshing',
                'Berlibur ke kampung halaman untuk silaturahmi',
                'Rencana liburan bersama keluarga ke Yogyakarta',
                'Mengambil cuti untuk quality time dengan keluarga',
                'Berlibur ke Bandung untuk mengunjungi saudara',
                'Cuti tahunan untuk wedding anniversary',
            ],
            'Cuti Sakit' => [
                'Sakit demam tinggi dan perlu istirahat total',
                'Mengalami flu berat dan batuk yang tidak sembuh-sembuh',
                'Sakit maag dan perlu bed rest',
                'Mengalami migrain yang cukup parah',
                'Sakit pinggang dan perlu terapi fisik',
                'Demam berdarah dan dirawat di rumah sakit',
                'Sakit gigi yang memerlukan tindakan operasi kecil',
            ],
            'Cuti Menikah' => [
                'Pernikahan sendiri dan perlu persiapan yang matang',
                'Menikah dengan pasangan dan honeymoon ke Lombok',
                'Pernikahan dan resepsi di kampung halaman',
            ],
            'Cuti Melahirkan' => [
                'Melahirkan anak pertama dan perlu masa recovery',
                'Melahirkan anak kedua via caesar dan perlu istirahat panjang',
                'Cuti melahirkan untuk menyusui dan merawat bayi',
            ],
            'Cuti Besar' => [
                'Mengambil cuti besar untuk renovasi rumah',
                'Cuti besar untuk mengurus orang tua yang sakit',
                'Cuti panjang untuk menyelesaikan kuliah S2',
            ],
            'Cuti Khusus' => [
                'Menghadiri pemakaman kakek di kampung halaman',
                'Mengurus orang tua yang dirawat di rumah sakit',
                'Menghadiri wisuda adik di luar kota',
                'Mengurus surat-surat penting di kampung halaman',
                'Menghadiri pernikahan saudara di Surabaya',
            ],
            'Cuti Tanpa Gaji' => [
                'Mengambil cuti tanpa gaji untuk mengurus bisnis sampingan',
                'Cuti tanpa gaji untuk merawat anak yang sakit keras',
                'Mengambil break untuk mental health dan recovery',
            ]
        ];

        // ✅ GENERATE: Setiap karyawan berpotensi punya 1-2 periode cuti dalam 2 bulan
        foreach ($karyawans as $karyawan) {
            // 60% chance karyawan punya cuti dalam periode ini (turun dari 70%)
            if (!$faker->boolean(60))
                continue;

            $jumlahCuti = $faker->numberBetween(1, 2); // 1-2 periode cuti per karyawan

            for ($c = 0; $c < $jumlahCuti; $c++) {
                // Tentukan jenis cuti berdasarkan probabilitas
                $randomNum = $faker->numberBetween(1, 100);
                $jenisCuti = 'Cuti Tahunan'; // default
                $cumulativeProbability = 0;

                foreach ($jenisCutiProbability as $jenis => $probability) {
                    $cumulativeProbability += $probability;
                    if ($randomNum <= $cumulativeProbability) {
                        $jenisCuti = $jenis;
                        break;
                    }
                }

                // ✅ GENERATE tanggal cuti dalam periode yang benar
                $tanggalMulai = $faker->dateTimeBetween($startDate, $endDate->copy()->subDays(7)); // Leave some buffer

                // Pastikan tanggal mulai adalah hari kerja (Senin-Jumat)
                while (in_array((int) $tanggalMulai->format('N'), [6, 7])) { // 6=Saturday, 7=Sunday
                    $tanggalMulai = $faker->dateTimeBetween($startDate, $endDate->copy()->subDays(7));
                }

                // ✅ SHORTER DURATION: Tentukan durasi yang lebih pendek untuk 2 bulan
                $durasi = match ($jenisCuti) {
                    'Cuti Tahunan' => $faker->numberBetween(2, 4),      // 2-4 hari
                    'Cuti Sakit' => $faker->numberBetween(1, 3),        // 1-3 hari
                    'Cuti Menikah' => $faker->numberBetween(3, 5),      // 3-5 hari
                    'Cuti Melahirkan' => $faker->numberBetween(14, 30), // 2-4 minggu (reduced)
                    'Cuti Besar' => $faker->numberBetween(5, 10),       // 1 minggu (reduced)
                    'Cuti Khusus' => $faker->numberBetween(1, 3),       // 1-3 hari
                    'Cuti Tanpa Gaji' => $faker->numberBetween(2, 5),   // 2-5 hari
                    default => $faker->numberBetween(1, 3)
                };

                // ✅ SKIP WEEKENDS in duration calculation
                $tanggalSelesai = Carbon::parse($tanggalMulai); // ✅ Convert DateTime to Carbon
                $dayCounter = 0;

                while ($dayCounter < $durasi) {
                    $tanggalSelesai->addDay();
                    if (!$tanggalSelesai->isWeekend()) {
                        $dayCounter++;
                    }
                    // Safety check
                    if ($tanggalSelesai->gt($endDate)) {
                        $tanggalSelesai = $endDate->copy();
                        break;
                    }
                }

                // ✅ IMPROVED STATUS LOGIC: Hanya yang disetujui yang akan jadi absensi
                // 90% disetujui (hanya yang disetujui yang akan mempengaruhi absensi)
                // 7% ditolak, 3% masih diajukan
                $statusProbability = $faker->numberBetween(1, 100);

                if ($statusProbability <= 90) { // ✅ INCREASED approval rate
                    $statusCuti = 'Disetujui';
                    $processedAt = (clone $tanggalMulai)->modify('-' . $faker->numberBetween(1, 7) . ' days');
                    $alasanPenolakan = null;
                } elseif ($statusProbability <= 97) {
                    $statusCuti = 'Ditolak';
                    $processedAt = (clone $tanggalMulai)->modify('-' . $faker->numberBetween(1, 5) . ' days');
                    $alasanPenolakan = $faker->randomElement([
                        'Periode cuti bertepatan dengan high season perusahaan',
                        'Kuota cuti tahunan untuk periode ini sudah habis',
                        'Tidak ada backup yang memadai untuk mengcover pekerjaan',
                        'Dokumen pendukung belum lengkap',
                        'Jadwal cuti bertepatan dengan project deadline penting',
                        'Tim sedang dalam kondisi understaffed'
                    ]);
                } else {
                    $statusCuti = 'Diajukan';
                    $processedAt = null;
                    $alasanPenolakan = null;
                }

                // Pilih keterangan berdasarkan jenis cuti
                $keterangan = $faker->randomElement($keteranganPerJenis[$jenisCuti]);

                // 40% ada dokumen pendukung
                $dokumenPendukung = null;
                if ($faker->boolean(40)) {
                    $dokumenSuffix = match ($jenisCuti) {
                        'Cuti Sakit' => 'surat_dokter',
                        'Cuti Menikah' => 'undangan_nikah',
                        'Cuti Melahirkan' => 'surat_dokter_kandungan',
                        'Cuti Khusus' => 'surat_kematian',
                        default => 'surat_pendukung'
                    };
                    $dokumenPendukung = 'cuti/' . $dokumenSuffix . '_' . $faker->randomNumber(6) . '.pdf';
                }

                $cutiData[] = [
                    'cuti_id' => 'CT' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                    'karyawan_id' => $karyawan->karyawan_id,
                    'jenis_cuti' => $jenisCuti,
                    'tanggal_mulai' => $tanggalMulai->format('Y-m-d'),
                    'tanggal_selesai' => $tanggalSelesai->format('Y-m-d'),
                    'keterangan' => $keterangan,
                    'dokumen_pendukung' => $dokumenPendukung,
                    'status_cuti' => $statusCuti,
                    'alasan_penolakan' => $alasanPenolakan,
                    'approver_id' => $statusCuti !== 'Diajukan' ? $approver->karyawan_id : null,
                    'processed_at' => $processedAt?->format('Y-m-d H:i:s'),
                    'created_at' => (clone $tanggalMulai)->modify('-' . $faker->numberBetween(3, 14) . ' days'),
                    'updated_at' => $processedAt?->format('Y-m-d H:i:s') ?? (clone $tanggalMulai)->modify('-' . $faker->numberBetween(3, 14) . ' days'),
                ];

                $counter++;
            }
        }

        // Insert data dalam batch
        foreach (array_chunk($cutiData, 50) as $chunk) {
            Cuti::insert($chunk);
        }

        $disetujuiCount = collect($cutiData)->where('status_cuti', 'Disetujui')->count();
        $ditolakCount = collect($cutiData)->where('status_cuti', 'Ditolak')->count();
        $diajukanCount = collect($cutiData)->where('status_cuti', 'Diajukan')->count();

        $this->command->info('Berhasil membuat ' . count($cutiData) . ' data cuti');
        $this->command->info('Periode: ' . $startDate->format('d-m-Y') . ' sampai ' . $endDate->format('d-m-Y'));
        $this->command->info("Status: Disetujui ({$disetujuiCount}), Ditolak ({$ditolakCount}), Diajukan ({$diajukanCount})");
        $this->command->info("⚠️  HANYA CUTI YANG DISETUJUI ({$disetujuiCount}) YANG AKAN MEMPENGARUHI ABSENSI!");
    }
}
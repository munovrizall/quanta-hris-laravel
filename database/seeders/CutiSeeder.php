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

        // Generate 100 data cuti
        for ($i = 1; $i <= 100; $i++) {
            // Pilih karyawan secara random
            $karyawan = $karyawans->random();

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

            // Generate tanggal cuti (dalam 3 bulan terakhir sampai 1 bulan ke depan)
            $tanggalMulai = $faker->dateTimeBetween('-3 months', '+1 month');

            // Tentukan durasi berdasarkan jenis cuti
            $durasi = match ($jenisCuti) {
                'Cuti Tahunan' => $faker->numberBetween(2, 7),      // 2-7 hari
                'Cuti Sakit' => $faker->numberBetween(1, 5),        // 1-5 hari
                'Cuti Menikah' => $faker->numberBetween(3, 7),      // 3-7 hari
                'Cuti Melahirkan' => $faker->numberBetween(90, 180), // 3-6 bulan
                'Cuti Besar' => $faker->numberBetween(14, 30),      // 2-4 minggu
                'Cuti Khusus' => $faker->numberBetween(1, 3),       // 1-3 hari
                'Cuti Tanpa Gaji' => $faker->numberBetween(7, 14),  // 1-2 minggu
                default => $faker->numberBetween(1, 3)
            };

            $tanggalSelesai = (clone $tanggalMulai)->modify("+{$durasi} days");

            // 85% disetujui, 10% ditolak, 5% masih diajukan
            $statusProbability = $faker->numberBetween(1, 100);

            if ($statusProbability <= 85) {
                $statusCuti = 'Disetujui';
                $processedAt = (clone $tanggalMulai)->modify('-' . $faker->numberBetween(1, 7) . ' days'); // Diproses 1-7 hari sebelum cuti
                $alasanPenolakan = null;
            } elseif ($statusProbability <= 95) {
                $statusCuti = 'Ditolak';
                $processedAt = (clone $tanggalMulai)->modify('-' . $faker->numberBetween(1, 5) . ' days');
                $alasanPenolakan = $faker->randomElement([
                    'Periode cuti bertepatan dengan high season perusahaan, mohon diajukan ulang di periode lain',
                    'Kuota cuti tahunan untuk periode ini sudah habis',
                    'Tidak ada backup yang memadai untuk mengcover pekerjaan selama cuti',
                    'Dokumen pendukung belum lengkap, silakan lengkapi terlebih dahulu',
                    'Jadwal cuti bertepatan dengan project deadline yang sangat penting',
                    'Tim sedang dalam kondisi understaffed, mohon ditunda sampai kondisi normal'
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
                'created_at' => (clone $tanggalMulai)->modify('-' . $faker->numberBetween(3, 14) . ' days'), // Dibuat 3-14 hari sebelum cuti
                'updated_at' => $processedAt?->format('Y-m-d H:i:s') ?? (clone $tanggalMulai)->modify('-' . $faker->numberBetween(3, 14) . ' days'),
            ];

            $counter++;
        }

        // Insert data dalam batch
        foreach (array_chunk($cutiData, 50) as $chunk) {
            Cuti::insert($chunk);
        }

        $this->command->info('Berhasil membuat ' . count($cutiData) . ' data cuti');
        $this->command->info('Status: Disetujui (~85%), Ditolak (~10%), Diajukan (~5%)');
        $this->command->info('Jenis Cuti: Tahunan (40%), Sakit (25%), Khusus (10%), Besar (10%), dst.');
    }
}
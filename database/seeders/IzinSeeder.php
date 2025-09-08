<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Izin;
use App\Models\Karyawan;
use Carbon\Carbon;
use Faker\Factory as Faker;

class IzinSeeder extends Seeder
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

        $izinData = [];
        $counter = 1;

        // Daftar jenis izin dengan probabilitas
        $jenisIzinProbability = [
            'Izin Keperluan Keluarga' => 20,      // 20%
            'Izin Keperluan Pribadi' => 15,       // 15%
            'Izin Sakit' => 18,                   // 18%
            'Izin Datang Terlambat' => 12,        // 12%
            'Izin Pulang Lebih Awal' => 10,       // 10%
            'Izin Keluar Kantor' => 8,            // 8%
            'Izin Menghadiri Acara' => 7,         // 7%
            'Izin Keperluan Dinas' => 5,          // 5%
            'Izin Tidak Masuk' => 3,              // 3%
            'Izin Cuti Mendadak' => 2,            // 2%
        ];

        // Keterangan yang realistis berdasarkan jenis izin
        $keteranganPerJenis = [
            'Izin Keperluan Keluarga' => [
                'Mengantarkan anak ke dokter karena demam tinggi',
                'Mengurus orang tua yang sakit di rumah sakit',
                'Menghadiri acara pernikahan saudara kandung',
                'Mengurus keperluan sekolah anak yang urgent',
                'Mengantar istri melahirkan ke rumah sakit',
                'Menjaga anak yang sakit di rumah',
                'Menghadiri pemakaman keluarga dekat',
                'Mengurus administrasi keluarga yang mendesak',
            ],
            'Izin Keperluan Pribadi' => [
                'Mengurus perpanjangan SIM yang akan expired',
                'Mengurus dokumen penting di kantor pemerintahan',
                'Menghadiri interview kerja untuk pasangan',
                'Mengurus kredit rumah di bank',
                'Medical check-up rutin tahunan',
                'Mengurus BPJS kesehatan yang bermasalah',
                'Menghadiri sidang pengadilan sebagai saksi',
                'Mengurus asuransi yang claim',
            ],
            'Izin Sakit' => [
                'Merasa tidak enak badan dan demam sejak pagi',
                'Sakit kepala yang sangat mengganggu aktivitas',
                'Sakit perut dan mual-mual setelah makan',
                'Flu berat dengan batuk yang tidak berhenti',
                'Migrain akut yang membutuhkan istirahat total',
                'Sakit gigi yang sudah tidak tertahankan',
                'Diare dan muntah-muntah sejak dini hari',
                'Vertigo yang membuat tidak bisa berkendara',
            ],
            'Izin Datang Terlambat' => [
                'Terjebak macet parah di tol dalam kota',
                'Motor mogok di jalan dan harus diperbaiki',
                'Kecelakaan ringan di perjalanan ke kantor',
                'Hujan deras yang menyebabkan banjir di jalan',
                'Mengantarkan anak ke sekolah karena emergency',
                'KRL terlambat dan padat luar biasa',
                'Ban mobil bocor di tengah jalan',
                'Terlambat bangun karena alarm tidak berbunyi',
            ],
            'Izin Pulang Lebih Awal' => [
                'Anak sakit dan harus dijemput dari sekolah',
                'Ada meeting penting dengan bank untuk KPR',
                'Appointment dengan dokter yang sudah lama dijadwalkan',
                'Mengurus administrasi yang hanya buka sampai sore',
                'Emergency keluarga yang membutuhkan kehadiran',
                'Kondisi badan kurang fit dan perlu istirahat',
                'Menghadiri acara keluarga yang tidak bisa ditunda',
                'Ada tukang yang datang untuk perbaikan rumah',
            ],
            'Izin Keluar Kantor' => [
                'Meeting dengan klien di lokasi proyek',
                'Mengambil dokumen penting di kantor cabang',
                'Mengurus pembayaran vendor di bank',
                'Survey lokasi untuk keperluan kerja',
                'Menghadiri training eksternal di hotel',
                'Meeting dengan supplier di kantor mereka',
                'Mengambil sample produk di gudang pusat',
                'Koordinasi dengan tim lapangan di site',
            ],
            'Izin Menghadiri Acara' => [
                'Menghadiri wisuda adik di universitas',
                'Acara pernikahan teman dekat dari kuliah',
                'Seminar nasional yang berkaitan dengan pekerjaan',
                'Workshop pengembangan skill yang mandatory',
                'Acara launching produk partner bisnis',
                'Conference industri yang penting untuk karir',
                'Acara charity yang diadakan yayasan keluarga',
                'Gathering alumni yang sudah lama direncanakan',
            ],
            'Izin Keperluan Dinas' => [
                'Audit internal di kantor cabang lain',
                'Representasi perusahaan di acara industry',
                'Training mandatory dari head office',
                'Meeting dengan regulator pemerintah',
                'Koordinasi proyek dengan tim eksternal',
                'Presentasi proposal ke calon investor',
                'Monitoring progress proyek di lapangan',
                'Due diligence untuk merger dan akuisisi',
            ],
            'Izin Tidak Masuk' => [
                'Kondisi cuaca ekstrem yang berbahaya',
                'Transportasi umum mogok total di area rumah',
                'Emergency keluarga yang sangat urgent',
                'Kondisi kesehatan yang tidak memungkinkan',
                'Bencana alam ringan di area tempat tinggal',
                'Blackout total di area rumah dan kantor',
                'Demonstrasi besar yang tutup akses jalan',
                'Karantina mandiri karena kontak COVID',
            ],
            'Izin Cuti Mendadak' => [
                'Keluarga dekat meninggal dunia secara mendadak',
                'Kecelakaan keluarga yang membutuhkan pendampingan',
                'Bencana alam yang menimpa rumah keluarga',
                'Emergency medis yang memerlukan operasi',
                'Situasi force majeure yang tidak bisa dihindari',
            ],
        ];

        // Generate 100 data izin
        for ($i = 1; $i <= 100; $i++) {
            // Pilih karyawan secara random
            $karyawan = $karyawans->random();

            // Tentukan jenis izin berdasarkan probabilitas
            $randomNum = $faker->numberBetween(1, 100);
            $jenisIzin = 'Izin Keperluan Keluarga'; // default
            $cumulativeProbability = 0;

            foreach ($jenisIzinProbability as $jenis => $probability) {
                $cumulativeProbability += $probability;
                if ($randomNum <= $cumulativeProbability) {
                    $jenisIzin = $jenis;
                    break;
                }
            }

            // Generate tanggal izin (dalam 3 bulan terakhir sampai 1 bulan ke depan)
            $tanggalMulai = $faker->dateTimeBetween('-3 months', '+1 month');

            // Tentukan durasi berdasarkan jenis izin
            $durasi = match ($jenisIzin) {
                'Izin Keperluan Keluarga' => $faker->numberBetween(1, 3),      // 1-3 hari
                'Izin Keperluan Pribadi' => $faker->numberBetween(1, 2),       // 1-2 hari
                'Izin Sakit' => $faker->numberBetween(1, 3),                   // 1-3 hari
                'Izin Datang Terlambat' => 0,                                  // Same day
                'Izin Pulang Lebih Awal' => 0,                                 // Same day
                'Izin Keluar Kantor' => 0,                                     // Same day
                'Izin Menghadiri Acara' => $faker->numberBetween(1, 2),        // 1-2 hari
                'Izin Keperluan Dinas' => $faker->numberBetween(1, 3),         // 1-3 hari
                'Izin Tidak Masuk' => $faker->numberBetween(1, 2),             // 1-2 hari
                'Izin Cuti Mendadak' => $faker->numberBetween(2, 5),           // 2-5 hari
                default => $faker->numberBetween(1, 2)
            };

            $tanggalSelesai = (clone $tanggalMulai)->modify("+{$durasi} days");

            // 88% disetujui, 8% ditolak, 4% masih diajukan (izin umumnya lebih mudah disetujui)
            $statusProbability = $faker->numberBetween(1, 100);

            if ($statusProbability <= 88) {
                $statusIzin = 'Disetujui';
                $processedAt = (clone $tanggalMulai)->modify('-' . $faker->numberBetween(1, 5) . ' hours'); // Diproses 1-5 jam sebelumnya
                $alasanPenolakan = null;
            } elseif ($statusProbability <= 96) {
                $statusIzin = 'Ditolak';
                $processedAt = (clone $tanggalMulai)->modify('-' . $faker->numberBetween(1, 3) . ' hours');
                $alasanPenolakan = $faker->randomElement([
                    'Tidak ada coverage yang memadai untuk pekerjaan yang ditinggalkan',
                    'Periode yang dipilih bertepatan dengan deadline project penting',
                    'Keterangan kurang detail dan tidak mencukupi',
                    'Sudah terlalu banyak karyawan yang izin di hari yang sama',
                    'Dokumen pendukung diperlukan untuk jenis izin ini',
                    'Jadwal izin terlalu mendadak, harap koordinasi lebih awal',
                    'Workload sedang tinggi, mohon reschedule ke waktu lain',
                    'Tidak sesuai dengan kebijakan izin perusahaan'
                ]);
            } else {
                $statusIzin = 'Diajukan';
                $processedAt = null;
                $alasanPenolakan = null;
            }

            // Pilih keterangan berdasarkan jenis izin
            $keterangan = $faker->randomElement($keteranganPerJenis[$jenisIzin]);

            // 30% ada dokumen pendukung (lebih sedikit dari cuti)
            $dokumenPendukung = null;
            if ($faker->boolean(30)) {
                $dokumenSuffix = match ($jenisIzin) {
                    'Izin Sakit' => 'surat_dokter',
                    'Izin Keperluan Keluarga' => 'surat_keterangan',
                    'Izin Menghadiri Acara' => 'undangan_acara',
                    'Izin Keperluan Dinas' => 'surat_tugas',
                    'Izin Cuti Mendadak' => 'surat_kematian',
                    default => 'surat_pendukung'
                };
                $dokumenPendukung = 'izin/' . $dokumenSuffix . '_' . $faker->randomNumber(6) . '.pdf';
            }

            // Untuk izin same day, created_at bisa jadi di hari yang sama
            $createdAt = match ($jenisIzin) {
                'Izin Datang Terlambat' => (clone $tanggalMulai)->modify('-' . $faker->numberBetween(0, 2) . ' hours'),
                'Izin Pulang Lebih Awal' => (clone $tanggalMulai)->modify('-' . $faker->numberBetween(1, 8) . ' hours'),
                'Izin Keluar Kantor' => (clone $tanggalMulai)->modify('-' . $faker->numberBetween(1, 24) . ' hours'),
                default => (clone $tanggalMulai)->modify('-' . $faker->numberBetween(1, 7) . ' days')
            };

            $izinData[] = [
                'izin_id' => 'IZ' . str_pad($counter, 4, '0', STR_PAD_LEFT),
                'karyawan_id' => $karyawan->karyawan_id,
                'jenis_izin' => $jenisIzin,
                'tanggal_mulai' => $tanggalMulai->format('Y-m-d'),
                'tanggal_selesai' => $tanggalSelesai->format('Y-m-d'),
                'keterangan' => $keterangan,
                'dokumen_pendukung' => $dokumenPendukung,
                'status_izin' => $statusIzin,
                'alasan_penolakan' => $alasanPenolakan,
                'approver_id' => $statusIzin !== 'Diajukan' ? $approver->karyawan_id : null,
                'processed_at' => $processedAt?->format('Y-m-d H:i:s'),
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'updated_at' => $processedAt?->format('Y-m-d H:i:s') ?? $createdAt->format('Y-m-d H:i:s'),
            ];

            $counter++;
        }

        // Insert data dalam batch
        foreach (array_chunk($izinData, 50) as $chunk) {
            Izin::insert($chunk);
        }

        $this->command->info('Berhasil membuat ' . count($izinData) . ' data izin');
        $this->command->info('Status: Disetujui (~88%), Ditolak (~8%), Diajukan (~4%)');
        $this->command->info('Jenis Izin: Keluarga (20%), Pribadi (15%), Sakit (18%), Terlambat (12%), dst.');
        $this->command->info('Distribusi durasi: Same day untuk izin harian, 1-5 hari untuk izin lainnya');
    }
}
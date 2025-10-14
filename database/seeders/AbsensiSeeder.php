<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Cabang;
use App\Models\Cuti;
use App\Models\Izin;
use Carbon\Carbon;
use Faker\Factory as Faker;

class AbsensiSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $faker = Faker::create('id_ID');

    // Ambil semua karyawan
    $karyawans = Karyawan::all();
    $cabangs = Cabang::all();

    if ($karyawans->isEmpty() || $cabangs->isEmpty()) {
      $this->command->error('Tidak ada data karyawan atau cabang. Jalankan PerusahaanKaryawanSeeder terlebih dahulu.');
      return;
    }

    // Generate data untuk periode: bulan lalu sampai hari terakhir bulan ini
    $startDate = Carbon::now()->subMonthsNoOverflow(5)->startOfMonth();
    $endDate = Carbon::now()->endOfMonth();

    // ✅ CLEAR existing absensi data untuk periode ini (untuk avoid duplikasi)
    $this->command->info('Clearing existing absensi data for period...');
    Absensi::whereBetween('tanggal', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
      ->delete();

    // ✅ INTEGRASI: Ambil data cuti dan izin yang sudah ada dan disetujui
    $cutiDisetujui = Cuti::where('status_cuti', 'Disetujui')
      ->whereBetween('tanggal_mulai', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
      ->orWhereBetween('tanggal_selesai', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
      ->get();

    $izinDisetujui = Izin::where('status_izin', 'Disetujui')
      ->whereBetween('tanggal_mulai', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
      ->orWhereBetween('tanggal_selesai', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
      ->get();

    // ✅ BUAT mapping tanggal cuti dan izin per karyawan dengan UNIQUE tanggal
    $cutiPerKaryawan = [];
    $izinPerKaryawan = [];
    $allReservedDates = []; // Track all reserved dates to avoid duplicates

    // Map cuti dates
    foreach ($cutiDisetujui as $cuti) {
      $startCuti = Carbon::parse($cuti->tanggal_mulai);
      $endCuti = Carbon::parse($cuti->tanggal_selesai);

      // Ensure dates are within our period
      if ($startCuti->lt($startDate))
        $startCuti = $startDate->copy();
      if ($endCuti->gt($endDate))
        $endCuti = $endDate->copy();

      $currentDate = $startCuti->copy();
      while ($currentDate <= $endCuti) {
        if (!$currentDate->isWeekend()) {
          $dateKey = $cuti->karyawan_id . '_' . $currentDate->format('Y-m-d');

          // ✅ AVOID DUPLICATES: Cek apakah tanggal sudah direservasi
          if (!isset($allReservedDates[$dateKey])) {
            $cutiPerKaryawan[$cuti->karyawan_id][] = $currentDate->format('Y-m-d');
            $allReservedDates[$dateKey] = 'cuti';
          }
        }
        $currentDate->addDay();
      }
    }

    // Map izin dates
    foreach ($izinDisetujui as $izin) {
      $startIzin = Carbon::parse($izin->tanggal_mulai);
      $endIzin = Carbon::parse($izin->tanggal_selesai);

      // Ensure dates are within our period
      if ($startIzin->lt($startDate))
        $startIzin = $startDate->copy();
      if ($endIzin->gt($endDate))
        $endIzin = $endDate->copy();

      $currentDate = $startIzin->copy();
      while ($currentDate <= $endIzin) {
        if (!$currentDate->isWeekend()) {
          $dateKey = $izin->karyawan_id . '_' . $currentDate->format('Y-m-d');

          // ✅ PRIORITY: Cuti > Izin (jika ada conflict, cuti menang)
          if (!isset($allReservedDates[$dateKey])) {
            $izinPerKaryawan[$izin->karyawan_id][] = $currentDate->format('Y-m-d');
            $allReservedDates[$dateKey] = 'izin';
          }
        }
        $currentDate->addDay();
      }
    }

    $absensiData = [];
    $counter = 1;
    $totalAbsensiGenerated = 0;

    $this->command->info("Mulai generate data absensi untuk {$karyawans->count()} karyawan selama 2 bulan...");
    $this->command->info("Mengintegrasikan {$cutiDisetujui->count()} cuti dan {$izinDisetujui->count()} izin yang disetujui...");
    $this->command->info("Total reserved dates: " . count($allReservedDates));

    // Loop untuk setiap karyawan
    foreach ($karyawans as $index => $karyawan) {
      $currentDate = $startDate->copy();
      $absensiKaryawan = 0;

      // Ambil daftar tanggal cuti dan izin untuk karyawan ini
      $tanggalCutiKaryawan = $cutiPerKaryawan[$karyawan->karyawan_id] ?? [];
      $tanggalIzinKaryawan = $izinPerKaryawan[$karyawan->karyawan_id] ?? [];

      $this->command->info("Processing karyawan " . ($index + 1) . "/" . $karyawans->count() . ": {$karyawan->nama_lengkap}");

      // Generate absensi untuk setiap hari kerja (Senin-Jumat) selama 2 bulan
      while ($currentDate <= $endDate) {
        // Skip weekend (Sabtu dan Minggu)
        if ($currentDate->isWeekend()) {
          $currentDate->addDay();
          continue;
        }

        // Skip jika karyawan belum mulai bekerja di tanggal tersebut
        if ($currentDate->lt(Carbon::parse($karyawan->tanggal_mulai_bekerja))) {
          $currentDate->addDay();
          continue;
        }

        $tanggalStr = $currentDate->format('Y-m-d');
        $dateKey = $karyawan->karyawan_id . '_' . $tanggalStr;

        // ✅ CEK STATUS: Cuti, Izin, atau Hadir/Alfa (ONLY ONE per date)
        if (in_array($tanggalStr, $tanggalCutiKaryawan)) {
          // Status absensi: Cuti
          $absensiData[] = $this->generateAbsensiCuti($faker, $karyawan, $cabangs->random(), $currentDate, $counter);
          $absensiKaryawan++;
          $counter++;
        } elseif (in_array($tanggalStr, $tanggalIzinKaryawan)) {
          // Status absensi: Izin
          $absensiData[] = $this->generateAbsensiIzin($faker, $karyawan, $cabangs->random(), $currentDate, $counter);
          $absensiKaryawan++;
          $counter++;
        } else {
          // Status normal: Hadir, Tidak Tepat, atau Alfa
          $attendanceProbability = $this->getAttendanceProbability($karyawan->jabatan);

          if ($faker->boolean($attendanceProbability)) {
            // Generate absensi hadir dengan variasi
            $absensiData[] = $this->generateAbsensiHadir($faker, $karyawan, $cabangs->random(), $currentDate, $counter);
            $absensiKaryawan++;
            $counter++;
          } else {
            // Generate absensi tidak hadir (Alfa)
            $absensiData[] = $this->generateAbsensiTidakHadir($faker, $karyawan, $cabangs->random(), $currentDate, $counter);
            $absensiKaryawan++;
            $counter++;
          }
        }

        $currentDate->addDay();

        // Safety limit untuk mencegah memory overflow
        if ($counter > 15000) {
          break 2;
        }
      }

      $totalAbsensiGenerated += $absensiKaryawan;
      $this->command->info("- Generated {$absensiKaryawan} records for {$karyawan->nama_lengkap}");
    }

    // Insert data dalam batch untuk performa lebih baik
    $this->command->info('Inserting data to database...');
    $chunks = array_chunk($absensiData, 200);
    $chunkCount = count($chunks);

    foreach ($chunks as $index => $chunk) {
      Absensi::insert($chunk);
      $this->command->info("Inserted batch " . ($index + 1) . "/" . $chunkCount);
    }

    $this->command->info('=== SUMMARY ===');
    $this->command->info('Berhasil membuat ' . count($absensiData) . ' data absensi');
    $this->command->info('Total karyawan: ' . $karyawans->count());
    $this->command->info('Rata-rata absensi per karyawan: ' . round($totalAbsensiGenerated / $karyawans->count(), 1) . ' hari');
    $this->command->info('Periode: ' . $startDate->format('d-m-Y') . ' sampai ' . $endDate->format('d-m-Y'));
    $this->command->info('Integrasi: ' . $cutiDisetujui->count() . ' cuti + ' . $izinDisetujui->count() . ' izin');
  }

  // ✅ IMPROVED: Generate absensi dengan status Cuti
  private function generateAbsensiCuti($faker, $karyawan, $cabang, $date, $counter): array
  {
    return [
      'absensi_id' => 'AB' . str_pad($counter, 4, '0', STR_PAD_LEFT),
      'karyawan_id' => $karyawan->karyawan_id,
      'cabang_id' => $cabang->cabang_id,
      'tanggal' => $date->format('Y-m-d'),
      'waktu_masuk' => $date->copy()->setTime(9, 0, 0)->format('Y-m-d H:i:s'), // Set waktu standar
      'waktu_pulang' => $date->copy()->setTime(17, 0, 0)->format('Y-m-d H:i:s'), // Set waktu standar
      'status_masuk' => 'Tepat Waktu',
      'status_pulang' => 'Tepat Waktu',
      'durasi_telat' => null,
      'durasi_pulang_cepat' => null,
      'koordinat_masuk' => '0.000000,0.000000',
      'koordinat_pulang' => '0.000000,0.000000',
      'foto_masuk' => 'cuti_placeholder.jpg', // ✅ NOT NULL - required by database
      'foto_pulang' => 'cuti_placeholder.jpg', // ✅ NOT NULL - required by database
      'status_absensi' => 'Cuti', // ✅ Sesuai enum database
      'created_at' => $date->copy()->setTime(9, 0, 0)->format('Y-m-d H:i:s'),
      'updated_at' => $date->copy()->setTime(17, 0, 0)->format('Y-m-d H:i:s'),
    ];
  }

  // ✅ IMPROVED: Generate absensi dengan status Izin
  private function generateAbsensiIzin($faker, $karyawan, $cabang, $date, $counter): array
  {
    return [
      'absensi_id' => 'AB' . str_pad($counter, 4, '0', STR_PAD_LEFT),
      'karyawan_id' => $karyawan->karyawan_id,
      'cabang_id' => $cabang->cabang_id,
      'tanggal' => $date->format('Y-m-d'),
      'waktu_masuk' => $date->copy()->setTime(9, 0, 0)->format('Y-m-d H:i:s'), // Set waktu standar
      'waktu_pulang' => $date->copy()->setTime(17, 0, 0)->format('Y-m-d H:i:s'), // Set waktu standar  
      'status_masuk' => 'Tepat Waktu',
      'status_pulang' => 'Tepat Waktu',
      'durasi_telat' => null,
      'durasi_pulang_cepat' => null,
      'koordinat_masuk' => '0.000000,0.000000',
      'koordinat_pulang' => '0.000000,0.000000',
      'foto_masuk' => 'izin_placeholder.jpg', // ✅ NOT NULL - required by database
      'foto_pulang' => 'izin_placeholder.jpg', // ✅ NOT NULL - required by database
      'status_absensi' => 'Izin', // ✅ Sesuai enum database
      'created_at' => $date->copy()->setTime(9, 0, 0)->format('Y-m-d H:i:s'),
      'updated_at' => $date->copy()->setTime(17, 0, 0)->format('Y-m-d H:i:s'),
    ];
  }

  /**
   * Tentukan probabilitas kehadiran berdasarkan jabatan/posisi
   */
  private function getAttendanceProbability(string $jabatan): int
  {
    return match (true) {
      // Management level - kehadiran tinggi
      str_contains($jabatan, 'CEO') => 95,
      str_contains($jabatan, 'Manager') => 93,
      str_contains($jabatan, 'Administrator') => 92,

      // Staff level - kehadiran sedang-tinggi
      str_contains($jabatan, 'Staff') => 89,
      str_contains($jabatan, 'Officer') => 88,

      // Karyawan biasa - kehadiran normal
      default => 85
    };
  }

  /**
   * Generate data absensi untuk karyawan yang hadir
   */
  private function generateAbsensiHadir($faker, $karyawan, $cabang, $date, $counter): array
  {
    // Jam kerja standar: 09:00 - 17:00
    $jamMasukStandar = $date->copy()->setTime(9, 0, 0);
    $jamPulangStandar = $date->copy()->setTime(17, 0, 0);

    // Generate waktu masuk dengan variasi lebih realistis
    // 60% tepat waktu, 25% sedikit terlambat (9:01-9:30), 15% terlambat (9:31-10:30)
    $ketepatanWaktu = $faker->numberBetween(1, 100);

    if ($ketepatanWaktu <= 60) {
      // Tepat waktu atau lebih awal (7:30-9:00)
      $waktuMasuk = $date->copy()->setTime(
        $faker->numberBetween(7, 8),
        $faker->numberBetween(30, 59),
        $faker->numberBetween(0, 59)
      );
    } elseif ($ketepatanWaktu <= 85) {
      // Sedikit terlambat (9:01-9:30)
      $waktuMasuk = $date->copy()->setTime(9, $faker->numberBetween(1, 30), $faker->numberBetween(0, 59));
    } else {
      // Terlambat (9:31-10:30)
      $waktuMasuk = $date->copy()->setTime(
        $faker->numberBetween(9, 10),
        $faker->numberBetween(31, 59),
        $faker->numberBetween(0, 59)
      );
    }

    // Status masuk berdasarkan waktu masuk
    $statusMasuk = $waktuMasuk->lte($jamMasukStandar) ? 'Tepat Waktu' : 'Telat';

    // Hitung durasi telat
    $durasiTelat = null;
    if ($statusMasuk === 'Telat') {
      $telat = $jamMasukStandar->diffInMinutes($waktuMasuk);
      $durasiTelat = sprintf('%02d:%02d:00', floor($telat / 60), $telat % 60);
    }

    // Generate waktu pulang dengan variasi
    $probabilityPulang = $faker->numberBetween(1, 100);
    $statusPulang = 'Tepat Waktu';
    $durasiPulangCepat = null;

    if ($probabilityPulang <= 70) {
      // Pulang normal (17:00 - 17:45)
      $waktuPulang = $date->copy()->setTime(17, $faker->numberBetween(0, 45), $faker->numberBetween(0, 59));
      $statusPulang = 'Tepat Waktu';
      $durasiPulangCepat = null;
    } elseif ($probabilityPulang <= 85) {
      // Pulang cepat (15:00 - 16:59) - dengan izin atau keperluan
      $waktuPulang = $date->copy()->setTime($faker->numberBetween(15, 16), $faker->numberBetween(0, 59), $faker->numberBetween(0, 59));
      $statusPulang = 'Pulang Cepat';
      $pulangCepat = $waktuPulang->diffInMinutes($jamPulangStandar);
      $durasiPulangCepat = sprintf('%02d:%02d:00', floor($pulangCepat / 60), $pulangCepat % 60);
    } else {
      // Lembur (18:00 - 21:00)
      $waktuPulang = $date->copy()->setTime($faker->numberBetween(18, 21), $faker->numberBetween(0, 59), $faker->numberBetween(0, 59));
      $statusPulang = 'Tepat Waktu'; // Lembur masih dianggap tepat waktu
      $durasiPulangCepat = null;
    }

    // Koordinat dengan variasi yang lebih realistis
    $koordinatMasuk = $this->generateKoordinat($faker, $cabang);
    $koordinatPulang = $this->generateKoordinat($faker, $cabang);

    // ✅ FIXED: Status absensi keseluruhan berdasarkan logika yang benar
    // Status absensi = 'Hadir' HANYA jika KEDUA status masuk DAN pulang tepat waktu
    // Jika SALAH SATU tidak tepat, maka status absensi = 'Tidak Tepat'
    if ($statusMasuk === 'Tepat Waktu' && $statusPulang === 'Tepat Waktu') {
      $statusAbsensi = 'Hadir';
    } else {
      // Jika ada yang tidak tepat (telat masuk ATAU pulang cepat)
      $statusAbsensi = 'Tidak Tepat';
    }

    // ✅ SPECIAL CASE: Jika terlambat lebih dari 60 menit, langsung 'Tidak Tepat'
    if ($statusMasuk === 'Telat') {
      $menitTerlambat = $jamMasukStandar->diffInMinutes($waktuMasuk);
      if ($menitTerlambat > 60) {
        $statusAbsensi = 'Tidak Tepat';
      }
    }

    return [
      'absensi_id' => 'AB' . str_pad($counter, 4, '0', STR_PAD_LEFT),
      'karyawan_id' => $karyawan->karyawan_id,
      'cabang_id' => $cabang->cabang_id,
      'tanggal' => $date->format('Y-m-d'),
      'waktu_masuk' => $waktuMasuk->format('Y-m-d H:i:s'),
      'waktu_pulang' => $waktuPulang->format('Y-m-d H:i:s'),
      'status_masuk' => $statusMasuk,
      'status_pulang' => $statusPulang,
      'durasi_telat' => $durasiTelat,
      'durasi_pulang_cepat' => $durasiPulangCepat,
      'koordinat_masuk' => $koordinatMasuk,
      'koordinat_pulang' => $koordinatPulang,
      'foto_masuk' => 'masuk_' . $faker->randomNumber(6) . '.jpg',
      'foto_pulang' => $faker->boolean(95) ? 'pulang_' . $faker->randomNumber(6) . '.jpg' : null,
      'status_absensi' => $statusAbsensi, // ✅ Sekarang logikanya sudah benar
      'created_at' => $waktuMasuk,
      'updated_at' => $waktuPulang,
    ];
  }

  /**
   * Generate data absensi untuk karyawan yang tidak hadir (Alfa)
   */
  private function generateAbsensiTidakHadir($faker, $karyawan, $cabang, $date, $counter): array
  {
    // Untuk Alfa, buat seolah-olah karyawan "terdaftar" tapi tidak benar-benar masuk
    // Gunakan waktu standar sebagai placeholder
    $waktuStandarMasuk = $date->copy()->setTime(9, 0, 0);
    $waktuStandarPulang = $date->copy()->setTime(17, 0, 0);

    return [
      'absensi_id' => 'AB' . str_pad($counter, 4, '0', STR_PAD_LEFT),
      'karyawan_id' => $karyawan->karyawan_id,
      'cabang_id' => $cabang->cabang_id,
      'tanggal' => $date->format('Y-m-d'),

      // Waktu placeholder - tidak NULL tapi menandakan tidak hadir
      'waktu_masuk' => $waktuStandarMasuk->format('Y-m-d H:i:s'),
      'waktu_pulang' => $waktuStandarPulang->format('Y-m-d H:i:s'),

      // Status yang menunjukkan ketidakhadiran
      'status_masuk' => 'Telat', // Anggap sangat telat
      'status_pulang' => 'Tepat Waktu', // Status dummy

      // Durasi yang ekstrem untuk menandakan alfa
      'durasi_telat' => '08:00:00', // 8 jam = tidak masuk sama sekali
      'durasi_pulang_cepat' => null,

      // Koordinat default (0,0) menandakan tidak ada lokasi
      'koordinat_masuk' => '0.000000,0.000000',
      'koordinat_pulang' => '0.000000,0.000000',

      // Foto default untuk alfa
      'foto_masuk' => 'no_photo_alfa.jpg',
      'foto_pulang' => 'no_photo_alfa.jpg',

      // Status utama: Alfa
      'status_absensi' => 'Alfa',

      'created_at' => $waktuStandarMasuk,
      'updated_at' => $waktuStandarPulang,
    ];
  }

  /**
   * Generate koordinat realistis berdasarkan lokasi cabang
   */
  private function generateKoordinat($faker, $cabang): string
  {
    // Radius variasi koordinat berdasarkan radius lokasi cabang
    $maxVariation = ($cabang->radius_lokasi / 111320); // Konversi meter ke derajat (aproksimasi)

    $latVariation = $faker->randomFloat(6, -$maxVariation, $maxVariation);
    $lngVariation = $faker->randomFloat(6, -$maxVariation, $maxVariation);

    $lat = $cabang->latitude + $latVariation;
    $lng = $cabang->longitude + $lngVariation;

    return round($lat, 6) . ',' . round($lng, 6);
  }
}
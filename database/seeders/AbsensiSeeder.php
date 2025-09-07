<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Cabang;
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

    // Ambil semua karyawan (20 karyawan)
    $karyawans = Karyawan::all();
    $cabangs = Cabang::all();

    if ($karyawans->isEmpty() || $cabangs->isEmpty()) {
      $this->command->error('Tidak ada data karyawan atau cabang. Jalankan PerusahaanKaryawanSeeder terlebih dahulu.');
      return;
    }

    // Generate data untuk 25 hari kerja (sekitar 1 bulan)
    $startDate = Carbon::now()->subDays(30);
    $endDate = Carbon::now()->subDay();

    $absensiData = [];
    $counter = 1;

    // Loop untuk setiap karyawan
    foreach ($karyawans as $karyawan) {
      $currentDate = $startDate->copy();

      // Generate absensi untuk setiap hari kerja (Senin-Jumat)
      while ($currentDate <= $endDate) {
        // Skip weekend (Sabtu dan Minggu)
        if ($currentDate->isWeekend()) {
          $currentDate->addDay();
          continue;
        }

        // 95% kemungkinan karyawan hadir
        if ($faker->boolean(95)) {
          $absensiData[] = $this->generateAbsensiHadir($faker, $karyawan, $cabangs->random(), $currentDate, $counter);
          $counter++;
        } else {
          // 5% kemungkinan tidak hadir (Alfa)
          $absensiData[] = $this->generateAbsensiAlfa($faker, $karyawan, $cabangs->random(), $currentDate, $counter);
          $counter++;
        }

        $currentDate->addDay();

        // Batasi maksimal 500 data
        if ($counter > 500) {
          break 2;
        }
      }
    }

    // Insert data dalam batch untuk performa lebih baik
    foreach (array_chunk($absensiData, 100) as $chunk) {
      Absensi::insert($chunk);
    }

    $this->command->info('Berhasil membuat ' . count($absensiData) . ' data absensi');
  }

  /**
   * Generate data absensi untuk karyawan yang hadir
   */
  private function generateAbsensiHadir($faker, $karyawan, $cabang, $date, $counter): array
  {
    // Jam kerja standar: 09:00 - 17:00
    $jamMasukStandar = $date->copy()->setTime(9, 0, 0);
    $jamPulangStandar = $date->copy()->setTime(17, 0, 0);

    // Generate waktu masuk (07:30 - 10:00)
    $waktuMasuk = $date->copy()
      ->setTime(
        $faker->numberBetween(7, 9), // jam 7-9
        $faker->numberBetween(0, 59), // menit
        $faker->numberBetween(0, 59)  // detik
      );

    // Tentukan status masuk - HARUS sesuai dengan ENUM database
    $statusMasuk = $waktuMasuk->lte($jamMasukStandar) ? 'Tepat Waktu' : 'Telat';

    // Hitung durasi telat jika terlambat
    $durasiTelat = null;
    if ($statusMasuk === 'Telat') {
      $telat = $jamMasukStandar->diffInMinutes($waktuMasuk);
      $durasiTelat = sprintf('%02d:%02d:00', floor($telat / 60), $telat % 60);
    }

    // Generate waktu pulang (85% pulang normal, 15% pulang cepat/lembur)
    $probability = $faker->numberBetween(1, 100);

    if ($probability <= 85) {
      // Pulang normal (17:00 - 17:30)
      $waktuPulang = $date->copy()
        ->setTime(17, $faker->numberBetween(0, 30), $faker->numberBetween(0, 59));
      $statusPulang = 'Tepat Waktu'; // Sesuai ENUM
      $durasiPulangCepat = null;
    } elseif ($probability <= 95) {
      // Pulang cepat (15:00 - 16:59)
      $waktuPulang = $date->copy()
        ->setTime($faker->numberBetween(15, 16), $faker->numberBetween(0, 59), $faker->numberBetween(0, 59));
      $statusPulang = 'Pulang Cepat'; // Sesuai ENUM

      // Hitung durasi pulang cepat (jam standar - jam pulang aktual)
      $pulangCepat = $waktuPulang->diffInMinutes($jamPulangStandar);
      $durasiPulangCepat = sprintf('%02d:%02d:00', floor($pulangCepat / 60), $pulangCepat % 60);
    } else {
      // Lembur (17:31 - 20:00)
      $waktuPulang = $date->copy()
        ->setTime($faker->numberBetween(18, 20), $faker->numberBetween(0, 59), $faker->numberBetween(0, 59));
      $statusPulang = 'Tepat Waktu'; // Sesuai ENUM
      $durasiPulangCepat = null;
    }

    // Koordinat realistis untuk Jakarta dan sekitarnya
    $koordinatMasuk = $this->generateKoordinat($faker, $cabang);
    $koordinatPulang = $this->generateKoordinat($faker, $cabang);

    // Status absensi berdasarkan ketepatan waktu - HARUS sesuai dengan ENUM
    if ($statusMasuk === 'Tepat Waktu' && ($statusPulang === 'Tepat Waktu' || $statusPulang === null)) {
      $statusAbsensi = 'Hadir';
    } else {
      $statusAbsensi = 'Tidak Tepat';
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
      'foto_pulang' => $faker->boolean(90) ? 'pulang_' . $faker->randomNumber(6) . '.jpg' : null,
      'status_absensi' => $statusAbsensi,
      'created_at' => $waktuMasuk,
      'updated_at' => $waktuPulang,
    ];
  }

  /**
   * Generate data absensi untuk karyawan yang alfa
   */
  private function generateAbsensiAlfa($faker, $karyawan, $cabang, $date, $counter): array
  {
    // Untuk data alfa, buat waktu dummy tapi tetap masuk akal
    $dummyTime = $date->copy()->setTime(9, 0, 0);

    return [
      'absensi_id' => 'AB' . str_pad($counter, 4, '0', STR_PAD_LEFT),
      'karyawan_id' => $karyawan->karyawan_id,
      'cabang_id' => $cabang->cabang_id,
      'tanggal' => $date->format('Y-m-d'),
      'waktu_masuk' => $dummyTime->format('Y-m-d H:i:s'),
      'waktu_pulang' => null,
      'status_masuk' => null, // Set ke NULL untuk data alfa
      'status_pulang' => null,
      'durasi_telat' => null,
      'durasi_pulang_cepat' => null,
      'koordinat_masuk' => '0,0',
      'koordinat_pulang' => null,
      'foto_masuk' => 'default.jpg',
      'foto_pulang' => null,
      'status_absensi' => 'Alfa', // Sesuai ENUM
      'created_at' => $dummyTime,
      'updated_at' => $dummyTime,
    ];
  }

  /**
   * Generate koordinat realistis berdasarkan lokasi cabang
   */
  private function generateKoordinat($faker, $cabang): string
  {
    // Radius variasi koordinat (sekitar 100 meter)
    $latVariation = $faker->randomFloat(6, -0.001, 0.001);
    $lngVariation = $faker->randomFloat(6, -0.001, 0.001);

    $lat = $cabang->latitude + $latVariation;
    $lng = $cabang->longitude + $lngVariation;

    return round($lat, 6) . ',' . round($lng, 6);
  }
}
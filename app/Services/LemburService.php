<?php

namespace App\Services;

use App\Models\Lembur;
use App\Models\Karyawan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LemburService
{
  /**
   * Menghitung insentif lembur berdasarkan durasi dan gaji karyawan
   * sesuai PP 35/2021 tentang upah lembur.
   *
   * @param string $durasiLembur Format HH:MM:SS
   * @param Karyawan $karyawan
   * @return float
   */
  public function calculateInsentif(string $durasiLembur, Karyawan $karyawan): float
  {
    // Guard clause: validasi input
    if (!$durasiLembur || !$karyawan) {
      Log::warning("LemburService: Invalid input - durasi atau karyawan kosong");
      return 0;
    }

    try {
      // Langkah 1: Parse durasi lembur (format HH:MM:SS) ke dalam menit
      $durasi = Carbon::parse($durasiLembur);
      $totalMenit = ($durasi->hour * 60) + $durasi->minute;

      // Jika ada detik, bulatkan menitnya ke atas
      if ($durasi->second > 0) {
        $totalMenit++;
      }

      if ($totalMenit <= 0) {
        Log::info("LemburService: Durasi lembur <= 0 untuk karyawan {$karyawan->karyawan_id}");
        return 0;
      }

      // Langkah 2: Bulatkan total menit ke jam penuh berikutnya (sesuai aturan)
      // Contoh: 30 menit -> 1 jam, 75 menit -> 2 jam
      $jamDihitung = (int) ceil($totalMenit / 60.0);

      // Langkah 3: Hitung upah per jam dengan RUMUS RESMI (dibagi 173)
      $gajiPokok = (float) ($karyawan->gaji_pokok ?? 0);

      // TODO: Jika ada tunjangan tetap, bisa ditambahkan di sini
      $upahSebulan = $gajiPokok; // + tunjangan_tetap jika ada

      if ($upahSebulan <= 0) {
        Log::warning("LemburService: Upah sebulan <= 0 untuk karyawan {$karyawan->karyawan_id}");
        return 0;
      }

      // Pembagi 173 sesuai PP 35/2021
      $upahPerJam = $upahSebulan / 173;

      // Langkah 4: Terapkan tarif berjenjang
      // - Jam pertama: 1.5x upah per jam
      // - Jam kedua dan seterusnya: 2x upah per jam
      $totalUpahLembur = 0;

      // Perhitungan untuk jam pertama
      if ($jamDihitung >= 1) {
        $totalUpahLembur += (1 * 1.5 * $upahPerJam);
      }

      // Perhitungan untuk jam-jam berikutnya (jika ada)
      if ($jamDihitung > 1) {
        $sisaJam = $jamDihitung - 1;
        $totalUpahLembur += ($sisaJam * 2 * $upahPerJam);
      }

      $result = round($totalUpahLembur, 2);

      Log::info("LemburService: Karyawan {$karyawan->karyawan_id} - Durasi: {$durasiLembur} ({$totalMenit} menit -> {$jamDihitung} jam), Upah/jam: " . number_format($upahPerJam, 2) . ", Total: " . number_format($result, 2));

      return $result;

    } catch (\Exception $e) {
      Log::error("LemburService: Error calculating insentif for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Menghitung insentif untuk model Lembur yang sudah ada
   *
   * @param Lembur $lembur
   * @return float
   */
  public function calculateInsentifFromLembur(Lembur $lembur): float
  {
    if (!$lembur->karyawan || !$lembur->durasi_lembur) {
      return 0;
    }

    return $this->calculateInsentif($lembur->durasi_lembur, $lembur->karyawan);
  }

  /**
   * Format insentif ke format Rupiah
   *
   * @param float $insentif
   * @return string
   */
  public function formatRupiah(float $insentif): string
  {
    return 'Rp ' . number_format($insentif, 0, ',', '.');
  }

  /**
   * Validasi apakah durasi lembur valid
   *
   * @param string $durasiLembur
   * @return bool
   */
  public function validateDurasi(string $durasiLembur): bool
  {
    try {
      $durasi = Carbon::parse($durasiLembur);
      $totalMenit = ($durasi->hour * 60) + $durasi->minute;
      return $totalMenit > 0;
    } catch (\Exception $e) {
      return false;
    }
  }
}
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
   * @param bool $isHariLibur Tandai true untuk tanggal merah/weekend
   * @return float
   */
  public function calculateInsentif(string $durasiLembur, Karyawan $karyawan, bool $isHariLibur = false): float
  {
    if (!$durasiLembur || !$karyawan) {
      Log::warning("LemburService: Invalid input - durasi atau karyawan kosong");
      return 0;
    }

    try {
      $durasi = Carbon::parse($durasiLembur);
      $totalMenit = ($durasi->hour * 60) + $durasi->minute;

      if ($durasi->second > 0) {
        $totalMenit++;
      }

      if ($totalMenit <= 0) {
        Log::info("LemburService: Durasi lembur <= 0 untuk karyawan {$karyawan->karyawan_id}");
        return 0;
      }

      $jamDihitung = (int) ceil($totalMenit / 60.0);

      $gajiPokok = (float) ($karyawan->gaji_pokok ?? 0);
      $tunjanganTetap = (float) ($karyawan->tunjangan_tetap ?? 0);
      $upahSebulan = $gajiPokok + $tunjanganTetap;

      if ($upahSebulan <= 0) {
        Log::warning("LemburService: Upah sebulan <= 0 untuk karyawan {$karyawan->karyawan_id}");
        return 0;
      }

      $upahPerJam = $upahSebulan / 173;

      $totalUpahLembur = 0;

      if ($isHariLibur) {
        $jamPertama = min($jamDihitung, 8);
        $totalUpahLembur += ($jamPertama * 2 * $upahPerJam);

        if ($jamDihitung > 8) {
          $totalUpahLembur += (1 * 3 * $upahPerJam);
        }

        if ($jamDihitung > 9) {
          $sisa = $jamDihitung - 9;
          $totalUpahLembur += ($sisa * 4 * $upahPerJam);
        }
      } else {
        if ($jamDihitung >= 1) {
          $totalUpahLembur += (1 * 1.5 * $upahPerJam);
        }

        if ($jamDihitung > 1) {
          $sisaJam = $jamDihitung - 1;
          $totalUpahLembur += ($sisaJam * 2 * $upahPerJam);
        }
      }

      // ROUND TO INTEGER - NO DECIMALS
      $result = round($totalUpahLembur);

      Log::info("LemburService: Karyawan {$karyawan->karyawan_id} - Durasi: {$durasiLembur} ({$totalMenit} menit -> {$jamDihitung} jam), Upah/jam: " . number_format($upahPerJam, 0) . ", Hari Libur: " . ($isHariLibur ? 'Ya' : 'Tidak') . ", Total: " . number_format($result, 0));

      return $result;

    } catch (\Exception $e) {
      Log::error("LemburService: Error calculating insentif for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Menghitung total lembur untuk periode tertentu
   *
   * @param Karyawan $karyawan
   * @param Carbon $periodeStart  
   * @param Carbon $periodeEnd
   * @return array
   */
  public function calculateTotalLemburForPeriode(Karyawan $karyawan, Carbon $periodeStart, Carbon $periodeEnd): array
  {
    try {
      $lemburRecords = Lembur::where('karyawan_id', $karyawan->karyawan_id)
        ->whereBetween('tanggal_lembur', [$periodeStart, $periodeEnd])
        ->where('status_lembur', 'Disetujui')
        ->get();

      $totalInsentif = 0;
      $totalJam = 0;
      $totalSesi = $lemburRecords->count();

      foreach ($lemburRecords as $lembur) {
        $isHariLibur = Carbon::parse($lembur->tanggal_lembur)->isWeekend();
        $insentif = $this->calculateInsentif($lembur->durasi_lembur, $karyawan, $isHariLibur);
        $totalInsentif += $insentif;

        // Hitung total jam
        $durasi = Carbon::parse($lembur->durasi_lembur);
        $totalJam += $durasi->hour + ($durasi->minute / 60);
      }

      return [
        'total_insentif' => round($totalInsentif),
        'total_jam' => round($totalJam, 1),
        'total_sesi' => $totalSesi,
        'records' => $lemburRecords
      ];

    } catch (\Exception $e) {
      Log::error("Error calculating total lembur for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
      return [
        'total_insentif' => 0,
        'total_jam' => 0,
        'total_sesi' => 0,
        'records' => collect([])
      ];
    }
  }

  /**
   * Menghitung insentif untuk model Lembur yang sudah ada
   */
  public function calculateInsentifFromLembur(Lembur $lembur): float
  {
    if (!$lembur->karyawan || !$lembur->durasi_lembur) {
      return 0;
    }

    $isHariLibur = $lembur->tanggal_lembur ? Carbon::parse($lembur->tanggal_lembur)->isWeekend() : false;
    return $this->calculateInsentif($lembur->durasi_lembur, $lembur->karyawan, $isHariLibur);
  }

  /**
   * Format insentif ke format Rupiah
   */
  public function formatRupiah(float $insentif): string
  {
    return 'Rp ' . number_format($insentif, 0, ',', '.');
  }

  /**
   * Validasi apakah durasi lembur valid
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

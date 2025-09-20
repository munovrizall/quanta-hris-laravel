<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\Log;

class PenaltyService
{
  /**
   * Hitung potongan keterlambatan berdasarkan data perusahaan
   *
   * @param Karyawan $karyawan
   * @param int $jumlahHariTerlambat
   * @return array
   */
  public function calculateKeterlambatanDeduction(Karyawan $karyawan, int $jumlahHariTerlambat): array
  {
    try {
      $perusahaan = $this->getPerusahaan($karyawan);

      if (!$perusahaan || $jumlahHariTerlambat <= 0) {
        return $this->getEmptyPotonganData();
      }

      $gajiPokok = (float) $karyawan->gaji_pokok;
      $potonganKeterlambatan = (float) ($perusahaan->potongan_keterlambatan ?? 0);

      // Metode perhitungan berdasarkan jenis potongan di database
      $totalPotongan = $this->calculateByDeductionType($gajiPokok, $jumlahHariTerlambat, $potonganKeterlambatan);

      return [
        'total_potongan' => $totalPotongan,
        'jumlah_hari_terlambat' => $jumlahHariTerlambat,
        'potongan_per_hari' => $jumlahHariTerlambat > 0 ? ($totalPotongan / $jumlahHariTerlambat) : 0,
        'breakdown' => [
          'gaji_pokok' => $gajiPokok,
          'potongan_setting' => $potonganKeterlambatan,
          'metode_perhitungan' => $this->getCalculationMethod($potonganKeterlambatan),
        ]
      ];

    } catch (\Exception $e) {
      Log::error("Error calculating keterlambatan deduction for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
      return $this->getEmptyPotonganData();
    }
  }

  /**
   * Hitung potongan alfa berdasarkan gaji harian
   *
   * @param Karyawan $karyawan
   * @param int $jumlahHariAlfa
   * @return array
   */
  public function calculateAlfaDeduction(Karyawan $karyawan, int $jumlahHariAlfa, int $hariKerjaPerBulan = 22): array
  {
    try {
      if ($jumlahHariAlfa <= 0) {
        return $this->getEmptyAlfaData(); // Helper baru untuk data kosong
      }

      // PASTIKAN HARI KERJA TIDAK NOL UNTUK MENGHINDARI ERROR
      if ($hariKerjaPerBulan === 0) {
        throw new \Exception("Hari kerja per bulan tidak boleh nol.");
      }

      // LANGKAH 1: HITUNG KOMPONEN GAJI TETAP
      $upahTetapSebulan = (float) $karyawan->gaji_pokok + (float) $karyawan->tunjangan_jabatan;
      $potonganUpahTetapPerHari = $upahTetapSebulan / $hariKerjaPerBulan;

      // LANGKAH 2: HITUNG KOMPONEN TUNJANGAN HARIAN YANG HANGUS
      $potonganTunjanganTidakTetapHarian = (float) $karyawan->tunjangan_makan_harian + (float) $karyawan->tunjangan_transport_harian;

      // LANGKAH 3: JUMLAHKAN KEDUANYA UNTUK DAPAT POTONGAN TOTAL PER HARI
      $totalPotonganPerHari = $potonganUpahTetapPerHari + $potonganTunjanganTidakTetapHarian;
      $totalPotongan = $totalPotonganPerHari * $jumlahHariAlfa;

      return [
        'total_potongan' => round($totalPotongan),
        'jumlah_hari_alfa' => $jumlahHariAlfa,
        'potongan_per_hari' => round($totalPotonganPerHari),
        'breakdown' => [
          'upah_tetap_sebulan' => $upahTetapSebulan,
          'potongan_upah_tetap_harian' => round($potonganUpahTetapPerHari),
          'potongan_tunjangan_harian' => round($potonganTunjanganTidakTetapHarian),
          'hari_kerja_per_bulan' => $hariKerjaPerBulan,
          'metode_perhitungan' => 'Upah Tetap Harian + Tunjangan Harian',
        ]
      ];
    } catch (\Exception $e) {
      Log::error("Error calculating alfa deduction for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
      return $this->getEmptyAlfaData();
    }
  }

  /**
   * Calculate based on deduction type stored in database
   */
  private function calculateByDeductionType(float $gajiPokok, int $jumlahHari, float $potonganSetting, int $hariKerjaPerBulan = 22): float
  {
    if ($potonganSetting <= 0) {
      return 0;
    }

    // Jika potongan_setting berupa nominal tetap (misal: 50000)
    if ($potonganSetting >= 1000) {
      return $potonganSetting * $jumlahHari;
    }

    // Jika potongan_setting berupa persentase (misal: 0.5 = 0.5% dari gaji harian)
    if ($potonganSetting < 1 && $potonganSetting > 0) {
      $gajiPerHari = $gajiPokok / $hariKerjaPerBulan; // Hari kerja per bulan
      return ($gajiPerHari * $potonganSetting) * $jumlahHari;
    }

    // Jika potongan_setting berupa jam (misal: 4 = 4 jam per hari terlambat)
    if ($potonganSetting >= 1 && $potonganSetting <= 8) {
      $gajiPerJam = $gajiPokok / ($hariKerjaPerBulan * 8); // Per jam
      return ($gajiPerJam * $potonganSetting) * $jumlahHari;
    }

    // Default fallback: 4 jam per hari terlambat
    $gajiPerJam = $gajiPokok / ($hariKerjaPerBulan * 8);
    return ($gajiPerJam * 4) * $jumlahHari;
  }

  /**
   * Get calculation method description
   */
  private function getCalculationMethod(float $potonganSetting): string
  {
    if ($potonganSetting >= 1000) {
      return "Nominal tetap: Rp " . number_format($potonganSetting, 0, ',', '.') . " per hari";
    }

    if ($potonganSetting < 1 && $potonganSetting > 0) {
      return "Persentase: " . ($potonganSetting * 100) . "% dari gaji harian";
    }

    if ($potonganSetting >= 1 && $potonganSetting <= 8) {
      return "Jam: " . $potonganSetting . " jam per hari terlambat";
    }

    return "Default: 4 jam per hari terlambat";
  }

  /**
   * Get perusahaan dari karyawan
   */
  private function getPerusahaan(Karyawan $karyawan): ?Perusahaan
  {
    // Jika karyawan punya relasi perusahaan langsung
    if ($karyawan->relationLoaded('perusahaan')) {
      return $karyawan->perusahaan;
    }

    // Jika ada field perusahaan_id di tabel karyawan
    if ($karyawan->perusahaan_id) {
      return Perusahaan::find($karyawan->perusahaan_id);
    }

    // Fallback: ambil perusahaan pertama
    return Perusahaan::first();
  }

  /**
   * Return empty potongan data structure
   */
  private function getEmptyPotonganData(): array
  {
    return [
      'total_potongan' => 0,
      'jumlah_hari_terlambat' => 0,
      'potongan_per_hari' => 0,
      'breakdown' => [
        'gaji_pokok' => 0,
        'potongan_setting' => 0,
        'metode_perhitungan' => 'N/A',
      ]
    ];
  }

  private function getEmptyAlfaData(): array
  {
    return [
      'total_potongan' => 0,
      'jumlah_hari_alfa' => 0,
      'potongan_per_hari' => 0,
      'breakdown' => []
    ];
  }
}
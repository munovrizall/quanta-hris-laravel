<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\Log;

class BpjsService
{
  /**
   * Menghitung total potongan BPJS untuk seorang karyawan
   *
   * @param Karyawan $karyawan
   * @return array
   */
  public function calculateBpjsDeductions(Karyawan $karyawan): array
  {
    try {
      $perusahaan = $this->getPerusahaan($karyawan);

      if (!$perusahaan) {
        Log::warning("Perusahaan tidak ditemukan untuk karyawan {$karyawan->karyawan_id}");
        return $this->getEmptyBpjsData();
      }

      $gajiPokok = (float) $karyawan->gaji_pokok;

      if ($gajiPokok <= 0) {
        return $this->getEmptyBpjsData();
      }

      // Hitung masing-masing komponen BPJS
      $bpjsKesehatan = $this->calculateBpjsKesehatan($gajiPokok, $perusahaan);
      $bpjsJht = $this->calculateBpjsJht($gajiPokok, $perusahaan);
      $bpjsJp = $this->calculateBpjsJp($gajiPokok, $perusahaan);

      $totalBpjs = $bpjsKesehatan + $bpjsJht + $bpjsJp;

      return [
        'bpjs_kesehatan' => $bpjsKesehatan,
        'bpjs_jht' => $bpjsJht,
        'bpjs_jp' => $bpjsJp,
        'total_bpjs' => $totalBpjs,
        'breakdown' => [
          'gaji_pokok' => $gajiPokok,
          'persen_kesehatan' => $perusahaan->persen_bpjs_kesehatan_karyawan ?? 0,
          'persen_jht' => $perusahaan->persen_bpjs_jht_karyawan ?? 0,
          'persen_jp' => $perusahaan->persen_bpjs_jp_karyawan ?? 0,
          'batas_kesehatan' => $perusahaan->batas_gaji_bpjs_kesehatan ?? 0,
          'batas_pensiun' => $perusahaan->batas_gaji_bpjs_pensiun ?? 0,
        ]
      ];

    } catch (\Exception $e) {
      Log::error("Error calculating BPJS for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
      return $this->getEmptyBpjsData();
    }
  }

  /**
   * Hitung BPJS Kesehatan
   */
  private function calculateBpjsKesehatan(float $gajiPokok, Perusahaan $perusahaan): float
  {
    $persenKesehatan = (float) ($perusahaan->persen_bpjs_kesehatan_karyawan ?? 4.0); // Default 4%
    $batasGaji = (float) ($perusahaan->batas_gaji_bpjs_kesehatan ?? 0);

    // Jika ada batas gaji, gunakan yang terkecil antara gaji pokok atau batas gaji
    $gajiYangDihitung = $batasGaji > 0 ? min($gajiPokok, $batasGaji) : $gajiPokok;

    return ($gajiYangDihitung * $persenKesehatan) / 100;
  }

  /**
   * Hitung BPJS Jaminan Hari Tua (JHT)
   */
  private function calculateBpjsJht(float $gajiPokok, Perusahaan $perusahaan): float
  {
    $persenJht = (float) ($perusahaan->persen_bpjs_jht_karyawan ?? 2.0); // Default 2%

    // JHT biasanya tidak ada batas gaji maksimal, tapi kita cek jika ada
    return ($gajiPokok * $persenJht) / 100;
  }

  /**
   * Hitung BPJS Jaminan Pensiun (JP)
   */
  private function calculateBpjsJp(float $gajiPokok, Perusahaan $perusahaan): float
  {
    $persenJp = (float) ($perusahaan->persen_bpjs_jp_karyawan ?? 1.0); // Default 1%
    $batasGaji = (float) ($perusahaan->batas_gaji_bpjs_pensiun ?? 0);

    // Jika ada batas gaji, gunakan yang terkecil antara gaji pokok atau batas gaji
    $gajiYangDihitung = $batasGaji > 0 ? min($gajiPokok, $batasGaji) : $gajiPokok;

    return ($gajiYangDihitung * $persenJp) / 100;
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

    // Fallback: ambil perusahaan pertama (jika hanya ada 1 perusahaan)
    return Perusahaan::first();
  }

  /**
   * Return empty BPJS data structure
   */
  private function getEmptyBpjsData(): array
  {
    return [
      'bpjs_kesehatan' => 0,
      'bpjs_jht' => 0,
      'bpjs_jp' => 0,
      'total_bpjs' => 0,
      'breakdown' => [
        'gaji_pokok' => 0,
        'persen_kesehatan' => 0,
        'persen_jht' => 0,
        'persen_jp' => 0,
        'batas_kesehatan' => 0,
        'batas_pensiun' => 0,
      ]
    ];
  }

  /**
   * Get BPJS total only (for backward compatibility)
   */
  public function calculateTotalBpjs(Karyawan $karyawan): float
  {
    $bpjsData = $this->calculateBpjsDeductions($karyawan);
    return $bpjsData['total_bpjs'];
  }
}
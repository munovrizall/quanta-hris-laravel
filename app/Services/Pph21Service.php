<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\TarifTer;
use Illuminate\Support\Facades\Log;

class Pph21Service
{
  /**
   * Menghitung potongan PPh 21 bulanan untuk seorang karyawan berdasarkan metode TER.
   * FIXED: Menggunakan penghasilan bruto, bukan hanya gaji pokok
   *
   * @param Karyawan $karyawan
   * @param float|null $penghasilanBruto - Jika tidak disediakan, akan dihitung otomatis
   * @return float
   */
  public function calculateMonthlyPph21Deduction(Karyawan $karyawan, ?float $penghasilanBruto = null): float
  {
    // 1. Hitung penghasilan bruto jika tidak disediakan
    if ($penghasilanBruto === null) {
      $penghasilanBruto = $this->calculatePenghasilanBruto($karyawan);
    }

    $golonganPtkp = $karyawan->golonganPtkp;

    // Jika karyawan tidak punya penghasilan atau golongan PTKP, tidak ada pajak.
    if ($penghasilanBruto <= 0 || !$golonganPtkp) {
      Log::info("PPh21: Karyawan {$karyawan->karyawan_id} tidak ada penghasilan atau PTKP");
      return 0;
    }

    // 2. Tentukan Kategori TER dari golongan PTKP
    $kategoriTerId = $golonganPtkp->kategori_ter_id ?? null;

    if (!$kategoriTerId) {
      Log::warning("PPh21: Kategori TER tidak ditemukan untuk karyawan {$karyawan->karyawan_id}");
      return 0;
    }

    // 3. Cari tarif yang sesuai di tabel tarif_ter berdasarkan PENGHASILAN BRUTO
    $tarifTer = TarifTer::where('kategori_ter_id', $kategoriTerId)
      ->where('batas_bawah', '<=', $penghasilanBruto)
      ->where(function ($query) use ($penghasilanBruto) {
        $query->where('batas_atas', '>=', $penghasilanBruto)
          ->orWhereNull('batas_atas');
      })
      ->first();

    // Jika tarif tidak ditemukan, catat dan kembalikan 0
    if (!$tarifTer) {
      Log::warning("PPh21: Tarif TER tidak ditemukan untuk karyawan {$karyawan->karyawan_id} dengan penghasilan Rp " . number_format($penghasilanBruto, 0, ',', '.'));
      return 0;
    }

    // 4. Hitung PPh 21 dari PENGHASILAN BRUTO
    $potonganPph21 = $penghasilanBruto * $tarifTer->tarif;

    // *** DEBUG LOG - DETAILED PPh21 CALCULATION ***
    Log::info("=== PPh21 CALCULATION: {$karyawan->nama_lengkap} ({$karyawan->karyawan_id}) ===", [
      'penghasilan_bruto' => 'Rp ' . number_format($penghasilanBruto, 0, ',', '.'),
      'golongan_ptkp' => $golonganPtkp->golongan ?? 'N/A',
      'kategori_ter_id' => $kategoriTerId,
      'tarif_ter' => [
        'id' => $tarifTer->id,
        'tarif' => $tarifTer->tarif,
        'tarif_persen' => ($tarifTer->tarif * 100) . '%',
        'batas_bawah' => 'Rp ' . number_format($tarifTer->batas_bawah, 0, ',', '.'),
        'batas_atas' => $tarifTer->batas_atas ? 'Rp ' . number_format($tarifTer->batas_atas, 0, ',', '.') : 'Unlimited',
      ],
      'calculation' => [
        'formula' => "Rp " . number_format($penghasilanBruto, 0, ',', '.') . " × " . ($tarifTer->tarif * 100) . "%",
        'result' => 'Rp ' . number_format($potonganPph21, 0, ',', '.'),
      ]
    ]);

    // Kembalikan hasil perhitungan, dibulatkan ke rupiah terdekat
    return round($potonganPph21);
  }

  /**
   * Menghitung penghasilan bruto karyawan (gaji + tunjangan + lembur)
   * 
   * @param Karyawan $karyawan
   * @return float
   */
  private function calculatePenghasilanBruto(Karyawan $karyawan): float
  {
    try {
      $gajiPokok = (float) ($karyawan->gaji_pokok ?? 0);

      // Hitung tunjangan menggunakan TunjanganService
      $tunjanganService = new TunjanganService();
      $totalTunjangan = $tunjanganService->getTotalTunjangan($karyawan);

      // Untuk saat ini, lembur dianggap 0 karena perlu periode spesifik
      $totalLembur = 0;

      $penghasilanBruto = $gajiPokok + $totalTunjangan + $totalLembur;

      Log::info("PPh21 PenghasilanBruto Calculation for {$karyawan->karyawan_id}:", [
        'gaji_pokok' => $gajiPokok,
        'total_tunjangan' => $totalTunjangan,
        'total_lembur' => $totalLembur,
        'penghasilan_bruto' => $penghasilanBruto
      ]);

      return $penghasilanBruto;

    } catch (\Exception $e) {
      Log::error("Error calculating penghasilan bruto for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Menghitung PPh21 dengan breakdown komponen yang jelas
   * 
   * @param Karyawan $karyawan
   * @param float $gajiPokok
   * @param float $totalTunjangan
   * @param float $totalLembur
   * @return array
   */
  public function calculatePph21WithBreakdown(Karyawan $karyawan, float $gajiPokok, float $totalTunjangan, float $totalLembur = 0): array
  {
    $penghasilanBruto = $gajiPokok + $totalTunjangan + $totalLembur;
    $pph21Amount = $this->calculateMonthlyPph21Deduction($karyawan, $penghasilanBruto);

    $golonganPtkp = $karyawan->golonganPtkp;
    $kategoriTerId = $golonganPtkp->kategori_ter_id ?? null;

    $tarifTer = null;
    if ($kategoriTerId && $penghasilanBruto > 0) {
      $tarifTer = TarifTer::where('kategori_ter_id', $kategoriTerId)
        ->where('batas_bawah', '<=', $penghasilanBruto)
        ->where(function ($query) use ($penghasilanBruto) {
          $query->where('batas_atas', '>=', $penghasilanBruto)
            ->orWhereNull('batas_atas');
        })
        ->first();
    }

    return [
      'pph21_amount' => $pph21Amount,
      'penghasilan_bruto' => $penghasilanBruto,
      'components' => [
        'gaji_pokok' => $gajiPokok,
        'total_tunjangan' => $totalTunjangan,
        'total_lembur' => $totalLembur,
      ],
      'tarif_info' => [
        'tarif' => $tarifTer?->tarif ?? 0,
        'tarif_persen' => $tarifTer ? ($tarifTer->tarif * 100) . '%' : '0%',
        'batas_bawah' => $tarifTer?->batas_bawah ?? 0,
        'batas_atas' => $tarifTer?->batas_atas,
      ],
      'ptkp_info' => [
        'golongan_ptkp' => $golonganPtkp?->golongan ?? 'N/A',
        'kategori_ter' => $golonganPtkp?->kategoriTer?->nama_kategori ?? 'N/A',
        'kategori_ter_id' => $kategoriTerId,
      ]
    ];
  }
}
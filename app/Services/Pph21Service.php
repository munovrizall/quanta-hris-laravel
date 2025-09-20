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
      Log::info("PPh21 = 0 untuk karyawan {$karyawan->karyawan_id}: penghasilan_bruto={$penghasilanBruto}, ptkp=" . ($golonganPtkp ? 'ada' : 'tidak ada'));
      return 0.0;
    }

    // 2. Tentukan Kategori TER dari golongan PTKP
    $kategoriTerId = $golonganPtkp->kategori_ter_id ?? null;

    if (!$kategoriTerId) {
      Log::warning("Karyawan ID {$karyawan->karyawan_id} tidak memiliki kategori TER pada golongan PTKP-nya. PTKP ID: {$golonganPtkp->golongan_ptkp_id}");
      return 0.0;
    }

    // 3. Cari tarif yang sesuai di tabel tarif_ter berdasarkan PENGHASILAN BRUTO
    $tarifTer = TarifTer::where('kategori_ter_id', $kategoriTerId)
      ->where('batas_bawah', '<=', $penghasilanBruto)
      ->where('batas_atas', '>=', $penghasilanBruto)
      ->first();

    // Jika tarif tidak ditemukan, catat dan kembalikan 0
    if (!$tarifTer) {
      Log::warning("Tarif TER tidak ditemukan untuk Karyawan ID {$karyawan->karyawan_id} dengan penghasilan bruto {$penghasilanBruto} pada kategori {$kategoriTerId}.");

      // Debug: tampilkan semua tarif untuk kategori ini
      $availableTarifs = TarifTer::where('kategori_ter_id', $kategoriTerId)->get();
      Log::info("Tarif yang tersedia untuk kategori {$kategoriTerId}:", $availableTarifs->toArray());

      return 0.0;
    }

    // 4. Hitung PPh 21 dari PENGHASILAN BRUTO
    $potonganPph21 = $penghasilanBruto * $tarifTer->tarif;

    // *** DEBUG LOG - DETAILED PPh21 CALCULATION ***
    Log::info("=== PPh21 CALCULATION: {$karyawan->nama_lengkap} ({$karyawan->karyawan_id}) ===", [
      'breakdown_penghasilan' => [
        'gaji_pokok' => 'Rp ' . number_format($karyawan->gaji_pokok, 0, ',', '.'),
        'penghasilan_bruto' => 'Rp ' . number_format($penghasilanBruto, 0, ',', '.'),
      ],
      'ptkp_info' => [
        'golongan_ptkp' => $golonganPtkp->nama_golongan_ptkp ?? 'N/A',
        'kategori_ter_id' => $kategoriTerId,
      ],
      'tarif_info' => [
        'tarif_ter_id' => $tarifTer->tarif_ter_id ?? 'N/A',
        'batas_bawah' => 'Rp ' . number_format($tarifTer->batas_bawah, 0, ',', '.'),
        'batas_atas' => 'Rp ' . number_format($tarifTer->batas_atas, 0, ',', '.'),
        'tarif_persen' => ($tarifTer->tarif * 100) . '%',
        'tarif_decimal' => $tarifTer->tarif,
      ],
      'calculation' => [
        'formula' => "Rp {$penghasilanBruto} × {$tarifTer->tarif}",
        'pph21_amount' => 'Rp ' . number_format($potonganPph21, 0, ',', '.'),
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
      $gajiPokok = (float) $karyawan->gaji_pokok;

      // Hitung tunjangan menggunakan TunjanganService
      $tunjanganService = new TunjanganService();
      $tunjanganData = $tunjanganService->calculateAllTunjangan($karyawan);
      $totalTunjangan = $tunjanganData['total_tunjangan'];

      // Untuk sekarang, abaikan lembur dalam perhitungan otomatis
      // karena data lembur biasanya dihitung per periode dan sudah disediakan dari luar
      $totalLembur = 0; // Akan di-override dari parameter jika ada

      $penghasilanBruto = $gajiPokok + $totalTunjangan + $totalLembur;

      Log::info("Penghasilan bruto otomatis untuk {$karyawan->nama_lengkap}: Gaji({$gajiPokok}) + Tunjangan({$totalTunjangan}) + Lembur({$totalLembur}) = {$penghasilanBruto}");

      return $penghasilanBruto;

    } catch (\Exception $e) {
      Log::error("Error calculating penghasilan bruto for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());

      // Fallback ke gaji pokok saja
      return (float) $karyawan->gaji_pokok;
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

    // Dapatkan info tarif
    $tarifTer = null;
    if ($kategoriTerId && $penghasilanBruto > 0) {
      $tarifTer = TarifTer::where('kategori_ter_id', $kategoriTerId)
        ->where('batas_bawah', '<=', $penghasilanBruto)
        ->where('batas_atas', '>=', $penghasilanBruto)
        ->first();
    }

    return [
      'pph21_amount' => $pph21Amount,
      'penghasilan_bruto' => $penghasilanBruto,
      'breakdown_penghasilan' => [
        'gaji_pokok' => $gajiPokok,
        'total_tunjangan' => $totalTunjangan,
        'total_lembur' => $totalLembur,
      ],
      'ptkp_info' => [
        'golongan_ptkp' => $golonganPtkp->nama_golongan_ptkp ?? 'N/A',
        'kategori_ter' => $golonganPtkp->kategoriTer->nama_kategori ?? 'N/A',
      ],
      'tarif_info' => [
        'tarif_persen' => $tarifTer ? ($tarifTer->tarif * 100) : 0,
        'batas_bawah' => $tarifTer->batas_bawah ?? 0,
        'batas_atas' => $tarifTer->batas_atas ?? 0,
      ]
    ];
  }
}
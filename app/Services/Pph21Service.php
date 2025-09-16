<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\TarifTer;
use Illuminate\Support\Facades\Log;

class Pph21Service
{
  /**
   * Menghitung potongan PPh 21 bulanan untuk seorang karyawan berdasarkan metode TER.
   *
   * @param Karyawan $karyawan
   * @return float
   */
  public function calculateMonthlyPph21Deduction(Karyawan $karyawan): float
  {
    // 1. Ambil data yang diperlukan dari karyawan
    $gajiBruto = (float) $karyawan->gaji_pokok;
    $golonganPtkp = $karyawan->golonganPtkp;

    // Jika karyawan tidak punya gaji atau golongan PTKP, tidak ada pajak.
    if ($gajiBruto <= 0 || !$golonganPtkp) {
      Log::info("PPh21 = 0 untuk karyawan {$karyawan->karyawan_id}: gaji={$gajiBruto}, ptkp=" . ($golonganPtkp ? 'ada' : 'tidak ada'));
      return 0.0;
    }

    // 2. Tentukan Kategori TER dari golongan PTKP
    // Periksa field yang benar - sesuaikan dengan struktur database Anda
    $kategoriTerId = $golonganPtkp->kategori_ter_id ?? null;
    
    if (!$kategoriTerId) {
      Log::warning("Karyawan ID {$karyawan->karyawan_id} tidak memiliki kategori TER pada golongan PTKP-nya. PTKP ID: {$golonganPtkp->golongan_ptkp_id}");
      return 0.0;
    }

    // 3. Cari tarif yang sesuai di tabel tarif_ter
    $tarifTer = TarifTer::where('kategori_ter_id', $kategoriTerId) // Pastikan nama field ini benar
      ->where('batas_bawah', '<=', $gajiBruto)
      ->where('batas_atas', '>=', $gajiBruto)
      ->first();

    // Jika tarif tidak ditemukan (misal: gaji di luar rentang), catat dan kembalikan 0
    if (!$tarifTer) {
      Log::warning("Tarif TER tidak ditemukan untuk Karyawan ID {$karyawan->karyawan_id} dengan gaji bruto {$gajiBruto} pada kategori {$kategoriTerId}.");
      
      // Debug: tampilkan semua tarif untuk kategori ini
      $availableTarifs = TarifTer::where('kategori_ter_id', $kategoriTerId)->get();
      Log::info("Tarif yang tersedia untuk kategori {$kategoriTerId}:", $availableTarifs->toArray());
      
      return 0.0;
    }

    // 4. Hitung PPh 21
    $potonganPph21 = $gajiBruto * $tarifTer->tarif;

    // Kembalikan hasil perhitungan, dibulatkan ke rupiah terdekat jika perlu
    return round($potonganPph21);
  }
}
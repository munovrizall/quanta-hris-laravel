<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\Log;

class BpjsService
{
    /**
     * Menghitung total potongan BPJS untuk seorang karyawan - FIXED VERSION
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

            // Hitung masing-masing komponen BPJS dengan PERSENTASE YANG BENAR
            $bpjsKesehatan = $this->calculateBpjsKesehatan($gajiPokok, $perusahaan);
            $bpjsJht = $this->calculateBpjsJht($gajiPokok, $perusahaan);
            $bpjsJp = $this->calculateBpjsJp($gajiPokok, $perusahaan);

            $totalBpjs = $bpjsKesehatan + $bpjsJht + $bpjsJp;

            // *** DEBUG LOG - DETAILED BPJS BREAKDOWN ***
            Log::info("=== BPJS BREAKDOWN KARYAWAN: {$karyawan->nama_lengkap} ({$karyawan->karyawan_id}) ===", [
                'gaji_pokok' => 'Rp ' . number_format($gajiPokok, 0, ',', '.'),
                'perusahaan_config' => [
                    'persen_kesehatan' => $perusahaan->persen_bpjs_kesehatan ?? 'NULL',
                    'persen_jht' => $perusahaan->persen_bpjs_jht ?? 'NULL',
                    'persen_jp' => $perusahaan->persen_bpjs_jp ?? 'NULL',
                    'batas_kesehatan' => 'Rp ' . number_format($perusahaan->batas_gaji_bpjs_kesehatan ?? 0, 0, ',', '.'),
                    'batas_pensiun' => 'Rp ' . number_format($perusahaan->batas_gaji_bpjs_pensiun ?? 0, 0, ',', '.'),
                ],
                'calculations' => [
                    'bpjs_kesehatan' => 'Rp ' . number_format($bpjsKesehatan, 0, ',', '.'),
                    'bpjs_jht' => 'Rp ' . number_format($bpjsJht, 0, ',', '.'),
                    'bpjs_jp' => 'Rp ' . number_format($bpjsJp, 0, ',', '.'),
                    'total_bpjs' => 'Rp ' . number_format($totalBpjs, 0, ',', '.'),
                ],
                'verification' => [
                    'expected_4persen' => 'Rp ' . number_format($gajiPokok * 0.04, 0, ',', '.'),
                    'difference' => $totalBpjs != ($gajiPokok * 0.04) ? 'DIFFERENT' : 'MATCH',
                ]
            ]);

            return [
                'bpjs_kesehatan' => $bpjsKesehatan,
                'bpjs_jht' => $bpjsJht,
                'bpjs_jp' => $bpjsJp,
                'total_bpjs' => $totalBpjs,
                'breakdown' => [
                    'gaji_pokok' => $gajiPokok,
                    'persen_kesehatan' => $perusahaan->persen_bpjs_kesehatan ?? 1,
                    'persen_jht' => $perusahaan->persen_bpjs_jht ?? 2,
                    'persen_jp' => $perusahaan->persen_bpjs_jp ?? 1,
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
     * Hitung BPJS Kesehatan - FIXED
     */
    private function calculateBpjsKesehatan(float $gajiPokok, Perusahaan $perusahaan): float
    {
        // *** FIX: Gunakan nilai yang benar dari database atau default ***
        $persenKesehatan = (float) ($perusahaan->persen_bpjs_kesehatan ?? 0.01); // 1% (bukan 4%)
        $batasGaji = (float) ($perusahaan->batas_gaji_bpjs_kesehatan ?? 0);

        // Jika ada batas gaji, gunakan yang terkecil antara gaji pokok atau batas gaji
        $gajiYangDihitung = $batasGaji > 0 ? min($gajiPokok, $batasGaji) : $gajiPokok;

        // *** FIX: Pastikan persentase tidak dalam bentuk desimal 100x ***
        if ($persenKesehatan > 1) {
            // Jika nilai > 1, berarti sudah dalam persen (misal: 1.0 = 1%)
            return ($gajiYangDihitung * $persenKesehatan) / 100;
        } else {
            // Jika nilai <= 1, berarti sudah dalam desimal (misal: 0.01 = 1%)
            return $gajiYangDihitung * $persenKesehatan;
        }
    }

    /**
     * Hitung BPJS Jaminan Hari Tua (JHT) - FIXED
     */
    private function calculateBpjsJht(float $gajiPokok, Perusahaan $perusahaan): float
    {
        // *** FIX: Gunakan nilai yang benar dari database atau default ***
        $persenJht = (float) ($perusahaan->persen_bpjs_jht ?? 0.02); // 2%

        // *** FIX: Pastikan persentase tidak dalam bentuk desimal 100x ***
        if ($persenJht > 1) {
            return ($gajiPokok * $persenJht) / 100;
        } else {
            return $gajiPokok * $persenJht;
        }
    }

    /**
     * Hitung BPJS Jaminan Pensiun (JP) - FIXED
     */
    private function calculateBpjsJp(float $gajiPokok, Perusahaan $perusahaan): float
    {
        // *** FIX: Gunakan nilai yang benar dari database atau default ***
        $persenJp = (float) ($perusahaan->persen_bpjs_jp ?? 0.01); // 1%
        $batasGaji = (float) ($perusahaan->batas_gaji_bpjs_pensiun ?? 10547400);

        // Jika ada batas gaji, gunakan yang terkecil antara gaji pokok atau batas gaji
        $gajiYangDihitung = $batasGaji > 0 ? min($gajiPokok, $batasGaji) : $gajiPokok;

        // *** FIX: Pastikan persentase tidak dalam bentuk desimal 100x ***
        if ($persenJp > 1) {
            return ($gajiYangDihitung * $persenJp) / 100;
        } else {
            return $gajiYangDihitung * $persenJp;
        }
    }

    /**
     * Get perusahaan dari karyawan - IMPROVED
     */
    private function getPerusahaan(Karyawan $karyawan): ?Perusahaan
    {
        try {
            // Jika karyawan punya relasi perusahaan langsung
            if ($karyawan->relationLoaded('perusahaan') && $karyawan->perusahaan) {
                return $karyawan->perusahaan;
            }

            // Jika ada field perusahaan_id di tabel karyawan
            if ($karyawan->perusahaan_id) {
                return Perusahaan::find($karyawan->perusahaan_id);
            }

            // Fallback: ambil perusahaan pertama (jika hanya ada 1 perusahaan)
            $perusahaan = Perusahaan::first();
            
            if (!$perusahaan) {
                Log::warning("No perusahaan found in database");
            }
            
            return $perusahaan;
        } catch (\Exception $e) {
            Log::error("Error getting perusahaan for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
            return null;
        }
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
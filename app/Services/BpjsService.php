<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\Log;

class BpjsService
{
    /**
     * Menghitung total potongan BPJS untuk seorang karyawan - FIXED VERSION WITH CORRECT BASE
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
            $tunjanganTetap = (float) ($karyawan->tunjangan_jabatan ?? 0); // Tunjangan tetap

            // DASAR PERHITUNGAN BPJS = GAJI POKOK + TUNJANGAN TETAP
            $dasarBpjs = $gajiPokok + $tunjanganTetap;

            if ($gajiPokok <= 0) {
                return $this->getEmptyBpjsData();
            }

            // Hitung masing-masing komponen BPJS dengan DASAR YANG BENAR
            $bpjsKesehatan = $this->calculateBpjsKesehatan($dasarBpjs, $perusahaan);
            $bpjsJht = $this->calculateBpjsJht($gajiPokok, $perusahaan); // JHT tetap pakai gaji pokok saja
            $bpjsJp = $this->calculateBpjsJp($dasarBpjs, $perusahaan);

            $totalBpjs = $bpjsKesehatan + $bpjsJht + $bpjsJp;

            // ROUND TO INTEGER - NO DECIMALS
            $bpjsKesehatan = round($bpjsKesehatan);
            $bpjsJht = round($bpjsJht);
            $bpjsJp = round($bpjsJp);
            $totalBpjs = round($totalBpjs);

            return [
                'bpjs_kesehatan' => $bpjsKesehatan,
                'bpjs_jht' => $bpjsJht,
                'bpjs_jp' => $bpjsJp,
                'total_bpjs' => $totalBpjs,
                'breakdown' => [
                    'gaji_pokok' => $gajiPokok,
                    'tunjangan_tetap' => $tunjanganTetap,
                    'dasar_bpjs' => $dasarBpjs,
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
     * Hitung BPJS Kesehatan - FIXED: MENGGUNAKAN GAJI POKOK + TUNJANGAN TETAP
     */
    private function calculateBpjsKesehatan(float $dasarBpjs, Perusahaan $perusahaan): float
    {
        $persenKesehatan = (float) ($perusahaan->persen_bpjs_kesehatan ?? 0.01); // 1%
        $batasGaji = (float) ($perusahaan->batas_gaji_bpjs_kesehatan ?? 0);

        $gajiYangDihitung = $batasGaji > 0 ? min($dasarBpjs, $batasGaji) : $dasarBpjs;

        if ($persenKesehatan > 1) {
            return round(($gajiYangDihitung * $persenKesehatan) / 100);
        } else {
            return round($gajiYangDihitung * $persenKesehatan);
        }
    }

    /**
     * Hitung BPJS Jaminan Hari Tua (JHT) - TETAP MENGGUNAKAN GAJI POKOK SAJA
     */
    private function calculateBpjsJht(float $gajiPokok, Perusahaan $perusahaan): float
    {
        $persenJht = (float) ($perusahaan->persen_bpjs_jht ?? 0.02); // 2%

        if ($persenJht > 1) {
            return round(($gajiPokok * $persenJht) / 100);
        } else {
            return round($gajiPokok * $persenJht);
        }
    }

    /**
     * Hitung BPJS Jaminan Pensiun (JP) - FIXED: MENGGUNAKAN GAJI POKOK + TUNJANGAN TETAP
     */
    private function calculateBpjsJp(float $dasarBpjs, Perusahaan $perusahaan): float
    {
        $persenJp = (float) ($perusahaan->persen_bpjs_jp ?? 0.01); // 1%
        $batasGaji = (float) ($perusahaan->batas_gaji_bpjs_pensiun ?? 10547400);

        $gajiYangDihitung = $batasGaji > 0 ? min($dasarBpjs, $batasGaji) : $dasarBpjs;

        if ($persenJp > 1) {
            return round(($gajiYangDihitung * $persenJp) / 100);
        } else {
            return round($gajiYangDihitung * $persenJp);
        }
    }

    /**
     * Get perusahaan dari karyawan - IMPROVED
     */
    private function getPerusahaan(Karyawan $karyawan): ?Perusahaan
    {
        try {
            if ($karyawan->relationLoaded('perusahaan') && $karyawan->perusahaan) {
                return $karyawan->perusahaan;
            }

            if ($karyawan->perusahaan_id) {
                return Perusahaan::find($karyawan->perusahaan_id);
            }

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
                'tunjangan_tetap' => 0,
                'dasar_bpjs' => 0,
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
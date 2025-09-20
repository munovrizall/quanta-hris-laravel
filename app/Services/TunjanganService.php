<?php

namespace App\Services;

use App\Models\Karyawan;
use Illuminate\Support\Facades\Log;

class TunjanganService
{
    /**
     * Hitung semua komponen tunjangan untuk karyawan
     *
     * @param Karyawan $karyawan
     * @return array
     */
    public function calculateAllTunjangan(Karyawan $karyawan): array
    {
        try {
            // Ambil data tunjangan dari database - SUDAH BULANAN
            $tunjanganJabatan = (float) ($karyawan->tunjangan_jabatan ?? 0);
            $tunjanganMakanBulanan = (float) ($karyawan->tunjangan_makan_bulanan ?? 0);
            $tunjanganTransportBulanan = (float) ($karyawan->tunjangan_transport_bulanan ?? 0);

            // Total semua tunjangan
            $totalTunjangan = $tunjanganJabatan + $tunjanganMakanBulanan + $tunjanganTransportBulanan;

            return [
                'tunjangan_jabatan' => $tunjanganJabatan,
                'tunjangan_makan_bulanan' => $tunjanganMakanBulanan,
                'tunjangan_transport_bulanan' => $tunjanganTransportBulanan,
                'total_tunjangan' => $totalTunjangan,
                'breakdown_detail' => [
                    'gaji_pokok' => (float) $karyawan->gaji_pokok,
                    'status_pernikahan' => $karyawan->status_pernikahan ?? 'Belum Menikah',
                ],
                'compliance_check' => $this->checkComplianceRule((float) $karyawan->gaji_pokok, $totalTunjangan)
            ];

        } catch (\Exception $e) {
            Log::error("Error calculating tunjangan for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
            return $this->getEmptyTunjanganData();
        }
    }

    /**
     * Check apakah tunjangan sudah sesuai dengan 75% rule
     */
    private function checkComplianceRule(float $gajiPokok, float $totalTunjangan): array
    {
        if ($gajiPokok <= 0) {
            return [
                'is_compliant' => false,
                'percentage' => 0,
                'message' => 'Gaji pokok tidak valid'
            ];
        }

        $totalGaji = $gajiPokok + $totalTunjangan;
        $percentageGajiPokok = ($gajiPokok / $totalGaji) * 100;

        return [
            'is_compliant' => $percentageGajiPokok >= 75,
            'percentage' => round($percentageGajiPokok, 1),
            'total_gaji' => $totalGaji,
            'message' => $percentageGajiPokok >= 75 ? 'Sesuai standar (75% rule)' : 'Tidak sesuai standar (< 75%)'
        ];
    }

    /**
     * Get total tunjangan only (untuk backward compatibility)
     */
    public function getTotalTunjangan(Karyawan $karyawan): float
    {
        $tunjanganData = $this->calculateAllTunjangan($karyawan);
        return $tunjanganData['total_tunjangan'];
    }

    /**
     * Get breakdown tunjangan for display - UPDATED FOR BULANAN
     */
    public function getTunjanganBreakdown(Karyawan $karyawan): array
    {
        $data = $this->calculateAllTunjangan($karyawan);
        
        $breakdown = [];

        // Tunjangan Jabatan
        if ($data['tunjangan_jabatan'] > 0) {
            $breakdown[] = [
                'type' => 'jabatan',
                'label' => 'Tunjangan Jabatan',
                'amount' => $data['tunjangan_jabatan'],
                'description' => 'Tunjangan berdasarkan posisi: ' . ($karyawan->jabatan ?? 'N/A')
            ];
        }

        // Tunjangan Makan - BULANAN
        if ($data['tunjangan_makan_bulanan'] > 0) {
            $harianEquivalent = round($data['tunjangan_makan_bulanan'] / 22, 0);
            $breakdown[] = [
                'type' => 'makan',
                'label' => 'Tunjangan Makan',
                'amount' => $data['tunjangan_makan_bulanan'],
                'description' => 'Rp ' . number_format($harianEquivalent, 0, ',', '.') . '/hari setara × 22 hari kerja'
            ];
        }

        // Tunjangan Transport - BULANAN
        if ($data['tunjangan_transport_bulanan'] > 0) {
            $harianEquivalent = round($data['tunjangan_transport_bulanan'] / 22, 0);
            $breakdown[] = [
                'type' => 'transport',
                'label' => 'Tunjangan Transport',
                'amount' => $data['tunjangan_transport_bulanan'],
                'description' => 'Rp ' . number_format($harianEquivalent, 0, ',', '.') . '/hari setara × 22 hari kerja'
            ];
        }

        return [
            'breakdown' => $breakdown,
            'total' => $data['total_tunjangan'],
            'compliance' => $data['compliance_check']
        ];
    }

    /**
     * Return empty tunjangan data structure
     */
    private function getEmptyTunjanganData(): array
    {
        return [
            'tunjangan_jabatan' => 0,
            'tunjangan_makan_bulanan' => 0,
            'tunjangan_transport_bulanan' => 0,
            'total_tunjangan' => 0,
            'breakdown_detail' => [
                'gaji_pokok' => 0,
                'status_pernikahan' => 'N/A',
            ],
            'compliance_check' => [
                'is_compliant' => false,
                'percentage' => 0,
                'total_gaji' => 0,
                'message' => 'Data tidak tersedia'
            ]
        ];
    }
}
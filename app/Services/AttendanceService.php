<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Lembur;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceService
{
    /**
     * Get absensi data for multiple karyawan - BATCH OPERATION
     */
    public function getAbsensiDataBatch(Collection $karyawanIds, Carbon $periodeStart, Carbon $periodeEnd): array
    {
        $absensiStats = Absensi::whereIn('karyawan_id', $karyawanIds->toArray())
            ->whereBetween('tanggal', [$periodeStart->format('Y-m-d'), $periodeEnd->format('Y-m-d')])
            ->selectRaw('karyawan_id, status_absensi, COUNT(*) as count')
            ->groupBy(['karyawan_id', 'status_absensi'])
            ->get()
            ->groupBy('karyawan_id');

        $result = [];
        foreach ($karyawanIds as $karyawanId) {
            $stats = $absensiStats->get($karyawanId, collect())->keyBy('status_absensi');

            $result[$karyawanId] = [
                'total_hadir' => $stats->get('Hadir')?->count ?? 0,
                'total_alfa' => $stats->get('Alfa')?->count ?? 0,
                'total_tidak_tepat' => $stats->get('Tidak Tepat')?->count ?? 0,
                'total_cuti' => $stats->get('Cuti')?->count ?? 0,
                'total_izin' => $stats->get('Izin')?->count ?? 0,
                'total_absensi' => $stats->sum('count'),
            ];
        }

        return $result;
    }

    /**
     * Get lembur data for multiple karyawan - BATCH OPERATION
     */
    public function getLemburDataBatch(Collection $karyawanIds, Carbon $periodeStart, Carbon $periodeEnd): array
    {
        $lemburStats = Lembur::whereIn('karyawan_id', $karyawanIds->toArray())
            ->whereBetween('tanggal_lembur', [$periodeStart->format('Y-m-d'), $periodeEnd->format('Y-m-d')])
            ->where('status_lembur', 'Disetujui')
            ->selectRaw('karyawan_id, 
                         COUNT(*) as total_sessions,
                         SEC_TO_TIME(SUM(TIME_TO_SEC(durasi_lembur))) as total_durasi,
                         SUM(COALESCE(total_insentif, 0)) as total_insentif')
            ->groupBy('karyawan_id')
            ->get()
            ->keyBy('karyawan_id');

        $result = [];
        foreach ($karyawanIds as $karyawanId) {
            $stats = $lemburStats->get($karyawanId);

            if ($stats) {
                $durasi = Carbon::createFromFormat('H:i:s', $stats->total_durasi);
                $totalHours = $durasi->hour + ($durasi->minute / 60) + ($durasi->second / 3600);

                $result[$karyawanId] = [
                    'total_lembur_hours' => round($totalHours, 1),
                    'total_lembur_sessions' => $stats->total_sessions,
                    'total_lembur_insentif' => $stats->total_insentif ?? 0,
                ];
            } else {
                $result[$karyawanId] = [
                    'total_lembur_hours' => 0.0,
                    'total_lembur_sessions' => 0,
                    'total_lembur_insentif' => 0,
                ];
            }
        }

        return $result;
    }

    /**
     * Get absensi data for single karyawan
     */
    public function getAbsensiDataSingle(string $karyawanId, Carbon $periodeStart, Carbon $periodeEnd): array
    {
        return $this->getAbsensiDataBatch(collect([$karyawanId]), $periodeStart, $periodeEnd)[$karyawanId] ?? [
            'total_hadir' => 0,
            'total_alfa' => 0,
            'total_tidak_tepat' => 0,
            'total_cuti' => 0,
            'total_izin' => 0,
            'total_absensi' => 0
        ];
    }

    /**
     * Get lembur data for single karyawan
     */
    public function getLemburDataSingle(string $karyawanId, Carbon $periodeStart, Carbon $periodeEnd): array
    {
        return $this->getLemburDataBatch(collect([$karyawanId]), $periodeStart, $periodeEnd)[$karyawanId] ?? [
            'total_lembur_hours' => 0.0,
            'total_lembur_sessions' => 0,
            'total_lembur_insentif' => 0,
        ];
    }

    /**
     * Get combined attendance and overtime data for multiple karyawan
     */
    public function getCombinedDataBatch(Collection $karyawanIds, Carbon $periodeStart, Carbon $periodeEnd): array
    {
        $absensiData = $this->getAbsensiDataBatch($karyawanIds, $periodeStart, $periodeEnd);
        $lemburData = $this->getLemburDataBatch($karyawanIds, $periodeStart, $periodeEnd);

        $result = [];
        foreach ($karyawanIds as $karyawanId) {
            $result[$karyawanId] = array_merge(
                $absensiData[$karyawanId] ?? [
                    'total_hadir' => 0,
                    'total_alfa' => 0,
                    'total_tidak_tepat' => 0,
                    'total_cuti' => 0,
                    'total_izin' => 0,
                    'total_absensi' => 0
                ],
                $lemburData[$karyawanId] ?? [
                    'total_lembur_hours' => 0.0,
                    'total_lembur_sessions' => 0,
                    'total_lembur_insentif' => 0,
                ]
            );
        }

        return $result;
    }

    /**
     * Get combined attendance and overtime data for single karyawan
     */
    public function getCombinedDataSingle(string $karyawanId, Carbon $periodeStart, Carbon $periodeEnd): array
    {
        return $this->getCombinedDataBatch(collect([$karyawanId]), $periodeStart, $periodeEnd)[$karyawanId] ?? [];
    }
}
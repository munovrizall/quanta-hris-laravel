<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Cuti;
use App\Models\Izin;
use App\Models\Lembur;
use App\Utils\MonthHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LaporanKinerjaService
{
    /**
     * Ambil daftar periode yang tersedia dari seluruh sumber data kinerja.
     */
    public function getAvailablePeriods(): Collection
    {
        $periods = collect();

        $periods = $periods->merge(
            Absensi::query()
                ->selectRaw('YEAR(tanggal) as tahun, MONTH(tanggal) as bulan')
                ->groupByRaw('YEAR(tanggal), MONTH(tanggal)')
                ->get()
        );

        $periods = $periods->merge(
            Lembur::query()
                ->selectRaw('YEAR(tanggal_lembur) as tahun, MONTH(tanggal_lembur) as bulan')
                ->groupByRaw('YEAR(tanggal_lembur), MONTH(tanggal_lembur)')
                ->get()
        );

        $periods = $periods->merge(
            Cuti::query()
                ->selectRaw('YEAR(tanggal_mulai) as tahun, MONTH(tanggal_mulai) as bulan')
                ->groupByRaw('YEAR(tanggal_mulai), MONTH(tanggal_mulai)')
                ->get()
        );

        $periods = $periods->merge(
            Izin::query()
                ->selectRaw('YEAR(tanggal_mulai) as tahun, MONTH(tanggal_mulai) as bulan')
                ->groupByRaw('YEAR(tanggal_mulai), MONTH(tanggal_mulai)')
                ->get()
        );

        return $periods
            ->unique(fn ($item) => sprintf('%04d-%02d', $item->tahun, $item->bulan))
            ->sortBy(fn ($item) => Carbon::createFromDate($item->tahun, $item->bulan)->format('Y-m'))
            ->values()
            ->map(function ($item) {
                $carbon = Carbon::createFromDate($item->tahun, $item->bulan, 1);

                return [
                    'tahun' => (int) $item->tahun,
                    'bulan' => (int) $item->bulan,
                    'label' => MonthHelper::formatPeriod((int) $item->bulan, (int) $item->tahun),
                    'key' => $carbon->format('Y-m'),
                ];
            });
    }

    /**
     * Ambil ringkasan kinerja karyawan untuk periode tertentu.
     */
    public function getMonthlySummary(int $year, int $month): array
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = (clone $periodStart)->endOfMonth();

        $attendanceQuery = Absensi::query()
            ->whereBetween('tanggal', [$periodStart->toDateString(), $periodEnd->toDateString()]);

        $totalAttendance = (clone $attendanceQuery)->count();
        $onTimeCount = (clone $attendanceQuery)->where('status_masuk', 'Tepat Waktu')->count();
        $lateCount = (clone $attendanceQuery)->where('status_masuk', 'Telat')->count();
        $earlyLeaveCount = (clone $attendanceQuery)->where('status_pulang', 'Pulang Cepat')->count();

        $onTimeRate = $totalAttendance > 0
            ? round(($onTimeCount / $totalAttendance) * 100, 2)
            : 0.0;

        $lateRate = $totalAttendance > 0
            ? round(($lateCount / $totalAttendance) * 100, 2)
            : 0.0;

        $lemburRecords = Lembur::query()
            ->where('status_lembur', 'Disetujui')
            ->whereBetween('tanggal_lembur', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get();

        $lemburSessions = $lemburRecords->count();
        $lemburHours = $lemburRecords->reduce(function (float $carry, Lembur $lembur) {
            $duration = $lembur->durasi_lembur;
            if (!$duration) {
                return $carry;
            }

            try {
                $parts = explode(':', $duration);
                $hours = (int) ($parts[0] ?? 0);
                $minutes = (int) ($parts[1] ?? 0);
                $seconds = (int) ($parts[2] ?? 0);

                return $carry + $hours + ($minutes / 60) + ($seconds / 3600);
            } catch (\Throwable $e) {
                return $carry;
            }
        }, 0.0);

        $cutiStats = $this->calculateLeaveStats(
            Cuti::query()
                ->where('status_cuti', 'Disetujui')
                ->where(function ($query) use ($periodStart, $periodEnd) {
                    $query
                        ->whereBetween('tanggal_mulai', [$periodStart, $periodEnd])
                        ->orWhereBetween('tanggal_selesai', [$periodStart, $periodEnd])
                        ->orWhere(function ($subQuery) use ($periodStart, $periodEnd) {
                            $subQuery
                                ->where('tanggal_mulai', '<', $periodStart)
                                ->where('tanggal_selesai', '>', $periodEnd);
                        });
                })
                ->get(),
            $periodStart,
            $periodEnd
        );

        $izinStats = $this->calculateLeaveStats(
            Izin::query()
                ->where('status_izin', 'Disetujui')
                ->where(function ($query) use ($periodStart, $periodEnd) {
                    $query
                        ->whereBetween('tanggal_mulai', [$periodStart, $periodEnd])
                        ->orWhereBetween('tanggal_selesai', [$periodStart, $periodEnd])
                        ->orWhere(function ($subQuery) use ($periodStart, $periodEnd) {
                            $subQuery
                                ->where('tanggal_mulai', '<', $periodStart)
                                ->where('tanggal_selesai', '>', $periodEnd);
                        });
                })
                ->get(),
            $periodStart,
            $periodEnd
        );

        return [
            'period' => [
                'year' => $year,
                'month' => $month,
                'label' => MonthHelper::formatPeriod($month, $year),
                'range' => sprintf('%s - %s', $periodStart->isoFormat('D MMM'), $periodEnd->isoFormat('D MMM YYYY')),
            ],
            'attendance' => [
                'total' => $totalAttendance,
                'on_time' => $onTimeCount,
                'on_time_rate' => $onTimeRate,
                'late' => $lateCount,
                'late_rate' => $lateRate,
                'early_leave' => $earlyLeaveCount,
            ],
            'lembur' => [
                'sessions' => $lemburSessions,
                'hours' => round($lemburHours, 1),
            ],
            'cuti' => $cutiStats,
            'izin' => $izinStats,
        ];
    }

    /**
     * Ambil data tren kinerja untuk beberapa bulan terakhir.
     */
    public function getMonthlyTrend(int $months = 6): array
    {
        $periods = $this->getAvailablePeriods();

        if ($periods->isEmpty()) {
            return [
                'labels' => [],
                'on_time_rate' => [],
                'late_rate' => [],
                'early_leave' => [],
            ];
        }

        $latestPeriods = $periods
            ->sortByDesc('key')
            ->take($months)
            ->sortBy('key')
            ->values();

        $labels = [];
        $onTimeRate = [];
        $lateRate = [];
        $earlyLeave = [];

        foreach ($latestPeriods as $period) {
            $summary = $this->getMonthlySummary($period['tahun'], $period['bulan']);

            $labels[] = $summary['period']['label'];
            $onTimeRate[] = $summary['attendance']['on_time_rate'];
            $lateRate[] = $summary['attendance']['late_rate'];
            $earlyLeave[] = $summary['attendance']['early_leave'];
        }

        return [
            'labels' => $labels,
            'on_time_rate' => $onTimeRate,
            'late_rate' => $lateRate,
            'early_leave' => $earlyLeave,
        ];
    }

    /**
     * Hitung statistik cuti/izin dengan mempertimbangkan rentang tanggal yang overlap.
     */
    protected function calculateLeaveStats(Collection $records, Carbon $periodStart, Carbon $periodEnd): array
    {
        $requests = $records->count();
        $totalDays = 0;

        foreach ($records as $record) {
            if (empty($record->tanggal_mulai)) {
                continue;
            }

            $startDate = Carbon::parse($record->tanggal_mulai);
            $endDate = $record->tanggal_selesai
                ? Carbon::parse($record->tanggal_selesai)
                : $startDate;

            $start = $startDate->copy()->max($periodStart);
            $end = $endDate->copy()->min($periodEnd);

            if ($start->greaterThan($end)) {
                continue;
            }

            $totalDays += $start->diffInDays($end) + 1;
        }

        return [
            'requests' => $requests,
            'days' => $totalDays,
        ];
    }

    /**
     * Ambil performa harian untuk periode tertentu (per hari dalam bulan).
     */
    public function getDailyPerformance(int $year, int $month): array
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = (clone $periodStart)->endOfMonth();

        // Get attendance data
        $rawStats = Absensi::query()
            ->whereBetween('tanggal', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->selectRaw('DATE(tanggal) as tanggal_date,
                COUNT(*) as total,
                SUM(CASE WHEN status_absensi = "Hadir" THEN 1 ELSE 0 END) as on_time,
                SUM(CASE WHEN status_absensi = "Tidak Tepat" THEN 1 ELSE 0 END) as late')
            ->groupByRaw('DATE(tanggal)')
            ->orderByRaw('DATE(tanggal) ASC')
            ->get()
            ->keyBy('tanggal_date');

        // Get overtime data
        $lemburStats = Lembur::query()
            ->where('status_lembur', 'Disetujui')
            ->whereBetween('tanggal_lembur', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->selectRaw('DATE(tanggal_lembur) as tanggal_date,
                COUNT(*) as total_lembur')
            ->groupByRaw('DATE(tanggal_lembur)')
            ->get()
            ->keyBy('tanggal_date');

        // Get leave data (cuti)
        $cutiStats = Cuti::query()
            ->where('status_cuti', 'Disetujui')
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('tanggal_mulai', [$periodStart->toDateString(), $periodEnd->toDateString()])
                      ->orWhereBetween('tanggal_selesai', [$periodStart->toDateString(), $periodEnd->toDateString()])
                      ->orWhere(function ($subQuery) use ($periodStart, $periodEnd) {
                          $subQuery->where('tanggal_mulai', '<=', $periodStart->toDateString())
                                   ->where('tanggal_selesai', '>=', $periodEnd->toDateString());
                      });
            })
            ->get()
            ->flatMap(function ($cuti) use ($periodStart, $periodEnd) {
                $dates = [];
                $start = Carbon::parse($cuti->tanggal_mulai)->max($periodStart);
                $end = Carbon::parse($cuti->tanggal_selesai)->min($periodEnd);
                
                for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
                    $dates[] = $date->format('Y-m-d');
                }
                return $dates;
            })
            ->countBy()
            ->toArray();

        // Get permission data (izin)
        $izinStats = Izin::query()
            ->where('status_izin', 'Disetujui')
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('tanggal_mulai', [$periodStart->toDateString(), $periodEnd->toDateString()])
                      ->orWhereBetween('tanggal_selesai', [$periodStart->toDateString(), $periodEnd->toDateString()])
                      ->orWhere(function ($subQuery) use ($periodStart, $periodEnd) {
                          $subQuery->where('tanggal_mulai', '<=', $periodStart->toDateString())
                                   ->where('tanggal_selesai', '>=', $periodEnd->toDateString());
                      });
            })
            ->get()
            ->flatMap(function ($izin) use ($periodStart, $periodEnd) {
                $dates = [];
                $start = Carbon::parse($izin->tanggal_mulai)->max($periodStart);
                $end = Carbon::parse($izin->tanggal_selesai)->min($periodEnd);
                
                for ($date = $start->copy(); $date->lessThanOrEqualTo($end); $date->addDay()) {
                    $dates[] = $date->format('Y-m-d');
                }
                return $dates;
            })
            ->countBy()
            ->toArray();

        $labels = [];
        $onTimeCounts = [];
        $lateCounts = [];
        $lemburCounts = [];
        $cutiCounts = [];
        $izinCounts = [];

        for ($date = $periodStart->copy(); $date->lessThanOrEqualTo($periodEnd); $date->addDay()) {
            $key = $date->format('Y-m-d');
            $attendanceStats = $rawStats->get($key);
            $lemburStat = $lemburStats->get($key);

            $onTime = $attendanceStats->on_time ?? 0;
            $late = $attendanceStats->late ?? 0;
            $lembur = $lemburStat->total_lembur ?? 0;
            $cuti = $cutiStats[$key] ?? 0;
            $izin = $izinStats[$key] ?? 0;

            $labels[] = $date->translatedFormat('d M');
            $onTimeCounts[] = (int) $onTime;
            $lateCounts[] = (int) $late;
            $lemburCounts[] = (int) $lembur;
            $cutiCounts[] = (int) $cuti;
            $izinCounts[] = (int) $izin;
        }

        return [
            'labels' => $labels,
            'on_time' => $onTimeCounts,
            'late' => $lateCounts,
            'lembur' => $lemburCounts,
            'cuti' => $cutiCounts,
            'izin' => $izinCounts,
        ];
    }
}

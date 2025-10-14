<?php

namespace App\Services;

use App\Models\Penggajian;
use App\Utils\MonthHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LaporanKeuanganService
{
    /**
     * Ambil daftar periode penggajian yang tersedia.
     */
    public function getAvailablePeriods(): Collection
    {
        return Penggajian::query()
            ->selectRaw('periode_tahun as tahun, periode_bulan as bulan')
            ->groupBy('periode_tahun', 'periode_bulan')
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan')
            ->get()
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
     * Ambil ringkasan keuangan gaji untuk periode tertentu.
     */
    public function getMonthlySummary(int $year, int $month): array
    {
        $periodStart = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $periodEnd = (clone $periodStart)->endOfMonth();

        $records = Penggajian::query()
            ->where('periode_tahun', $year)
            ->where('periode_bulan', $month)
            ->get();

        if ($records->isEmpty()) {
            return [];
        }

        $totalNetSalary = (float) $records->sum('gaji_bersih');
        $totalGrossIncome = (float) $records->sum('penghasilan_bruto');
        $totalAllowances = (float) $records->sum('total_tunjangan');
        $totalOvertime = (float) $records->sum('total_lembur');
        $totalDeductions = (float) $records->sum('total_potongan');

        $employeeCount = $records
            ->pluck('karyawan_id')
            ->filter()
            ->unique()
            ->count();

        $departmentBreakdown = Penggajian::query()
            ->selectRaw("COALESCE(k.departemen, 'Tidak Ditentukan') as departemen")
            ->selectRaw('SUM(penggajian.gaji_bersih) as total_gaji')
            ->selectRaw('COUNT(DISTINCT penggajian.karyawan_id) as jumlah_karyawan')
            ->leftJoin('karyawan as k', 'k.karyawan_id', '=', 'penggajian.karyawan_id')
            ->where('penggajian.periode_tahun', $year)
            ->where('penggajian.periode_bulan', $month)
            ->groupBy('departemen')
            ->orderByDesc('total_gaji')
            ->get()
            ->map(function ($row) use ($totalNetSalary) {
                $total = (float) $row->total_gaji;

                return [
                    'departemen' => $row->departemen,
                    'total_gaji' => $total,
                    'jumlah_karyawan' => (int) $row->jumlah_karyawan,
                    'persentase' => $totalNetSalary > 0
                        ? round(($total / $totalNetSalary) * 100, 2)
                        : 0.0,
                ];
            })
            ->all();

        return [
            'period' => [
                'year' => $year,
                'month' => $month,
                'label' => MonthHelper::formatPeriod($month, $year),
                'range' => sprintf(
                    '%s - %s',
                    $periodStart->isoFormat('D MMM'),
                    $periodEnd->isoFormat('D MMM YYYY')
                ),
            ],
            'totals' => [
                'total_salary' => $totalNetSalary,
                'gross_income' => $totalGrossIncome,
                'total_allowances' => $totalAllowances,
                'total_overtime' => $totalOvertime,
                'total_deductions' => $totalDeductions,
                'employee_count' => $employeeCount,
                'average_salary' => $employeeCount > 0
                    ? round($totalNetSalary / $employeeCount, 2)
                    : 0.0,
            ],
            'department_breakdown' => $departmentBreakdown,
        ];
    }

    /**
     * Ambil data tren total biaya gaji beberapa bulan terakhir.
     */
    public function getMonthlyTrend(int $months = 6): array
    {
        $periods = Penggajian::query()
            ->selectRaw('periode_tahun as tahun')
            ->selectRaw('periode_bulan as bulan')
            ->selectRaw('SUM(gaji_bersih) as total_gaji')
            ->groupBy('periode_tahun', 'periode_bulan')
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan')
            ->take($months)
            ->get()
            ->sortBy(fn ($item) => Carbon::createFromDate($item->tahun, $item->bulan)->format('Y-m'))
            ->values();

        return [
            'labels' => $periods
                ->map(fn ($item) => MonthHelper::formatPeriod((int) $item->bulan, (int) $item->tahun))
                ->all(),
            'totals' => $periods
                ->map(fn ($item) => (float) $item->total_gaji)
                ->all(),
            'keys' => $periods
                ->map(fn ($item) => Carbon::createFromDate($item->tahun, $item->bulan, 1)->format('Y-m'))
                ->all(),
        ];
    }
}

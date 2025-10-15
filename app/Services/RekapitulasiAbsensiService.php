<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Cuti;
use App\Models\Izin;
use App\Models\Karyawan;
use App\Models\Lembur;
use App\Utils\MonthHelper;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection as SupportCollection;

class RekapitulasiAbsensiService
{
    /**
     * Ambil periode default (bulan berjalan).
     */
    public function getDefaultPeriod(): array
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'label' => MonthHelper::formatPeriod((int) $start->format('m'), (int) $start->format('Y')),
        ];
    }

    /**
     * Mengonversi input tanggal menjadi periode Carbon yang valid.
     */
    public function resolvePeriod(?string $startDate, ?string $endDate): array
    {
        $default = $this->getDefaultPeriod();

        $start = Carbon::parse($startDate ?: $default['start'])->startOfDay();
        $end = Carbon::parse($endDate ?: $default['end'])->endOfDay();

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->clone()->startOfDay(), $start->clone()->endOfDay()];
        }

        return [$start, $end];
    }

    /**
     * Daftar periode (berdasarkan bulan) yang tersedia pada data absensi.
     */
    public function getAvailablePeriods(): SupportCollection
    {
        return Absensi::query()
            ->selectRaw('YEAR(tanggal) as tahun, MONTH(tanggal) as bulan')
            ->groupByRaw('YEAR(tanggal), MONTH(tanggal)')
            ->orderByRaw('tahun desc, bulan desc')
            ->get()
            ->map(function ($row) {
                $bulan = (int) $row->bulan;
                $tahun = (int) $row->tahun;

                return [
                    'key' => sprintf('%04d-%02d', $tahun, $bulan),
                    'label' => MonthHelper::formatPeriod($bulan, $tahun),
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'start' => Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString(),
                    'end' => Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString(),
                ];
            });
    }

    /**
     * Query builder untuk rekap absensi per karyawan.
     */
    public function getRekapQuery(Carbon $start, Carbon $end): Builder
    {
        return Absensi::query()
            ->select('karyawan_id')
            ->selectRaw('COUNT(*) as total_kehadiran')
            ->selectRaw('SUM(CASE WHEN status_masuk = "Tepat Waktu" THEN 1 ELSE 0 END) as tepat_waktu')
            ->selectRaw('SUM(CASE WHEN status_masuk = "Telat" THEN 1 ELSE 0 END) as terlambat')
            ->selectRaw('SUM(CASE WHEN status_absensi = "Alfa" THEN 1 ELSE 0 END) as alfa')
            ->selectRaw('SUM(CASE WHEN status_absensi = "Tidak Tepat" THEN 1 ELSE 0 END) as tidak_tepat')
            ->selectRaw('SUM(CASE WHEN status_pulang = "Pulang Cepat" THEN 1 ELSE 0 END) as pulang_cepat')
            ->selectRaw('ROUND(SUM(CASE WHEN status_masuk = "Tepat Waktu" THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0) * 100, 2) as persentase_tepat')
            ->selectRaw('MIN(tanggal) as periode_mulai')
            ->selectRaw('MAX(tanggal) as periode_selesai')
            ->with([
                'karyawan' => fn(EloquentBuilder $query) => $query
                    ->select('karyawan_id', 'nama_lengkap', 'jabatan', 'departemen')
                    ->withCount([
                        'lembur as lembur_disetujui_count' => fn(Builder $lemburQuery) => $lemburQuery
                            ->where('status_lembur', 'Disetujui')
                            ->whereBetween('tanggal_lembur', [$start->toDateString(), $end->toDateString()]),
                        'cuti as cuti_disetujui_count' => fn(Builder $cutiQuery) => $cutiQuery
                            ->where('status_cuti', 'Disetujui')
                            ->whereBetween('tanggal_mulai', [$start->toDateString(), $end->toDateString()]),
                        'izin as izin_disetujui_count' => fn(Builder $izinQuery) => $izinQuery
                            ->where('status_izin', 'Disetujui')
                            ->whereBetween('tanggal_mulai', [$start->toDateString(), $end->toDateString()]),
                    ]),
            ])
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->groupBy('karyawan_id');
    }

    /**
     * Ambil koleksi data rekap siap pakai.
     */
    public function getRekapData(Carbon $start, Carbon $end): SupportCollection
    {
        return $this->getRekapQuery($start, $end)
            ->orderBy('karyawan_id')
            ->get()
            ->map(function (Absensi $absensi) {
                /** @var Karyawan|null $karyawan */
                $karyawan = $absensi->karyawan;

                return [
                    'karyawan_id' => $absensi->karyawan_id,
                    'nama' => $karyawan?->nama_lengkap ?? '-',
                    'jabatan' => $karyawan?->jabatan ?? '-',
                    'departemen' => $karyawan?->departemen ?? '-',
                    'total_kehadiran' => (int) $absensi->total_kehadiran,
                    'tepat_waktu' => (int) $absensi->tepat_waktu,
                    'terlambat' => (int) $absensi->terlambat,
                    'tidak_tepat' => (int) $absensi->tidak_tepat,
                    'alfa' => (int) $absensi->alfa,
                    'pulang_cepat' => (int) $absensi->pulang_cepat,
                    'persentase_tepat' => (float) $absensi->persentase_tepat,
                    'lembur_disetujui' => (int) ($karyawan?->lembur_disetujui_count ?? 0),
                    'cuti_disetujui' => (int) ($karyawan?->cuti_disetujui_count ?? 0),
                    'izin_disetujui' => (int) ($karyawan?->izin_disetujui_count ?? 0),
                ];
            });
    }

    /**
     * Hitung ringkasan global untuk periode.
     */
    public function getSummary(Carbon $start, Carbon $end): array
    {
        $baseQuery = Absensi::query()
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()]);

        $total = (clone $baseQuery)->count();
        $tepat = (clone $baseQuery)->where('status_masuk', 'Tepat Waktu')->count();
        $telat = (clone $baseQuery)->where('status_masuk', 'Telat')->count();
        $tidakTepat = (clone $baseQuery)->where('status_absensi', 'Tidak Tepat')->count();
        $alfa = (clone $baseQuery)->where('status_absensi', 'Alfa')->count();
        $pulangCepat = (clone $baseQuery)->where('status_pulang', 'Pulang Cepat')->count();

        $karyawanTerlibat = (clone $baseQuery)->distinct('karyawan_id')->count('karyawan_id');

        $persentaseTepat = $total > 0 ? round(($tepat / $total) * 100, 2) : 0.0;
        $persentaseTelat = $total > 0 ? round(($telat / $total) * 100, 2) : 0.0;

        return [
            'periode' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => sprintf('%s - %s', $start->isoFormat('D MMM YYYY'), $end->isoFormat('D MMM YYYY')),
            ],
            'ringkasan' => [
                'total_kehadiran' => $total,
                'karyawan_terlibat' => $karyawanTerlibat,
                'tepat_waktu' => $tepat,
                'telat' => $telat,
                'persentase_tepat' => $persentaseTepat,
                'persentase_telat' => $persentaseTelat,
                'tidak_tepat' => $tidakTepat,
                'alfa' => $alfa,
                'pulang_cepat' => $pulangCepat,
            ],
        ];
    }

    /**
     * Data lengkap untuk keperluan PDF.
     */
    public function getPdfPayload(Carbon $start, Carbon $end): array
    {
        return [
            'summary' => $this->getSummary($start, $end),
            'records' => $this->getRekapData($start, $end),
            'judulDokumen' => 'Rekapitulasi Absensi Karyawan',
            'periode' => sprintf('%s - %s', $start->isoFormat('D MMM YYYY'), $end->isoFormat('D MMM YYYY')),
            'tanggalCetak' => Carbon::now()->format('d/m/Y H:i:s'),
        ];
    }
}

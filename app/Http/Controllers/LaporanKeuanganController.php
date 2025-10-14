<?php

namespace App\Http\Controllers;

use App\Services\LaporanKeuanganService;
use App\Utils\MonthHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LaporanKeuanganController extends Controller
{
    public function cetak(int $tahun, int $bulan)
    {
        try {
            $service = app(LaporanKeuanganService::class);

            $summary = $service->getMonthlySummary($tahun, $bulan);

            if (empty($summary)) {
                abort(404, 'Data laporan keuangan tidak ditemukan untuk periode tersebut');
            }

            $departmentBreakdown = $summary['department_breakdown'] ?? [];

            $pdfData = [
                'summary' => $summary,
                'departmentBreakdown' => $departmentBreakdown,
                'periode' => $summary['period']['label'],
                'judulDokumen' => 'Laporan Keuangan Penggajian',
                'tanggalCetak' => Carbon::now()->format('d/m/Y H:i:s'),
            ];

            $pdf = Pdf::loadView('pdf.laporan-keuangan', $pdfData);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
            ]);

            $filename = sprintf('laporan-keuangan-%d-%02d.pdf', $tahun, $bulan);

            return $pdf->download($filename);
        } catch (\Throwable $th) {
            Log::error('Gagal mencetak laporan keuangan: ' . $th->getMessage(), [
                'tahun' => $tahun,
                'bulan' => $bulan,
            ]);

            abort(500, 'Terjadi kesalahan saat membuat PDF laporan keuangan.');
        }
    }
}

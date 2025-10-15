<?php

namespace App\Http\Controllers;

use App\Services\RekapitulasiAbsensiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RekapitulasiAbsensiController extends Controller
{
    public function cetak(Request $request)
    {
        try {
            /** @var RekapitulasiAbsensiService $service */
            $service = app(RekapitulasiAbsensiService::class);

            [$start, $end] = $service->resolvePeriod(
                $request->query('start_date'),
                $request->query('end_date')
            );

            $payload = $service->getPdfPayload($start, $end);

            $records = $payload['records'];

            if (method_exists($records, 'isEmpty') ? $records->isEmpty() : empty($records)) {
                abort(404, 'Tidak ada data rekapitulasi absensi pada periode tersebut.');
            }

            $pdf = Pdf::loadView('pdf.rekapitulasi-absensi', $payload);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
            ]);

            $filename = sprintf(
                'rekapitulasi-absensi-%s-sd-%s.pdf',
                $start->format('Ymd'),
                $end->format('Ymd')
            );

            if ($request->boolean('download')) {
                return $pdf->download($filename);
            }

            return $pdf->stream($filename);
        } catch (\Throwable $th) {
            Log::error('Gagal mencetak rekapitulasi absensi: ' . $th->getMessage(), [
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
            ]);

            abort(500, 'Terjadi kesalahan saat membuat PDF rekapitulasi absensi.');
        }
    }
}

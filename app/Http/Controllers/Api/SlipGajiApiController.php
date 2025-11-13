<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Penggajian;
use App\Services\AbsensiService;
use App\Services\TunjanganService;
use App\Services\BpjsService;
use App\Services\LemburService;
use App\Services\Pph21Service;
use App\Services\PotonganService;
use App\Utils\MonthHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SlipGajiApiController extends Controller
{
    /**
     * List slip gaji yang tersedia untuk karyawan login (sudah ditransfer)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $slips = Penggajian::where('karyawan_id', $user->karyawan_id)
            ->where('status_penggajian', 'Disetujui')
            ->where('sudah_ditransfer', true)
            ->orderBy('periode_tahun', 'desc')
            ->orderBy('periode_bulan', 'desc')
            ->get(['penggajian_id', 'periode_bulan', 'periode_tahun', 'gaji_bersih', 'sudah_ditransfer']);

        $data = $slips->map(function ($row) {
            return [
                'penggajian_id' => $row->penggajian_id,
                'periode_bulan' => $row->periode_bulan,
                'periode_tahun' => $row->periode_tahun,
                'periode_label' => MonthHelper::formatPeriod($row->periode_bulan, $row->periode_tahun),
                'gaji_bersih' => $row->gaji_bersih,
                'sudah_ditransfer' => (bool) $row->sudah_ditransfer,
            ];
        });

        return ApiResponse::format(true, 200, 'Slip gaji tersedia berhasil diambil.', $data);
    }

    /**
     * Detail slip gaji (breakdown) untuk periode tertentu milik karyawan login
     */
    public function show(Request $request, int $tahun, int $bulan)
    {
        $user = $request->user();
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $penggajian = Penggajian::where('karyawan_id', $user->karyawan_id)
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->where('status_penggajian', 'Disetujui')
            ->where('sudah_ditransfer', true)
            ->with(['karyawan.golonganPtkp.kategoriTer'])
            ->first();

        if (!$penggajian) {
            return ApiResponse::format(false, 404, 'Slip gaji tidak ditemukan untuk periode tersebut.', null);
        }

        $detail = $this->buildSlipDetail($penggajian, $bulan, $tahun);

        return ApiResponse::format(true, 200, 'Detail slip gaji berhasil diambil.', $detail);
    }

    /**
     * Download PDF slip gaji individual untuk karyawan login (langsung attachment)
     */
    public function download(Request $request, int $tahun, int $bulan)
    {
        $user = $request->user();
        if (!$user || !$user->karyawan_id) {
            return ApiResponse::format(false, 401, 'Unauthorized', null);
        }

        $penggajian = Penggajian::where('karyawan_id', $user->karyawan_id)
            ->where('periode_bulan', $bulan)
            ->where('periode_tahun', $tahun)
            ->where('status_penggajian', 'Disetujui')
            ->where('sudah_ditransfer', true)
            ->with(['karyawan.golonganPtkp.kategoriTer'])
            ->first();

        if (!$penggajian) {
            return ApiResponse::format(false, 404, 'Slip gaji tidak ditemukan untuk periode tersebut.', null);
        }

        $karyawanData = $this->buildSlipDetail($penggajian, $bulan, $tahun);

        $pdfData = [
            'karyawan' => $karyawanData,
            'periode' => MonthHelper::formatPeriod($bulan, $tahun),
            'periodeBulan' => $bulan,
            'periodeTahun' => $tahun,
            'tanggalCetak' => Carbon::now()->format('d/m/Y H:i:s'),
            'judulDokumen' => 'Slip Gaji Karyawan'
        ];

        $pdf = Pdf::loadView('pdf.slip-gaji-individual', $pdfData);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);

        $namaFile = preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $karyawanData['nama_lengkap']));
        $filename = "Slip-Gaji-{$namaFile}-{$tahun}-{$bulan}.pdf";

        return $pdf->download($filename);
    }

    private function buildSlipDetail(Penggajian $detail, int $bulan, int $tahun): array
    {
        $periodeStart = Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $periodeEnd = Carbon::create($tahun, $bulan, 1)->endOfMonth();

        $karyawan = $detail->karyawan;

        $attendanceService = new AbsensiService();
        $attendanceData = $attendanceService->getCombinedDataBatch(collect([$karyawan->karyawan_id]), $periodeStart, $periodeEnd);
        $karyawanAttendance = $attendanceData[$karyawan->karyawan_id] ?? [
            'total_hadir' => 0,
            'total_alfa' => 0,
            'total_tidak_tepat' => 0,
            'total_cuti' => 0,
            'total_izin' => 0,
            'total_lembur_hours' => 0,
            'total_lembur_sessions' => 0,
        ];

        $tunjanganService = new TunjanganService();
        $bpjsService = new BpjsService();
        $lemburService = new LemburService();
        $pph21Service = new Pph21Service();
        $potonganService = new PotonganService();

        $tunjanganData = $tunjanganService->getTunjanganBreakdown($karyawan);
        $bpjsData = $bpjsService->calculateBpjsDeductions($karyawan);
        $lemburData = $lemburService->calculateTotalLemburForPeriode($karyawan, $periodeStart, $periodeEnd);
        $pph21Data = $pph21Service->calculatePph21WithBreakdown(
            $karyawan,
            $detail->gaji_pokok,
            $detail->total_tunjangan,
            $detail->total_lembur
        );
        $potonganAlfaData = $potonganService->calculateAlfaDeduction($karyawan, $karyawanAttendance['total_alfa']);
        $potonganTerlambatData = $potonganService->calculateKeterlambatanDeduction($karyawan, $karyawanAttendance['total_tidak_tepat']);

        $bpjsBreakdownWithDescriptions = [
            [
                'label' => 'BPJS Kesehatan',
                'amount' => $bpjsData['bpjs_kesehatan'],
                'description' => ((float) $bpjsData['breakdown']['persen_kesehatan'] * 100) . '% dari gaji pokok + tunjangan tetap'
            ],
            [
                'label' => 'BPJS JHT',
                'amount' => $bpjsData['bpjs_jht'],
                'description' => ((float) $bpjsData['breakdown']['persen_jht'] * 100) . '% dari gaji pokok'
            ],
            [
                'label' => 'BPJS JP',
                'amount' => $bpjsData['bpjs_jp'],
                'description' => ((float) $bpjsData['breakdown']['persen_jp'] * 100) . '% dari gaji pokok + tunjangan tetap'
            ],
        ];

        return [
            'detail_id' => $detail->penggajian_id,
            'karyawan_id' => $karyawan->karyawan_id,
            'nama_lengkap' => $karyawan->nama_lengkap,
            'jabatan' => $karyawan->jabatan,
            'departemen' => $karyawan->departemen ?? 'N/A',
            'total_hadir' => $karyawanAttendance['total_hadir'],
            'total_alfa' => $karyawanAttendance['total_alfa'],
            'total_tidak_tepat' => $karyawanAttendance['total_tidak_tepat'],
            'total_cuti' => $karyawanAttendance['total_cuti'],
            'total_izin' => $karyawanAttendance['total_izin'],
            'total_lembur' => $lemburData['total_jam'],
            'total_lembur_sessions' => $lemburData['total_sesi'],
            'gaji_pokok' => $detail->gaji_pokok,
            'tunjangan_total' => $detail->total_tunjangan,
            'tunjangan_breakdown' => $tunjanganData,
            'bpjs_breakdown' => [
                'breakdown' => $bpjsBreakdownWithDescriptions,
                'total_amount' => $bpjsData['total_bpjs'],
            ],
            'lembur_pay' => $detail->total_lembur,
            'potongan_total' => $detail->total_potongan,
            'total_gaji' => $detail->gaji_bersih,
            'penghasilan_bruto' => $detail->penghasilan_bruto,
            'penyesuaian' => $detail->penyesuaian,
            'catatan_penyesuaian' => $detail->catatan_penyesuaian,
            'pph21_detail' => [
                'jumlah' => $pph21Data['pph21_amount'],
                'tarif_persen' => $pph21Data['tarif_info']['tarif_persen'],
                'golongan_ptkp' => $pph21Data['ptkp_info']['golongan_ptkp'],
                'kategori_ter' => $pph21Data['ptkp_info']['kategori_ter'],
                'penghasilan_bruto' => $pph21Data['penghasilan_bruto'],
            ],
            'potongan_detail' => [
                'alfa' => [
                    'total_potongan' => $detail->potongan_alfa,
                    'potongan_per_hari' => $potonganAlfaData['potongan_per_hari'] ?? 0
                ],
                'keterlambatan' => [
                    'total_potongan' => $detail->potongan_terlambat,
                    'potongan_per_hari' => $potonganTerlambatData['potongan_per_hari'] ?? 0
                ],
                'bpjs' => $detail->potongan_bpjs,
                'pph21' => $detail->potongan_pph21,
            ],
            'periode_label' => MonthHelper::formatPeriod($bulan, $tahun),
        ];
    }
}

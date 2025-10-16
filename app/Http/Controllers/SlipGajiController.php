<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Response;

class SlipGajiController extends Controller
{
  private TunjanganService $tunjanganService;
  private BpjsService $bpjsService;
  private LemburService $lemburService;
  private Pph21Service $pph21Service;
  private PotonganService $potonganService;

  public function __construct()
  {
    $this->tunjanganService = new TunjanganService();
    $this->bpjsService = new BpjsService();
    $this->lemburService = new LemburService();
    $this->pph21Service = new Pph21Service();
    $this->potonganService = new PotonganService();
  }

  /**
   * Cetak semua slip gaji untuk periode tertentu
   */
  public function cetakSemuaSlipGaji(int $tahun, int $bulan)
  {
    try {
      // Validasi periode
      $penggajianExists = Penggajian::where('periode_bulan', $bulan)
        ->where('periode_tahun', $tahun)
        ->where('status_penggajian', 'Disetujui')
        ->where('sudah_ditransfer', true)
        ->exists();

      if (!$penggajianExists) {
        abort(404, 'Data penggajian tidak ditemukan atau belum disetujui untuk periode tersebut');
      }

      // Ambil semua data penggajian untuk periode
      $penggajianData = Penggajian::where('periode_bulan', $bulan)
        ->where('periode_tahun', $tahun)
        ->where('status_penggajian', 'Disetujui')
        ->where('sudah_ditransfer', true)
        ->with(['karyawan.golonganPtkp.kategoriTer'])
        ->orderBy('karyawan_id')
        ->get();

      // Process data untuk semua karyawan
      $processedData = $this->processKaryawanDataForPdf($penggajianData, $bulan, $tahun);

      // Data untuk PDF
      $pdfData = [
        'karyawanData' => $processedData,
        'periode' => MonthHelper::formatPeriod($bulan, $tahun),
        'periodeBulan' => $bulan,
        'periodeTahun' => $tahun,
        'totalKaryawan' => count($processedData),
        'tanggalCetak' => Carbon::now()->format('d/m/Y H:i:s'),
        'judulDokumen' => 'Slip Gaji Semua Karyawan'
      ];

      // Generate PDF
      $pdf = PDF::loadView('pdf.slip-gaji-semua', $pdfData);
      $pdf->setPaper('A4', 'portrait');
      $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);

      $filename = "slip-gaji-semua-{$tahun}-{$bulan}.pdf";

      return $pdf->download($filename);

    } catch (\Exception $e) {
      Log::error('Error generating PDF for all slip gaji: ' . $e->getMessage());
      abort(500, 'Terjadi kesalahan saat membuat PDF');
    }
  }

  /**
   * Cetak slip gaji individual
   */
  public function cetakSlipGajiIndividual(string $karyawan_id, int $tahun, int $bulan)
  {
    try {
      // Ambil data penggajian karyawan
      $penggajian = Penggajian::where('karyawan_id', $karyawan_id)
        ->where('periode_bulan', $bulan)
        ->where('periode_tahun', $tahun)
        ->where('status_penggajian', 'Disetujui')
        ->where('sudah_ditransfer', true)
        ->with(['karyawan.golonganPtkp.kategoriTer'])
        ->first();

      if (!$penggajian) {
        abort(404, 'Slip gaji tidak ditemukan untuk karyawan dan periode tersebut');
      }

      // Process data untuk karyawan individual
      $processedData = $this->processKaryawanDataForPdf(collect([$penggajian]), $bulan, $tahun);

      if (empty($processedData)) {
        abort(404, 'Data karyawan tidak valid');
      }

      $karyawanData = $processedData[0];

      // Data untuk PDF
      $pdfData = [
        'karyawan' => $karyawanData,
        'periode' => MonthHelper::formatPeriod($bulan, $tahun),
        'periodeBulan' => $bulan,
        'periodeTahun' => $tahun,
        'tanggalCetak' => Carbon::now()->format('d/m/Y H:i:s'),
        'judulDokumen' => 'Slip Gaji Karyawan'
      ];

      // Generate PDF
      $pdf = PDF::loadView('pdf.slip-gaji-individual', $pdfData);
      $pdf->setPaper('A4', 'portrait');
      $pdf->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);


      $namaFile = preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $karyawanData['nama_lengkap']));
      $filename = "Slip-Gaji-{$namaFile}-{$tahun}-{$bulan}.pdf";

      return $pdf->download($filename);

    } catch (\Exception $e) {
      Log::error('Error generating individual slip gaji PDF: ' . $e->getMessage());
      abort(500, 'Terjadi kesalahan saat membuat PDF');
    }
  }

  /**
   * API endpoint untuk cetak semua slip gaji (untuk implementasi future)
   */
  public function apiCetakSemuaSlipGaji(Request $request, int $tahun, int $bulan)
  {
    // TODO: Implementasi API untuk cetak semua slip gaji
    // Bisa return base64 encoded PDF atau URL download
    return response()->json([
      'message' => 'API endpoint untuk cetak semua slip gaji - belum diimplementasi',
      'tahun' => $tahun,
      'bulan' => $bulan
    ]);
  }

  /**
   * API endpoint untuk cetak slip gaji individual (untuk implementasi future)
   */
  public function apiCetakSlipGajiIndividual(Request $request, string $karyawan_id, int $tahun, int $bulan)
  {
    // TODO: Implementasi API untuk cetak slip gaji individual
    // Bisa return base64 encoded PDF atau URL download
    return response()->json([
      'message' => 'API endpoint untuk cetak slip gaji individual - belum diimplementasi',
      'karyawan_id' => $karyawan_id,
      'tahun' => $tahun,
      'bulan' => $bulan
    ]);
  }

  /**
   * Process data karyawan untuk PDF
   */
  private function processKaryawanDataForPdf($penggajianData, int $bulan, int $tahun): array
  {
    $processedData = [];

    $periodeStart = Carbon::create($tahun, $bulan, 1)->startOfMonth();
    $periodeEnd = Carbon::create($tahun, $bulan, 1)->endOfMonth();

    $karyawanIds = $penggajianData->pluck('karyawan_id');

    $attendanceService = new AbsensiService();
    $attendanceData = $attendanceService->getCombinedDataBatch($karyawanIds, $periodeStart, $periodeEnd);

    foreach ($penggajianData as $detail) {
      $karyawan = $detail->karyawan;

      if (!$karyawan) {
        Log::warning("Karyawan not found for penggajian {$detail->penggajian_id}");
        continue;
      }

      $karyawanAttendance = $attendanceData[$karyawan->karyawan_id] ?? [
        'total_hadir' => 0,
        'total_alfa' => 0,
        'total_tidak_tepat' => 0,
        'total_cuti' => 0,
        'total_izin' => 0,
        'total_lembur_hours' => 0,
        'total_lembur_sessions' => 0,
      ];

      $tunjanganData = $this->tunjanganService->getTunjanganBreakdown($karyawan);
      $bpjsData = $this->bpjsService->calculateBpjsDeductions($karyawan);
      $lemburData = $this->lemburService->calculateTotalLemburForPeriode($karyawan, $periodeStart, $periodeEnd);

      $pph21Data = $this->pph21Service->calculatePph21WithBreakdown(
        $karyawan,
        $detail->gaji_pokok,
        $detail->total_tunjangan,
        $detail->total_lembur
      );

      $potonganAlfaData = $this->potonganService->calculateAlfaDeduction($karyawan, $karyawanAttendance['total_alfa']);
      $potonganTerlambatData = $this->potonganService->calculateKeterlambatanDeduction($karyawan, $karyawanAttendance['total_tidak_tepat']);

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

      $processedData[] = [
        'detail_id' => $detail->penggajian_id,
        'karyawan_id' => $karyawan->karyawan_id,
        'nama_lengkap' => $karyawan->nama_lengkap,
        'jabatan' => $karyawan->jabatan,
        'departemen' => $karyawan->departemen ?? 'N/A',
        'email' => $karyawan->email ?? '',
        'no_telepon' => $karyawan->no_telepon ?? '',
        'alamat' => $karyawan->alamat ?? '',
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
      ];
    }

    return $processedData;
  }
}
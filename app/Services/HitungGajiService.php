<?php

namespace App\Services;

use App\Models\Karyawan;
use Illuminate\Support\Facades\Log;

class HitungGajiService
{
  protected TunjanganService $tunjanganService;
  protected PenaltyService $penaltyService;
  protected BpjsService $bpjsService;
  protected Pph21Service $pph21Service;

  public function __construct()
  {
    $this->tunjanganService = new TunjanganService();
    $this->penaltyService = new PenaltyService();
    $this->bpjsService = new BpjsService();
    $this->pph21Service = new Pph21Service();
  }

  /**
   * Calculate salary components - SAME LOGIC AS ViewPenggajian
   */
  public function calculateSalaryComponents(Karyawan $karyawan, array $attendanceData): array
  {
    $gajiPokok = (float) $karyawan->gaji_pokok;

    // Tunjangan
    $tunjanganData = $this->tunjanganService->calculateAllTunjangan($karyawan);
    $tunjanganTotal = $tunjanganData['total_tunjangan'];

    // Lembur
    $lemburPay = $attendanceData['total_lembur_insentif'] ?? 0;

    // Penghasilan bruto
    $penghasilanBruto = $gajiPokok + $tunjanganTotal + $lemburPay;

    // Potongan
    $alfaData = $this->penaltyService->calculateAlfaDeduction($karyawan, $attendanceData['total_alfa']);
    $keterlambatanData = $this->penaltyService->calculateKeterlambatanDeduction($karyawan, $attendanceData['total_tidak_tepat']);
    $bpjsData = $this->bpjsService->calculateBpjsDeductions($karyawan);
    $potonganPph21 = $this->pph21Service->calculateMonthlyPph21Deduction($karyawan, $penghasilanBruto);

    // Safety check PPh21
    if ($potonganPph21 > ($penghasilanBruto * 0.3)) {
      Log::warning("PPh21 too high for karyawan {$karyawan->karyawan_id}");
      $potonganPph21 = min($potonganPph21, $penghasilanBruto * 0.15);
    }

    $potonganTotal = $alfaData['total_potongan'] + $keterlambatanData['total_potongan'] + $bpjsData['total_bpjs'] + $potonganPph21;
    $totalGaji = $penghasilanBruto - $potonganTotal;

    return [
      'gaji_pokok' => $gajiPokok,
      'tunjangan_total' => $tunjanganTotal,
      'lembur_pay' => $lemburPay,
      'penghasilan_bruto' => $penghasilanBruto,
      'potongan_total' => $potonganTotal,
      'total_gaji' => max(0, $totalGaji),
      'potongan_detail' => [
        'alfa' => $alfaData,
        'keterlambatan' => $keterlambatanData,
        'bpjs' => $bpjsData['total_bpjs'],
        'pph21' => $potonganPph21,
      ]
    ];
  }
}
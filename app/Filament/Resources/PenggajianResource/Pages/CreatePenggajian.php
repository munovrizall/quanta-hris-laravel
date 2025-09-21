<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\Penggajian;
use App\Models\DetailPenggajian;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Lembur;
use App\Services\BpjsService;
use App\Services\LemburService;
use App\Services\PenaltyService;
use App\Services\Pph21Service;
use App\Services\TunjanganService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreatePenggajian extends CreateRecord
{
    protected static string $resource = PenggajianResource::class;

    protected static ?string $title = 'Tambah Penggajian';

    protected static ?string $breadcrumb = 'Tambah';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate ID otomatis
        $allIds = Penggajian::withTrashed()
            ->pluck('penggajian_id')
            ->map(function ($id) {
                return intval(substr($id, 2)); // Ambil angka dari PG0001 -> 1
            })
            ->max();

        $nextNumber = ($allIds ?? 0) + 1;
        $data['penggajian_id'] = 'PG' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Generate detail penggajian setelah record utama dibuat
        $this->generateDetailPenggajian($this->record);
    }

    /**
     * Generate detail penggajian untuk semua karyawan eligible
     */
    private function generateDetailPenggajian(Penggajian $penggajian): void
    {
        try {
            DB::beginTransaction();

            $periodeStart = Carbon::create($penggajian->periode_tahun, $penggajian->periode_bulan, 1)->startOfMonth();
            $periodeEnd = Carbon::create($penggajian->periode_tahun, $penggajian->periode_bulan, 1)->endOfMonth();

            // Get all eligible karyawan
            $karyawanList = Karyawan::with(['golonganPtkp.kategoriTer'])
                ->whereDate('tanggal_mulai_bekerja', '<=', $periodeEnd)
                ->get();

            $batchSize = 50;
            $detailPenggajianData = [];

            // Process karyawan in batches
            foreach ($karyawanList->chunk($batchSize) as $karyawanChunk) {
                $karyawanIds = $karyawanChunk->pluck('karyawan_id');

                // Get batch data for performance
                $absensiData = $this->getAbsensiDataBatch($karyawanIds, $periodeStart, $periodeEnd);
                $lemburData = $this->getLemburDataBatch($karyawanIds, $periodeStart, $periodeEnd);

                foreach ($karyawanChunk as $karyawan) {
                    try {
                        // Combine absensi and lembur data
                        $karyawanAbsensi = $absensiData[$karyawan->karyawan_id] ?? [
                            'total_hadir' => 0,
                            'total_alfa' => 0,
                            'total_tidak_tepat' => 0,
                            'total_absensi' => 0
                        ];

                        $karyawanLembur = $lemburData[$karyawan->karyawan_id] ?? [
                            'total_lembur_hours' => 0,
                            'total_lembur_sessions' => 0,
                            'total_lembur_insentif' => 0
                        ];

                        $combinedData = array_merge($karyawanAbsensi, $karyawanLembur);

                        // Calculate salary components (same logic as ViewPenggajian)
                        $gajiData = $this->calculateGajiFromDatabase($karyawan, $combinedData);

                        // Prepare data for batch insert
                        $detailPenggajianData[] = [
                            'penggajian_id' => $penggajian->penggajian_id,
                            'karyawan_id' => $karyawan->karyawan_id,
                            'gaji_pokok' => $gajiData['gaji_pokok'],
                            'total_tunjangan' => $gajiData['tunjangan_total'],
                            'total_lembur' => $gajiData['lembur_pay'],
                            'penghasilan_bruto' => $gajiData['gaji_pokok'] + $gajiData['tunjangan_total'] + $gajiData['lembur_pay'],
                            'potongan_alfa' => $gajiData['potongan_detail']['alfa']['total_potongan'],
                            'potongan_terlambat' => $gajiData['potongan_detail']['keterlambatan']['total_potongan'],
                            'potongan_bpjs' => $gajiData['potongan_detail']['bpjs'],
                            'potongan_pph21' => $gajiData['potongan_detail']['pph21'],
                            'penyesuaian' => 0, // Default 0, bisa diubah manual nanti
                            'catatan_penyesuaian' => null,
                            'total_potongan' => $gajiData['potongan_total'],
                            'gaji_bersih' => $gajiData['total_gaji'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        // Batch insert every 100 records for memory efficiency
                        if (count($detailPenggajianData) >= 100) {
                            DetailPenggajian::insert($detailPenggajianData);
                            $detailPenggajianData = [];
                        }

                    } catch (\Exception $e) {
                        Log::error("Error generating detail penggajian for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
                    }
                }
            }

            // Insert remaining data
            if (!empty($detailPenggajianData)) {
                DetailPenggajian::insert($detailPenggajianData);
            }

            DB::commit();

            Log::info("Detail penggajian generated successfully for {$penggajian->penggajian_id}", [
                'total_karyawan' => $karyawanList->count(),
                'periode' => "{$penggajian->periode_bulan}/{$penggajian->periode_tahun}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error generating detail penggajian for {$penggajian->penggajian_id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get absensi data for multiple karyawan - BATCH OPERATION
     */
    private function getAbsensiDataBatch($karyawanIds, $periodeStart, $periodeEnd): array
    {
        $absensiStats = Absensi::whereIn('karyawan_id', $karyawanIds)
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
                'total_absensi' => $stats->sum('count'),
            ];
        }

        return $result;
    }

    /**
     * Get lembur data for multiple karyawan - BATCH OPERATION
     */
    private function getLemburDataBatch($karyawanIds, $periodeStart, $periodeEnd): array
    {
        $lemburStats = Lembur::whereIn('karyawan_id', $karyawanIds)
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
     * Calculate salary components - SAME LOGIC AS ViewPenggajian
     */
    private function calculateGajiFromDatabase($karyawan, $combinedData): array
    {
        $gajiPokok = (float) $karyawan->gaji_pokok;

        // Services
        $tunjanganService = new TunjanganService();
        $potonganService = new PenaltyService();
        $bpjsService = new BpjsService();
        $pph21Service = new Pph21Service();

        // Tunjangan
        $tunjanganData = $tunjanganService->calculateAllTunjangan($karyawan);
        $tunjanganTotal = $tunjanganData['total_tunjangan'];

        // Lembur
        $lemburPay = $combinedData['total_lembur_insentif'] ?? 0;

        // Penghasilan bruto
        $penghasilanBruto = $gajiPokok + $tunjanganTotal + $lemburPay;

        // Potongan
        $alfaData = $potonganService->calculateAlfaDeduction($karyawan, $combinedData['total_alfa']);
        $keterlambatanData = $potonganService->calculateKeterlambatanDeduction($karyawan, $combinedData['total_tidak_tepat']);
        $bpjsData = $bpjsService->calculateBpjsDeductions($karyawan);
        $potonganPph21 = $pph21Service->calculateMonthlyPph21Deduction($karyawan, $penghasilanBruto);

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Simpan');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Simpan & Tambah Lagi');
    }

    protected function getCancelFormAction(): \Filament\Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }
}
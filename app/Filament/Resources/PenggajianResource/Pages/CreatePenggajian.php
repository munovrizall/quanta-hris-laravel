<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\Penggajian;
use App\Models\DetailPenggajian;
use App\Models\Karyawan;
use App\Services\AttendanceService;
use App\Services\HitungGajiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreatePenggajian extends CreateRecord
{
    protected static string $resource = PenggajianResource::class;

    protected static ?string $title = 'Tambah Penggajian';

    protected static ?string $breadcrumb = 'Tambah';

    protected AttendanceService $attendanceService;
    protected HitungGajiService $payrollService;

    public function __construct()
    {
        parent::__construct();
        $this->attendanceService = new AttendanceService();
        $this->payrollService = new HitungGajiService();
    }

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

                // Get combined attendance data using service
                $combinedData = $this->attendanceService->getCombinedDataBatch($karyawanIds, $periodeStart, $periodeEnd);

                foreach ($karyawanChunk as $karyawan) {
                    try {
                        $attendanceData = $combinedData[$karyawan->karyawan_id] ?? [
                            'total_hadir' => 0,
                            'total_alfa' => 0,
                            'total_tidak_tepat' => 0,
                            'total_absensi' => 0,
                            'total_lembur_hours' => 0.0,
                            'total_lembur_sessions' => 0,
                            'total_lembur_insentif' => 0,
                        ];

                        // Calculate salary components using service
                        $gajiData = $this->payrollService->calculateSalaryComponents($karyawan, $attendanceData);

                        // Prepare data for batch insert
                        $detailPenggajianData[] = [
                            'penggajian_id' => $penggajian->penggajian_id,
                            'karyawan_id' => $karyawan->karyawan_id,
                            'gaji_pokok' => $gajiData['gaji_pokok'],
                            'total_tunjangan' => $gajiData['tunjangan_total'],
                            'total_lembur' => $gajiData['lembur_pay'],
                            'penghasilan_bruto' => $gajiData['penghasilan_bruto'],
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
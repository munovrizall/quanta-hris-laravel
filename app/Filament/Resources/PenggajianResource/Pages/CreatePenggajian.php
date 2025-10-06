<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\Karyawan;
use App\Models\Penggajian;
use App\Services\AbsensiService;
use App\Services\HitungGajiService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreatePenggajian extends CreateRecord
{
    protected static string $resource = PenggajianResource::class;

    protected static ?string $title = 'Tambah Draf Penggajian';

    protected static ?string $breadcrumb = 'Tambah';

    protected function handleRecordCreation(array $data): Penggajian
    {
        return DB::transaction(function () use ($data) {
            $rows = $this->buildDetailPenggajianRows($data);

            if (empty($rows)) {
                throw ValidationException::withMessages([
                    'periode_bulan' => 'Tidak ada karyawan yang memenuhi kriteria periode ini.',
                ]);
            }

            $firstRow = array_shift($rows);
            $record = Penggajian::create($firstRow);

            if (!empty($rows)) {
                Penggajian::insert($rows);
            }

            Log::info('Detail penggajian generated successfully', [
                'periode' => sprintf('%02d/%d', $record->periode_bulan, $record->periode_tahun),
                'total_karyawan' => 1 + count($rows),
            ]);

            return $record;
        });
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->title('Berhasil')
            ->body('Data penggajian berhasil dibuat untuk periode yang dipilih.')
            ->success()
            ->send();
    }

    // Override the redirect after create
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    /**
     * Generate detail rows for each karyawan in the selected period.
     */
    private function buildDetailPenggajianRows(array $data): array
    {
        $periodeStart = Carbon::create($data['periode_tahun'], $data['periode_bulan'], 1)->startOfMonth();
        $periodeEnd = Carbon::create($data['periode_tahun'], $data['periode_bulan'], 1)->endOfMonth();

        $karyawanList = Karyawan::with(['golonganPtkp.kategoriTer'])
            ->whereDate('tanggal_mulai_bekerja', '<=', $periodeEnd)
            ->get();

        if ($karyawanList->isEmpty()) {
            return [];
        }

        $attendanceService = new AbsensiService();
        $payrollService = new HitungGajiService();

        $rows = [];
        $currentIdCounter = $this->getNextSequenceNumber();

        foreach ($karyawanList->chunk(50) as $chunk) {
            $combinedData = $attendanceService->getCombinedDataBatch(
                $chunk->pluck('karyawan_id'),
                $periodeStart,
                $periodeEnd
            );

            foreach ($chunk as $karyawan) {
                $attendance = $combinedData[$karyawan->karyawan_id] ?? [
                    'total_hadir' => 0,
                    'total_alfa' => 0,
                    'total_tidak_tepat' => 0,
                    'total_absensi' => 0,
                    'total_lembur_hours' => 0,
                    'total_lembur_sessions' => 0,
                    'total_lembur_insentif' => 0,
                ];

                try {
                    $gajiData = $payrollService->calculateSalaryComponents($karyawan, $attendance);
                } catch (\Throwable $exception) {
                    Log::error('Gagal menghitung gaji karyawan', [
                        'karyawan_id' => $karyawan->karyawan_id,
                        'periode' => sprintf('%02d/%d', $data['periode_bulan'], $data['periode_tahun']),
                        'message' => $exception->getMessage(),
                    ]);
                    continue;
                }

                $rows[] = [
                    'penggajian_id' => $this->formatSequenceNumber($currentIdCounter++),
                    'periode_bulan' => $data['periode_bulan'],
                    'periode_tahun' => $data['periode_tahun'],
                    'status_penggajian' => $data['status_penggajian'] ?? 'Draf',
                    'verified_by' => $data['verified_by'] ?? null,
                    'approved_by' => $data['approved_by'] ?? null,
                    'processed_by' => $data['processed_by'] ?? null,
                    'catatan_penolakan_draf' => $data['catatan_penolakan_draf'] ?? null,
                    'karyawan_id' => $karyawan->karyawan_id,
                    'sudah_diproses' => false,
                    'gaji_pokok' => $gajiData['gaji_pokok'],
                    'total_tunjangan' => $gajiData['tunjangan_total'],
                    'total_lembur' => $gajiData['lembur_pay'],
                    'penghasilan_bruto' => $gajiData['penghasilan_bruto'],
                    'potongan_alfa' => $gajiData['potongan_detail']['alfa']['total_potongan'],
                    'potongan_terlambat' => $gajiData['potongan_detail']['keterlambatan']['total_potongan'],
                    'potongan_bpjs' => $gajiData['potongan_detail']['bpjs'],
                    'potongan_pph21' => $gajiData['potongan_detail']['pph21'],
                    'penyesuaian' => 0,
                    'catatan_penyesuaian' => null,
                    'total_potongan' => $gajiData['potongan_total'],
                    'gaji_bersih' => $gajiData['total_gaji'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $rows;
    }

    private function getNextSequenceNumber(): int
    {
        $lastNumber = Penggajian::withTrashed()
            ->pluck('penggajian_id')
            ->map(function ($id) {
                return intval(substr($id, 2));
            })
            ->max();

        return ($lastNumber ?? 0) + 1;
    }

    private function formatSequenceNumber(int $value): string
    {
        return 'PG' . str_pad($value, 4, '0', STR_PAD_LEFT);
    }
}

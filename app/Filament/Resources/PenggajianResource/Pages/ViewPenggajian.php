<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\DetailPenggajian;
use App\Services\AbsensiService;
use App\Services\TunjanganService;
use App\Services\BpjsService;
use App\Services\AttendanceService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Forms;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ViewPenggajian extends ViewRecord
{
    protected static string $resource = PenggajianResource::class;

    protected static ?string $title = 'Detail Penggajian';

    protected static ?string $breadcrumb = 'Detail';

    // Add this property to enable JavaScript interaction
    protected $listeners = ['editKaryawan' => 'openEditModal'];

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    public function getColumnStart(): int|string|array
    {
        return '1';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Ubah'),
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->modalHeading('Hapus Penggajian')
                ->modalDescription('Apakah Anda yakin ingin menghapus penggajian ini?')
                ->modalSubmitActionLabel('Ya, hapus'),
        ];
    }

    protected function getActions(): array
    {
        return [
            $this->editKaryawanGaji(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        // Get paginated data from database
        $paginatedDetailPenggajian = $this->getPaginatedDetailPenggajianFromDatabase($this->record);
        $karyawanData = $this->processKaryawanDataFromDatabase($paginatedDetailPenggajian);

        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Penggajian')
                    ->schema([
                        Infolists\Components\TextEntry::make('penggajian_id')
                            ->label('ID Penggajian')
                            ->badge()
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('periode')
                            ->label('Periode')
                            ->getStateUsing(function ($record): string {
                                $namaBulan = [
                                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                ];
                                return $namaBulan[$record->periode_bulan] . ' ' . $record->periode_tahun;
                            }),

                        Infolists\Components\TextEntry::make('status_penggajian')
                            ->label('Status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'Draf' => 'gray',
                                'Diverifikasi' => 'warning',
                                'Disetujui' => 'success',
                                'Ditolak' => 'danger',
                                default => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('total_karyawan_from_db')
                            ->label('Total Karyawan')
                            ->getStateUsing(function ($record): int {
                                return $this->getTotalKaryawanCountFromDatabase($record);
                            })
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('total_gaji_from_db')
                            ->label('Total Gaji')
                            ->getStateUsing(function ($record): string {
                                $totalGaji = $this->calculateTotalGajiFromDatabase($record);
                                return 'Rp ' . number_format($totalGaji, 0, ',', '.');
                            })
                            ->weight('bold')
                            ->color('success'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d F Y H:i'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Alur Persetujuan')
                    ->schema([
                        Infolists\Components\TextEntry::make('verifier.nama_lengkap')
                            ->label('Diverifikasi Oleh (Staff HRD)')
                            ->placeholder('Belum diverifikasi')
                            ->icon('heroicon-m-clipboard-document-check'),

                        Infolists\Components\TextEntry::make('approver.nama_lengkap')
                            ->label('Disetujui Oleh (Manager Finance)')
                            ->placeholder('Belum disetujui')
                            ->icon('heroicon-m-check-badge'),

                        Infolists\Components\TextEntry::make('processor.nama_lengkap')
                            ->label('Diproses Oleh (Account Payment)')
                            ->placeholder('Belum diproses')
                            ->icon('heroicon-m-cog-6-tooth'),
                    ])
                    ->columns(1),

                Infolists\Components\Section::make('Statistik Penggajian')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('total_gaji_pokok_from_db')
                                    ->label('Total Gaji Pokok')
                                    ->getStateUsing(function ($record): string {
                                        $total = $this->calculateTotalGajiPokokFromDatabase($record);
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    })
                                    ->color('primary'),

                                Infolists\Components\TextEntry::make('total_tunjangan_from_db')
                                    ->label('Total Tunjangan')
                                    ->getStateUsing(function ($record): string {
                                        $total = $this->calculateTotalTunjanganFromDatabase($record);
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    })
                                    ->color('info'),

                                Infolists\Components\TextEntry::make('total_lembur_from_db')
                                    ->label('Total Upah Lembur')
                                    ->getStateUsing(function ($record): string {
                                        $total = $this->calculateTotalLemburFromDatabase($record);
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    })
                                    ->color('warning'),

                                Infolists\Components\TextEntry::make('total_potongan_from_db')
                                    ->label('Total Potongan')
                                    ->getStateUsing(function ($record): string {
                                        $total = $this->calculateTotalPotonganFromDatabase($record);
                                        return 'Rp ' . number_format($total, 0, ',', '.');
                                    })
                                    ->color('danger'),
                            ]),

                        Infolists\Components\TextEntry::make('grand_total_from_db')
                            ->label('GRAND TOTAL PENGGAJIAN')
                            ->getStateUsing(function ($record): string {
                                $total = $this->calculateTotalGajiFromDatabase($record);
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            })
                            ->size('xl')
                            ->weight('bold')
                            ->color('success')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Catatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('catatan_penolakan_draf')
                            ->label('Catatan Penolakan')
                            ->placeholder('Tidak ada catatan penolakan')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn($record) => $record->status_penggajian === 'Ditolak'),

                Infolists\Components\Section::make('Detail Gaji Karyawan')
                    ->schema([
                        Infolists\Components\ViewEntry::make('karyawan_list')
                            ->label('')
                            ->view('filament.infolists.karyawan-gaji-detail')
                            ->viewData([
                                'karyawanData' => $karyawanData,
                                'pagination' => $paginatedDetailPenggajian,
                                'canEdit' => $this->record->status_penggajian === 'Draf',
                                'penggajianId' => $this->record->penggajian_id,
                                'livewireId' => $this->getId(), // Add this
                            ])
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }

    // Method to handle the modal opening from JavaScript
    public function openEditModal($detailId)
    {
        $this->mountAction('editKaryawanGaji', ['detailId' => $detailId]);
    }

    /**
     * Action untuk mengedit detail gaji karyawan
     */
    public function editKaryawanGaji(): Actions\Action
    {
        return Actions\Action::make('editKaryawanGaji')
            ->label('Edit Gaji')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->visible(fn() => $this->record->status_penggajian === 'Draf')
            ->form([
                Forms\Components\Hidden::make('detail_penggajian_id'),

                Forms\Components\Section::make('Informasi Karyawan')
                    ->schema([
                        Forms\Components\TextInput::make('karyawan_id')
                            ->label('ID Karyawan')
                            ->disabled(),
                        Forms\Components\TextInput::make('nama_karyawan')
                            ->label('Nama Karyawan')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Penghasilan')
                    ->schema([
                        Forms\Components\TextInput::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)))
                            ->disabled(),

                        Forms\Components\TextInput::make('total_tunjangan')
                            ->label('Total Tunjangan')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)))
                            ->disabled(),

                        Forms\Components\TextInput::make('total_lembur')
                            ->label('Upah Lembur')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)))
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $this->recalculateGajiBersih($set, $get);
                            }),

                        Forms\Components\TextInput::make('penghasilan_bruto')
                            ->label('Penghasilan Bruto')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->disabled()
                            ->reactive(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Potongan')
                    ->schema([
                        Forms\Components\TextInput::make('potongan_alfa')
                            ->label('Potongan Alfa')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)))
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $this->recalculateGajiBersih($set, $get);
                            }),

                        Forms\Components\TextInput::make('potongan_terlambat')
                            ->label('Potongan Terlambat')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)))
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $this->recalculateGajiBersih($set, $get);
                            }),

                        Forms\Components\TextInput::make('potongan_bpjs')
                            ->label('Potongan BPJS')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)))
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $this->recalculateGajiBersih($set, $get);
                            }),

                        Forms\Components\TextInput::make('potongan_pph21')
                            ->label('PPh21')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)))
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $this->recalculateGajiBersih($set, $get);
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Penyesuaian')
                    ->schema([
                        Forms\Components\TextInput::make('penyesuaian')
                            ->label('Penyesuaian')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->dehydrateStateUsing(fn($state) => (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)))
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                $this->recalculateGajiBersih($set, $get);
                            }),

                        Forms\Components\Textarea::make('catatan_penyesuaian')
                            ->label('Catatan Penyesuaian')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Total')
                    ->schema([
                        Forms\Components\TextInput::make('total_potongan')
                            ->label('Total Potongan')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->disabled(),

                        Forms\Components\TextInput::make('gaji_bersih')
                            ->label('Gaji Bersih')
                            ->numeric()
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->disabled()
                            ->extraAttributes(['class' => 'font-bold text-green-600']),
                    ])
                    ->columns(2),
            ])
            ->fillForm(function (array $arguments): array {
                $detailId = $arguments['detailId'];
                $detail = DetailPenggajian::with('karyawan')->find($detailId);

                if (!$detail) {
                    return [];
                }

                return [
                    'detail_penggajian_id' => $detail->id,
                    'karyawan_id' => $detail->karyawan_id,
                    'nama_karyawan' => $detail->karyawan->nama_lengkap,
                    'gaji_pokok' => $detail->gaji_pokok,
                    'total_tunjangan' => $detail->total_tunjangan,
                    'total_lembur' => $detail->total_lembur,
                    'penghasilan_bruto' => $detail->penghasilan_bruto,
                    'potongan_alfa' => $detail->potongan_alfa,
                    'potongan_terlambat' => $detail->potongan_terlambat,
                    'potongan_bpjs' => $detail->potongan_bpjs,
                    'potongan_pph21' => $detail->potongan_pph21,
                    'penyesuaian' => $detail->penyesuaian,
                    'catatan_penyesuaian' => $detail->catatan_penyesuaian,
                    'total_potongan' => $detail->total_potongan,
                    'gaji_bersih' => $detail->gaji_bersih,
                ];
            })
            ->action(function (array $data): void {
                try {
                    $detail = DetailPenggajian::find($data['detail_penggajian_id']);

                    if (!$detail) {
                        Notification::make()
                            ->title('Error')
                            ->body('Data detail penggajian tidak ditemukan.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Recalculate penghasilan bruto
                    $penghasilanBruto = $detail->gaji_pokok + $detail->total_tunjangan + $data['total_lembur'];

                    // Recalculate total potongan
                    $totalPotongan = $data['potongan_alfa'] + $data['potongan_terlambat'] +
                        $data['potongan_bpjs'] + $data['potongan_pph21'];

                    // Calculate gaji bersih
                    $gajiBersih = $penghasilanBruto - $totalPotongan + $data['penyesuaian'];

                    $detail->update([
                        'total_lembur' => $data['total_lembur'],
                        'penghasilan_bruto' => $penghasilanBruto,
                        'potongan_alfa' => $data['potongan_alfa'],
                        'potongan_terlambat' => $data['potongan_terlambat'],
                        'potongan_bpjs' => $data['potongan_bpjs'],
                        'potongan_pph21' => $data['potongan_pph21'],
                        'penyesuaian' => $data['penyesuaian'],
                        'catatan_penyesuaian' => $data['catatan_penyesuaian'],
                        'total_potongan' => $totalPotongan,
                        'gaji_bersih' => max(0, $gajiBersih), // Prevent negative salary
                    ]);

                    Notification::make()
                        ->title('Berhasil!')
                        ->body('Data gaji karyawan berhasil diperbarui.')
                        ->success()
                        ->send();

                    // Refresh the page
                    $this->dispatch('refresh');

                } catch (\Exception $e) {
                    Log::error('Error updating detail penggajian: ' . $e->getMessage());

                    Notification::make()
                        ->title('Error')
                        ->body('Terjadi kesalahan saat memperbarui data.')
                        ->danger()
                        ->send();
                }
            })
            ->modalHeading('Edit Gaji Karyawan')
            ->modalSubmitActionLabel('Simpan')
            ->modalCancelActionLabel('Batal')
            ->modalWidth('6xl');
    }

    /**
     * Helper function to recalculate gaji bersih
     */
    private function recalculateGajiBersih($set, $get)
    {
        $gajiPokok = (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $get('gaji_pokok') ?? 0));
        $totalTunjangan = (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $get('total_tunjangan') ?? 0));
        $totalLembur = (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $get('total_lembur') ?? 0));

        $potonganAlfa = (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $get('potongan_alfa') ?? 0));
        $potonganTerlambat = (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $get('potongan_terlambat') ?? 0));
        $potonganBpjs = (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $get('potongan_bpjs') ?? 0));
        $potonganPph21 = (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $get('potongan_pph21') ?? 0));
        $penyesuaian = (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $get('penyesuaian') ?? 0));

        $penghasilanBruto = $gajiPokok + $totalTunjangan + $totalLembur;
        $totalPotongan = $potonganAlfa + $potonganTerlambat + $potonganBpjs + $potonganPph21;
        $gajiBersih = $penghasilanBruto - $totalPotongan + $penyesuaian;

        $set('penghasilan_bruto', number_format($penghasilanBruto, 0, ',', '.'));
        $set('total_potongan', number_format($totalPotongan, 0, ',', '.'));
        $set('gaji_bersih', number_format(max(0, $gajiBersih), 0, ',', '.'));
    }

    // ...existing code...

    /**
     * Get paginated detail penggajian from database
     */
    private function getPaginatedDetailPenggajianFromDatabase($record): LengthAwarePaginator
    {
        return DetailPenggajian::with(['karyawan.golonganPtkp.kategoriTer'])
            ->where('penggajian_id', $record->penggajian_id)
            ->paginate(10, ['*'], 'page')
            ->withQueryString();
    }

    /**
     * Process karyawan data from detail_penggajian table
     */
    private function processKaryawanDataFromDatabase(LengthAwarePaginator $paginatedDetailPenggajian): array
    {
        $processedData = [];

        // Get periode for attendance data
        $periodeStart = Carbon::create($this->record->periode_tahun, $this->record->periode_bulan, 1)->startOfMonth();
        $periodeEnd = Carbon::create($this->record->periode_tahun, $this->record->periode_bulan, 1)->endOfMonth();

        // Get karyawan IDs for batch operation
        $karyawanIds = collect($paginatedDetailPenggajian->items())->pluck('karyawan_id');

        // Initialize attendance service
        $attendanceService = new AbsensiService();

        // Get attendance data in batch
        $attendanceData = $attendanceService->getCombinedDataBatch($karyawanIds, $periodeStart, $periodeEnd);

        foreach ($paginatedDetailPenggajian->items() as $detail) {
            $karyawan = $detail->karyawan;

            if (!$karyawan) {
                Log::warning("Karyawan not found for detail penggajian {$detail->id}");
                continue;
            }

            // Get attendance data for this karyawan
            $karyawanAttendance = $attendanceData[$karyawan->karyawan_id] ?? [
                'total_hadir' => 0,
                'total_alfa' => 0,
                'total_tidak_tepat' => 0,
                'total_cuti' => 0,
                'total_izin' => 0,
                'total_lembur_hours' => 0,
                'total_lembur_sessions' => 0,
            ];

            // Generate additional info for display
            $tunjanganBreakdown = $this->getTunjanganBreakdownForDisplay($karyawan);
            $bpjsBreakdown = $this->getBpjsBreakdownForDisplay($detail);
            $pph21Detail = $this->getPph21DetailForDisplay($karyawan, $detail);

            $processedData[] = [
                'detail_id' => $detail->id, // Add detail ID for editing
                'karyawan_id' => $karyawan->karyawan_id,
                'nama_lengkap' => $karyawan->nama_lengkap,
                'jabatan' => $karyawan->jabatan,
                'departemen' => $karyawan->departemen ?? 'N/A',
                'total_hadir' => $karyawanAttendance['total_hadir'],
                'total_alfa' => $karyawanAttendance['total_alfa'],
                'total_tidak_tepat' => $karyawanAttendance['total_tidak_tepat'],
                'total_cuti' => $karyawanAttendance['total_cuti'],
                'total_izin' => $karyawanAttendance['total_izin'],
                'total_lembur' => $karyawanAttendance['total_lembur_hours'],
                'total_lembur_sessions' => $karyawanAttendance['total_lembur_sessions'],
                'gaji_pokok' => $detail->gaji_pokok,
                'tunjangan_total' => $detail->total_tunjangan,
                'tunjangan_breakdown' => $tunjanganBreakdown,
                'bpjs_breakdown' => $bpjsBreakdown,
                'lembur_pay' => $detail->total_lembur,
                'potongan_total' => $detail->total_potongan,
                'total_gaji' => $detail->gaji_bersih,
                'penyesuaian' => $detail->penyesuaian,
                'catatan_penyesuaian' => $detail->catatan_penyesuaian,
                'pph21_detail' => $pph21Detail,
                'potongan_detail' => [
                    'alfa' => [
                        'total_potongan' => $detail->potongan_alfa,
                        'potongan_per_hari' => $karyawanAttendance['total_alfa'] > 0 ? $detail->potongan_alfa / $karyawanAttendance['total_alfa'] : 0
                    ],
                    'keterlambatan' => [
                        'total_potongan' => $detail->potongan_terlambat,
                        'potongan_per_hari' => $karyawanAttendance['total_tidak_tepat'] > 0 ? $detail->potongan_terlambat / $karyawanAttendance['total_tidak_tepat'] : 0
                    ],
                    'bpjs' => $detail->potongan_bpjs,
                    'pph21' => $detail->potongan_pph21,
                ],
            ];
        }

        return $processedData;
    }

    // Database calculation methods
    private function getTotalKaryawanCountFromDatabase($record): int
    {
        return DetailPenggajian::where('penggajian_id', $record->penggajian_id)->count();
    }

    private function calculateTotalGajiFromDatabase($record): float
    {
        return DetailPenggajian::where('penggajian_id', $record->penggajian_id)
            ->sum('gaji_bersih');
    }

    private function calculateTotalGajiPokokFromDatabase($record): float
    {
        return DetailPenggajian::where('penggajian_id', $record->penggajian_id)
            ->sum('gaji_pokok');
    }

    private function calculateTotalTunjanganFromDatabase($record): float
    {
        return DetailPenggajian::where('penggajian_id', $record->penggajian_id)
            ->sum('total_tunjangan');
    }

    private function calculateTotalLemburFromDatabase($record): float
    {
        return DetailPenggajian::where('penggajian_id', $record->penggajian_id)
            ->sum('total_lembur');
    }

    private function calculateTotalPotonganFromDatabase($record): float
    {
        return DetailPenggajian::where('penggajian_id', $record->penggajian_id)
            ->sum('total_potongan');
    }

    /**
     * Generate display breakdowns
     */
    private function getTunjanganBreakdownForDisplay($karyawan): array
    {
        $tunjanganService = new TunjanganService();
        return $tunjanganService->getTunjanganBreakdown($karyawan);
    }

    private function getBpjsBreakdownForDisplay($detail): array
    {
        // Generate BPJS breakdown for display
        $breakdown = [];

        if ($detail->potongan_bpjs > 0) {
            $breakdown[] = [
                'label' => 'Total BPJS',
                'amount' => $detail->potongan_bpjs,
                'description' => 'BPJS Kesehatan + JHT + JP'
            ];
        }

        return [
            'breakdown' => $breakdown,
            'total_amount' => $detail->potongan_bpjs,
            'info' => [
                'gaji_pokok' => $detail->gaji_pokok,
                'total_percentage' => round(($detail->potongan_bpjs / ($detail->gaji_pokok ?: 1)) * 100, 2),
            ]
        ];
    }

    private function getPph21DetailForDisplay($karyawan, $detail): array
    {
        $golonganPtkp = $karyawan->golonganPtkp;

        if (!$golonganPtkp) {
            return [
                'jumlah' => $detail->potongan_pph21,
                'tarif_persen' => 0,
                'golongan_ptkp' => 'N/A',
                'kategori_ter' => 'N/A',
                'penghasilan_bruto' => $detail->penghasilan_bruto,
            ];
        }

        // Find appropriate TER tariff
        $tarifTer = \App\Models\TarifTer::where('kategori_ter_id', $golonganPtkp->kategori_ter_id)
            ->where('batas_bawah', '<=', $detail->penghasilan_bruto)
            ->where('batas_atas', '>=', $detail->penghasilan_bruto)
            ->first();

        $tarifPersen = 0;
        $kategoriTer = 'N/A';

        if ($tarifTer) {
            $tarifPersen = $tarifTer->tarif * 100;
            $kategoriTer = $golonganPtkp->kategoriTer?->nama_kategori ?? 'TER-' . $golonganPtkp->kategori_ter_id;
        }

        return [
            'jumlah' => $detail->potongan_pph21,
            'tarif_persen' => $tarifPersen,
            'golongan_ptkp' => $golonganPtkp->nama_golongan_ptkp,
            'kategori_ter' => $kategoriTer,
            'penghasilan_bruto' => $detail->penghasilan_bruto,
        ];
    }
}
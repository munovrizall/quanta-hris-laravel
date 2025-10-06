<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Filament\Resources\PenggajianResource\Actions\EditGajiKaryawanAction;
use App\Models\Penggajian;
use App\Services\AbsensiService;
use App\Services\TunjanganService;
use App\Services\BpjsService;
use App\Services\LemburService;
use App\Services\Pph21Service;
use App\Services\PotonganService;
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

  protected $listeners = ['editKaryawan' => 'openEditModal'];

  public int $currentPage = 1;

  public string $paginationPath = '';

  // Remove constructor and use lazy loading instead
  private ?TunjanganService $tunjanganService = null;
  private ?BpjsService $bpjsService = null;
  private ?LemburService $lemburService = null;
  private ?Pph21Service $pph21Service = null;
  private ?PotonganService $potonganService = null;

  // Lazy loading methods for services
  private function getTunjanganService(): TunjanganService
  {
    if ($this->tunjanganService === null) {
      $this->tunjanganService = new TunjanganService();
    }
    return $this->tunjanganService;
  }

  private function getBpjsService(): BpjsService
  {
    if ($this->bpjsService === null) {
      $this->bpjsService = new BpjsService();
    }
    return $this->bpjsService;
  }

  private function getLemburService(): LemburService
  {
    if ($this->lemburService === null) {
      $this->lemburService = new LemburService();
    }
    return $this->lemburService;
  }

  private function getPph21Service(): Pph21Service
  {
    if ($this->pph21Service === null) {
      $this->pph21Service = new Pph21Service();
    }
    return $this->pph21Service;
  }

  private function getPotonganService(): PotonganService
  {
    if ($this->potonganService === null) {
      $this->potonganService = new PotonganService();
    }
    return $this->potonganService;
  }

  public function getColumnSpan(): int|string|array
  {
    return 'full';
  }

  public function getColumnStart(): int|string|array
  {
    return '1';
  }

  public function mount(int|string $record = null): void
  {
    $tahun = request()->route('tahun');
    $bulan = request()->route('bulan');

    if ($tahun && $bulan) {
      $penggajian = Penggajian::where('periode_bulan', $bulan)
        ->where('periode_tahun', $tahun)
        ->first();

      if (!$penggajian) {
        abort(404, 'Penggajian tidak ditemukan untuk periode tersebut');
      }

      $this->record = $penggajian;
    } else {
      parent::mount($record);
    }

    $this->currentPage = max(1, request()->integer('page', $this->currentPage));
    $this->paginationPath = request()->fullUrlWithoutQuery('page');
  }

  public function resolveRecord(int|string $key): \Illuminate\Database\Eloquent\Model
  {
    if (isset($this->record)) {
      return $this->record;
    }

    return parent::resolveRecord($key);
  }

  protected function getHeaderActions(): array
  {
    return [
      Actions\DeleteAction::make()
        ->label('Hapus')
        ->requiresConfirmation()
        ->modalHeading('Hapus Penggajian')
        ->modalDescription('Apakah Anda yakin ingin menghapus penggajian ini?')
        ->modalSubmitActionLabel('Ya, hapus')
        ->action(function () {
          Penggajian::where('periode_bulan', $this->record->periode_bulan)
            ->where('periode_tahun', $this->record->periode_tahun)
            ->delete();
        })
        ->successRedirectUrl(static::getResource()::getUrl('index')),
    ];
  }

  public function getBreadcrumbs(): array
  {
    $namaBulan = [
      1 => 'Januari',
      2 => 'Februari',
      3 => 'Maret',
      4 => 'April',
      5 => 'Mei',
      6 => 'Juni',
      7 => 'Juli',
      8 => 'Agustus',
      9 => 'September',
      10 => 'Oktober',
      11 => 'November',
      12 => 'Desember'
    ];

    $breadcrumbs = parent::getBreadcrumbs();

    if (isset($this->record)) {
      $periodeName = $namaBulan[$this->record->periode_bulan] . ' ' . $this->record->periode_tahun;
      $breadcrumbs[array_key_last($breadcrumbs)] = $periodeName;
    }

    return $breadcrumbs;
  }

  protected function getActions(): array
  {
    return [
      $this->editKaryawanGajiAction(),
    ];
  }

  public function editKaryawanGajiAction()
  {
    return EditGajiKaryawanAction::make()
      ->visible(fn() => $this->record->status_penggajian === 'Draf');
  }

  public function openEditModal($detailId)
  {
    $this->mountAction('editKaryawanGaji', ['detailId' => $detailId]);
  }

  public function infolist(Infolist $infolist): Infolist
  {
    $paginatedDetailPenggajian = $this->getPaginatedDetailPenggajianFromDatabase($this->record);
    $karyawanData = $this->processKaryawanDataFromDatabase($paginatedDetailPenggajian);

    return $infolist
      ->schema([
        Infolists\Components\Section::make('Informasi Penggajian')
          ->schema([
            Infolists\Components\TextEntry::make('periode')
              ->label('Periode')
              ->getStateUsing(function ($record): string {
                $namaBulan = [
                  1 => 'Januari',
                  2 => 'Februari',
                  3 => 'Maret',
                  4 => 'April',
                  5 => 'Mei',
                  6 => 'Juni',
                  7 => 'Juli',
                  8 => 'Agustus',
                  9 => 'September',
                  10 => 'Oktober',
                  11 => 'November',
                  12 => 'Desember'
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
                'periodeBulan' => $this->record->periode_bulan,
                'periodeTahun' => $this->record->periode_tahun,
                'livewireId' => $this->getId(),
              ])
          ])
          ->collapsible()
          ->collapsed(false),
      ]);
  }

  /**
   * Get paginated detail penggajian from database
   */
  private function getPaginatedDetailPenggajianFromDatabase($record): LengthAwarePaginator
  {
    if (request()->has('page')) {
      $this->currentPage = max(1, request()->integer('page', $this->currentPage));
    }

    if ($this->paginationPath === '') {
      $this->paginationPath = request()->fullUrlWithoutQuery('page');
    }

    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->with(['karyawan.golonganPtkp.kategoriTer'])
      ->paginate(10, ['*'], 'page', $this->currentPage)
      ->withPath($this->paginationPath)
      ->withQueryString();
  }

  /**
   * Process karyawan data using services - FIXED VERSION WITH LAZY LOADING
   */
  private function processKaryawanDataFromDatabase(LengthAwarePaginator $paginatedDetailPenggajian): array
  {
    $processedData = [];

    $periodeStart = Carbon::create($this->record->periode_tahun, $this->record->periode_bulan, 1)->startOfMonth();
    $periodeEnd = Carbon::create($this->record->periode_tahun, $this->record->periode_bulan, 1)->endOfMonth();

    $karyawanIds = collect($paginatedDetailPenggajian->items())->pluck('karyawan_id');

    $attendanceService = new AbsensiService();
    $attendanceData = $attendanceService->getCombinedDataBatch($karyawanIds, $periodeStart, $periodeEnd);

    foreach ($paginatedDetailPenggajian->items() as $detail) {
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

      // USE SERVICES FOR ALL CALCULATIONS - WITH LAZY LOADING
      $tunjanganData = $this->getTunjanganService()->getTunjanganBreakdown($karyawan);
      $bpjsData = $this->getBpjsService()->calculateBpjsDeductions($karyawan);

      // USE LEMBUR SERVICE TO CALCULATE LEMBUR DATA
      $lemburData = $this->getLemburService()->calculateTotalLemburForPeriode($karyawan, $periodeStart, $periodeEnd);

      // Calculate Pph21 using actual values from database
      $pph21Data = $this->getPph21Service()->calculatePph21WithBreakdown(
        $karyawan,
        $detail->gaji_pokok,
        $detail->total_tunjangan,
        $detail->total_lembur
      );

      // Calculate potongan using services
      $potonganAlfaData = $this->getPotonganService()->calculateAlfaDeduction($karyawan, $karyawanAttendance['total_alfa']);
      $potonganTerlambatData = $this->getPotonganService()->calculateKeterlambatanDeduction($karyawan, $karyawanAttendance['total_tidak_tepat']);

      // BUILD BPJS BREAKDOWN WITH DESCRIPTIONS 
      $bpjsBreakdownWithDescriptions = [
        [
          'label' => 'BPJS Kesehatan',
          'amount' => $bpjsData['bpjs_kesehatan'],
          'description' => ((float) $bpjsData['breakdown']['persen_kesehatan'] * 100) . '% dari gaji pokok + tunjangan tetap (Rp ' . number_format($bpjsData['breakdown']['dasar_bpjs'], 0, ',', '.') . ')'
        ],
        [
          'label' => 'BPJS JHT',
          'amount' => $bpjsData['bpjs_jht'],
          'description' => ((float) $bpjsData['breakdown']['persen_jht'] * 100) . '% dari gaji pokok (Rp ' . number_format($detail->gaji_pokok, 0, ',', '.') . ')'
        ],
        [
          'label' => 'BPJS JP',
          'amount' => $bpjsData['bpjs_jp'],
          'description' => ((float) $bpjsData['breakdown']['persen_jp'] * 100) . '% dari gaji pokok + tunjangan tetap (Rp ' . number_format($bpjsData['breakdown']['dasar_bpjs'], 0, ',', '.') . ')'
        ],
      ];

      $processedData[] = [
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
        'total_lembur' => $lemburData['total_jam'], // USE LEMBUR SERVICE DATA
        'total_lembur_sessions' => $lemburData['total_sesi'], // USE LEMBUR SERVICE DATA
        'gaji_pokok' => $detail->gaji_pokok,
        'tunjangan_total' => $detail->total_tunjangan,
        'tunjangan_breakdown' => $tunjanganData,
        'bpjs_breakdown' => [
          'breakdown' => $bpjsBreakdownWithDescriptions,
          'total_amount' => $bpjsData['total_bpjs'],
          'info' => $bpjsData['breakdown']
        ],
        'lembur_pay' => $detail->total_lembur,
        'lembur_detail' => [ // ADD LEMBUR BREAKDOWN
          'total_insentif' => $lemburData['total_insentif'],
          'total_jam' => $lemburData['total_jam'],
          'total_sesi' => $lemburData['total_sesi'],
          'formatted_amount' => $this->getLemburService()->formatRupiah($lemburData['total_insentif'])
        ],
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

  // Database calculation methods - USE DIRECT QUERIES
  private function getTotalKaryawanCountFromDatabase($record): int
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->count();
  }

  private function calculateTotalGajiFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('gaji_bersih');
  }

  private function calculateTotalGajiPokokFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('gaji_pokok');
  }

  private function calculateTotalTunjanganFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('total_tunjangan');
  }

  private function calculateTotalLemburFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('total_lembur');
  }

  private function calculateTotalPotonganFromDatabase($record): float
  {
    return Penggajian::where('periode_bulan', $record->periode_bulan)
      ->where('periode_tahun', $record->periode_tahun)
      ->sum('total_potongan');
  }
}
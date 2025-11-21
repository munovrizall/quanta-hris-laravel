<?php

namespace App\Filament\Resources\SlipGajiResource\Pages;

use App\Filament\Resources\SlipGajiResource;
use App\Models\Penggajian;
use App\Services\AbsensiService;
use App\Services\TunjanganService;
use App\Services\BpjsService;
use App\Services\LemburService;
use App\Services\Pph21Service;
use App\Services\PotonganService;
use App\Utils\MonthHelper;
use Filament\Actions;
use App\Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ViewSlipGaji extends ViewRecord
{
  protected static string $resource = SlipGajiResource::class;

  protected static ?string $title = 'Slip Gaji';

  protected static ?string $breadcrumb = 'Detail Slip Gaji';

  public int $currentPage = 1;
  public string $paginationPath = '';

  public function getColumnSpan(): int|string|array
  {
    return 'full';
  }

  public function getColumnStart(): int|string|array
  {
    return '1';
  }

  // Services dengan lazy loading
  private ?TunjanganService $tunjanganService = null;
  private ?BpjsService $bpjsService = null;
  private ?LemburService $lemburService = null;
  private ?Pph21Service $pph21Service = null;
  private ?PotonganService $potonganService = null;

  // Lazy loading methods untuk services
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

  public function mount(int|string $record = null): void
  {
    $tahun = request()->route('tahun');
    $bulan = request()->route('bulan');

    if ($tahun && $bulan) {
      $penggajian = Penggajian::where('periode_bulan', $bulan)
        ->where('periode_tahun', $tahun)
        ->where('status_penggajian', 'Disetujui') // Hanya yang sudah disetujui
        ->first();

      if (!$penggajian) {
        abort(404, 'Slip gaji tidak ditemukan untuk periode tersebut atau belum disetujui');
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
      Actions\Action::make('cetak_slip_gaji')
        ->label('Cetak Semua Slip Gaji')
        ->icon('heroicon-o-printer')
        ->color('primary')
        ->url(fn() => route('slip-gaji.cetak', [
          'tahun' => $this->record->periode_tahun,
          'bulan' => $this->record->periode_bulan
        ]))
        ->openUrlInNewTab(),
    ];
  }

  public function getBreadcrumbs(): array
  {
    $breadcrumbs = parent::getBreadcrumbs();

    if (isset($this->record)) {
      $periodeName = MonthHelper::getMonthName($this->record->periode_bulan) . ' ' . $this->record->periode_tahun;
      $breadcrumbs[array_key_last($breadcrumbs)] = $periodeName;
    }

    return $breadcrumbs;
  }

  public function infolist(Infolist $infolist): Infolist
  {
    $paginatedDetailPenggajian = $this->getPaginatedDetailPenggajianFromDatabase($this->record);
    $karyawanData = $this->processKaryawanDataFromDatabase($paginatedDetailPenggajian);

    return $infolist
      ->schema([
        Infolists\Components\Section::make('Detail Slip Gaji Karyawan')
          ->schema([
            Infolists\Components\ViewEntry::make('karyawan_list')
              ->label('')
              ->view('filament.infolists.slip-gaji-detail')
              ->viewData([
                'karyawanData' => $karyawanData,
                'pagination' => $paginatedDetailPenggajian,
                'periodeBulan' => $this->record->periode_bulan,
                'periodeTahun' => $this->record->periode_tahun,
                'livewireId' => $this->getId(),
              ])
          ])
          ->collapsible(false),
      ]);
  }

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
      ->where('status_penggajian', 'Disetujui')
      ->where('sudah_ditransfer', true)
      ->with(['karyawan.golonganPtkp.kategoriTer'])
      ->paginate(10, ['*'], 'page', $this->currentPage)
      ->withPath($this->paginationPath)
      ->withQueryString();
  }

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

      $tunjanganData = $this->getTunjanganService()->getTunjanganBreakdown($karyawan);
      $bpjsData = $this->getBpjsService()->calculateBpjsDeductions($karyawan);
      $lemburData = $this->getLemburService()->calculateTotalLemburForPeriode($karyawan, $periodeStart, $periodeEnd);

      $pph21Data = $this->getPph21Service()->calculatePph21WithBreakdown(
        $karyawan,
        $detail->gaji_pokok,
        $detail->total_tunjangan,
        $detail->total_lembur
      );

      $potonganAlfaData = $this->getPotonganService()->calculateAlfaDeduction($karyawan, $karyawanAttendance['total_alfa']);
      $potonganTerlambatData = $this->getPotonganService()->calculateKeterlambatanDeduction($karyawan, $karyawanAttendance['total_tidak_tepat']);

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
        'status_penggajian' => $detail->status_penggajian,
        'sudah_ditransfer' => $detail->sudah_ditransfer,
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
          'info' => $bpjsData['breakdown']
        ],
        'lembur_pay' => $detail->total_lembur,
        'lembur_detail' => [
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
}


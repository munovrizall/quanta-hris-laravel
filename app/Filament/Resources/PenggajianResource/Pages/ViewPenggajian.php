<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Lembur;
use App\Services\BpjsService;
use App\Services\LemburService;
use App\Services\PenaltyService;
use App\Services\Pph21Service;
use App\Services\TunjanganService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ViewPenggajian extends ViewRecord
{
  protected static string $resource = PenggajianResource::class;

  protected static ?string $title = 'Detail Penggajian';

  protected static ?string $breadcrumb = 'Detail';

  public function getColumnSpan(): int|string|array
  {
    return 'full';
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

  public function getColumnStart(): int|string|array
  {
    return 1;
  }

  public function infolist(Infolist $infolist): Infolist
  {
    // Get paginated data - now efficient!
    $paginatedKaryawan = $this->getPaginatedKaryawanFromDatabase($this->record);
    $karyawanData = $this->processKaryawanData($paginatedKaryawan, $this->record);

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

            Infolists\Components\TextEntry::make('estimated_total_karyawan')
              ->label('Total Karyawan')
              ->getStateUsing(function ($record): int {
                return $this->getTotalKaryawanCount($record);
              })
              ->badge()
              ->color('info'),

            Infolists\Components\TextEntry::make('estimated_total_gaji')
              ->label('Total Gaji')
              ->getStateUsing(function ($record): string {
                $totalGaji = $this->calculateTotalGaji($record);
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
                Infolists\Components\TextEntry::make('total_gaji_pokok')
                  ->label('Total Gaji Pokok')
                  ->getStateUsing(function ($record): string {
                    $total = $this->calculateTotalGajiPokok($record);
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('primary'),

                Infolists\Components\TextEntry::make('total_tunjangan')
                  ->label('Total Tunjangan')
                  ->getStateUsing(function ($record): string {
                    $total = $this->calculateTotalTunjangan($record);
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('info'),

                Infolists\Components\TextEntry::make('total_lembur_pay')
                  ->label('Total Upah Lembur')
                  ->getStateUsing(function ($record): string {
                    $total = $this->calculateTotalLembur($record);
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('warning'),

                Infolists\Components\TextEntry::make('total_potongan')
                  ->label('Total Potongan')
                  ->getStateUsing(function ($record): string {
                    $total = $this->calculateTotalPotongan($record);
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('danger'),
              ]),

            Infolists\Components\TextEntry::make('grand_total')
              ->label('GRAND TOTAL PENGGAJIAN')
              ->getStateUsing(function ($record): string {
                $total = $this->calculateTotalGaji($record);
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
                'pagination' => $paginatedKaryawan,
              ])
          ])
          ->collapsible()
          ->collapsed(false),
      ]);
  }

  /**
   * Get paginated karyawan directly from database - EFFICIENT!
   */
  private function getPaginatedKaryawanFromDatabase($record): LengthAwarePaginator
  {
    $periodeEnd = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->endOfMonth();

    return Karyawan::with(['golonganPtkp.kategoriTer'])
      ->whereDate('tanggal_mulai_bekerja', '<=', $periodeEnd)
      ->paginate(10, ['*'], 'page')
      ->withQueryString(); // Maintain query parameters
  }

  /**
   * Process only the current page karyawan data
   */
  private function processKaryawanData(LengthAwarePaginator $paginatedKaryawan, $record): array
  {
    $periodeStart = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->startOfMonth();
    $periodeEnd = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->endOfMonth();

    $processedData = [];

    foreach ($paginatedKaryawan->items() as $karyawan) {
      // Hitung absensi dan lembur
      $absensiData = $this->calculateAbsensi($karyawan->karyawan_id, $periodeStart, $periodeEnd);
      $lemburData = $this->calculateLembur($karyawan->karyawan_id, $periodeStart, $periodeEnd);

      // Gabungkan data lembur
      $combinedData = array_merge($absensiData, $lemburData);

      // Hitung gaji menggunakan data real dari database
      $gajiData = $this->calculateGajiFromDatabase($karyawan, $combinedData);

      $processedData[] = [
        'karyawan_id' => $karyawan->karyawan_id,
        'nama_lengkap' => $karyawan->nama_lengkap,
        'jabatan' => $karyawan->jabatan,
        'departemen' => $karyawan->departemen ?? 'N/A',
        'total_hadir' => $combinedData['total_hadir'],
        'total_alfa' => $combinedData['total_alfa'],
        'total_tidak_tepat' => $combinedData['total_tidak_tepat'],
        'total_lembur' => round($combinedData['total_lembur_hours'], 1),
        'total_lembur_sessions' => $combinedData['total_lembur_sessions'],
        'gaji_pokok' => $gajiData['gaji_pokok'],
        'tunjangan_total' => $gajiData['tunjangan_total'],
        'tunjangan_breakdown' => $gajiData['tunjangan_breakdown'], // ← Tunjangan breakdown
        'bpjs_breakdown' => $gajiData['bpjs_breakdown'], // ← BPJS breakdown - NEW!
        'lembur_pay' => $gajiData['lembur_pay'],
        'potongan_total' => $gajiData['potongan_total'],
        'total_gaji' => $gajiData['total_gaji'],
        'pph21_detail' => $gajiData['pph21_detail'],
        'potongan_detail' => $gajiData['potongan_detail'],
      ];
    }

    return $processedData;
  }

  /**
   * Get total karyawan count efficiently
   */
  private function getTotalKaryawanCount($record): int
  {
    $periodeEnd = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->endOfMonth();
    return Karyawan::whereDate('tanggal_mulai_bekerja', '<=', $periodeEnd)->count();
  }

  /**
   * *** DEBUG & FIXED VERSION: Calculate all totals dengan debugging ***
   */
  private function calculateAllTotals($record): array
  {
    $cacheKey = "all_totals_{$record->penggajian_id}_{$record->periode_tahun}_{$record->periode_bulan}";

    return cache()->remember($cacheKey, 300, function () use ($record) {
      $periodeStart = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->startOfMonth();
      $periodeEnd = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->endOfMonth();

      $totals = [
        'gaji_pokok' => 0,
        'tunjangan' => 0,
        'lembur' => 0,
        'potongan' => 0,
        'grand_total' => 0
      ];

      $debug = [
        'potongan_alfa' => 0,
        'potongan_terlambat' => 0,
        'potongan_bpjs' => 0,
        'potongan_pph21' => 0,
        'karyawan_count' => 0,
      ];

      $batchSize = 50;
      $pph21Service = new Pph21Service();

      Karyawan::with(['golonganPtkp.kategoriTer'])
        ->whereDate('tanggal_mulai_bekerja', '<=', $periodeEnd)
        ->chunk($batchSize, function ($karyawanChunk) use (&$totals, &$debug, $periodeStart, $periodeEnd, $pph21Service) {

          $karyawanIds = $karyawanChunk->pluck('karyawan_id');
          $absensiData = $this->getAbsensiDataBatch($karyawanIds, $periodeStart, $periodeEnd);
          $lemburData = $this->getLemburDataBatch($karyawanIds, $periodeStart, $periodeEnd);

          foreach ($karyawanChunk as $karyawan) {
            try {
              $debug['karyawan_count']++;

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

              // Menggunakan service untuk perhitungan yang akurat
              $tunjanganService = new TunjanganService();
              $potonganService = new PenaltyService();
              $bpjsService = new BpjsService();

              $gajiPokok = (float) $karyawan->gaji_pokok;
              $tunjanganData = $tunjanganService->calculateAllTunjangan($karyawan);
              $lemburPay = $combinedData['total_lembur_insentif'];

              // *** FIXED: Hitung penghasilan bruto untuk PPh21 ***
              $penghasilanBruto = $gajiPokok + $tunjanganData['total_tunjangan'] + $lemburPay;

              $alfaData = $potonganService->calculateAlfaDeduction($karyawan, $combinedData['total_alfa']);
              $keterlambatanData = $potonganService->calculateKeterlambatanDeduction($karyawan, $combinedData['total_tidak_tepat']);
              $bpjsData = $bpjsService->calculateBpjsDeductions($karyawan);

              // *** FIXED: Gunakan penghasilan bruto untuk PPh21 ***
              $individualPotonganPph21 = $pph21Service->calculateMonthlyPph21Deduction($karyawan, $penghasilanBruto);

              // Safety check PPh21
              if ($individualPotonganPph21 > ($penghasilanBruto * 0.3)) {
                Log::warning("PPh21 too high in totals calculation for karyawan {$karyawan->karyawan_id}");
                $individualPotonganPph21 = min($individualPotonganPph21, $penghasilanBruto * 0.15);
              }

              $totalPotongan = $alfaData['total_potongan'] + $keterlambatanData['total_potongan'] + $bpjsData['total_bpjs'] + $individualPotonganPph21;
              $totalGaji = $penghasilanBruto - $totalPotongan;

              // Akumulasi totals dengan data yang akurat
              $totals['gaji_pokok'] += $gajiPokok;
              $totals['tunjangan'] += $tunjanganData['total_tunjangan'];
              $totals['lembur'] += $lemburPay;
              $totals['potongan'] += $totalPotongan;
              $totals['grand_total'] += $totalGaji;

              // Akumulasi debug dengan data yang akurat
              $debug['potongan_alfa'] += $alfaData['total_potongan'];
              $debug['potongan_terlambat'] += $keterlambatanData['total_potongan'];
              $debug['potongan_bpjs'] += $bpjsData['total_bpjs'];
              $debug['potongan_pph21'] += $individualPotonganPph21;

            } catch (\Exception $e) {
              Log::warning("Error calculating totals for karyawan {$karyawan->karyawan_id}: " . $e->getMessage());
            }
          }
        });

      // Detailed logging dengan breakdown
      Log::info("=== DETAILED TOTALS BREAKDOWN (FIXED PPh21) ===", [
        'total_karyawan' => $debug['karyawan_count'],
        'gaji_pokok' => 'Rp ' . number_format($totals['gaji_pokok'], 0, ',', '.'),
        'tunjangan' => 'Rp ' . number_format($totals['tunjangan'], 0, ',', '.'),
        'lembur' => 'Rp ' . number_format($totals['lembur'], 0, ',', '.'),
        'potongan_breakdown' => [
          'alfa' => 'Rp ' . number_format($debug['potongan_alfa'], 0, ',', '.'),
          'terlambat' => 'Rp ' . number_format($debug['potongan_terlambat'], 0, ',', '.'),
          'bpjs' => 'Rp ' . number_format($debug['potongan_bpjs'], 0, ',', '.'),
          'pph21_from_bruto' => 'Rp ' . number_format($debug['potongan_pph21'], 0, ',', '.'),
          'total_potongan' => 'Rp ' . number_format($totals['potongan'], 0, ',', '.'),
        ],
        'grand_total' => 'Rp ' . number_format($totals['grand_total'], 0, ',', '.'),
        'verification_formula' => 'Rp ' . number_format(($totals['gaji_pokok'] + $totals['tunjangan'] + $totals['lembur']) - $totals['potongan'], 0, ',', '.'),
        'difference_check' => ($totals['grand_total'] == (($totals['gaji_pokok'] + $totals['tunjangan'] + $totals['lembur']) - $totals['potongan'])) ? 'VALID' : 'ERROR'
      ]);

      return $totals;
    });
  }

  // *** Update semua method statistik untuk menggunakan batch calculation ***
  private function calculateTotalGaji($record): float
  {
    $totals = $this->calculateAllTotals($record);
    return $totals['grand_total'];
  }

  private function calculateTotalGajiPokok($record): float
  {
    $totals = $this->calculateAllTotals($record);
    return $totals['gaji_pokok'];
  }

  private function calculateTotalTunjangan($record): float
  {
    $totals = $this->calculateAllTotals($record);
    return $totals['tunjangan'];
  }

  private function calculateTotalLembur($record): float
  {
    $totals = $this->calculateAllTotals($record);
    return $totals['lembur'];
  }

  private function calculateTotalPotongan($record): float
  {
    $totals = $this->calculateAllTotals($record);
    return $totals['potongan'];
  }

  /**
   * Get absensi data for multiple karyawan at once - BATCH OPERATION
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
   * Get lembur data for multiple karyawan at once - BATCH OPERATION
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
        // Parse total durasi to hours
        $durasi = Carbon::createFromFormat('H:i:s', $stats->total_durasi);
        $totalHours = $durasi->hour + ($durasi->minute / 60) + ($durasi->second / 3600);

        $result[$karyawanId] = [
          'total_lembur_hours' => round($totalHours, 1), // Format 1 desimal
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
   * Calculate absensi data for a specific employee in a period - SINGLE OPERATION
   */
  private function calculateAbsensi($karyawanId, $periodeStart, $periodeEnd): array
  {
    try {
      // Get basic absensi stats without complex lembur calculation
      $absensiStats = Absensi::where('karyawan_id', $karyawanId)
        ->whereBetween('tanggal', [$periodeStart->format('Y-m-d'), $periodeEnd->format('Y-m-d')])
        ->selectRaw('status_absensi, COUNT(*) as count')
        ->groupBy('status_absensi')
        ->get()
        ->keyBy('status_absensi');

      return [
        'total_hadir' => $absensiStats->get('Hadir')?->count ?? 0,
        'total_alfa' => $absensiStats->get('Alfa')?->count ?? 0,
        'total_tidak_tepat' => $absensiStats->get('Tidak Tepat')?->count ?? 0,
        'total_absensi' => $absensiStats->sum('count'),
      ];

    } catch (\Exception $e) {
      Log::error("Error calculating absensi for karyawan {$karyawanId}: " . $e->getMessage());

      return [
        'total_hadir' => 0,
        'total_alfa' => 0,
        'total_tidak_tepat' => 0,
        'total_absensi' => 0,
      ];
    }
  }

  /**
   * Calculate lembur data from lembur table - SINGLE OPERATION
   */
  private function calculateLembur($karyawanId, $periodeStart, $periodeEnd): array
  {
    try {
      $lemburService = new LemburService();

      // Get approved overtime data from lembur table
      $lemburData = Lembur::where('karyawan_id', $karyawanId)
        ->whereBetween('tanggal_lembur', [$periodeStart->format('Y-m-d'), $periodeEnd->format('Y-m-d')])
        ->where('status_lembur', 'Disetujui')
        ->with('karyawan')
        ->get();

      $totalLemburHours = 0;
      $totalLemburSessions = $lemburData->count();
      $totalInsentif = 0;

      foreach ($lemburData as $lembur) {
        try {
          // Parse durasi_lembur (TIME format: HH:MM:SS)
          $durasi = Carbon::createFromFormat('H:i:s', $lembur->durasi_lembur);
          $hours = $durasi->hour + ($durasi->minute / 60) + ($durasi->second / 3600);
          $totalLemburHours += $hours;

          // Gunakan total_insentif dari database atau fallback ke service
          if ($lembur->total_insentif && $lembur->total_insentif > 0) {
            $totalInsentif += $lembur->total_insentif;
          } else {
            $insentif = $lemburService->calculateInsentifFromLembur($lembur);
            $totalInsentif += $insentif;
          }
        } catch (\Exception $e) {
          Log::warning("Error parsing lembur data for lembur {$lembur->lembur_id}: " . $e->getMessage());
        }
      }

      return [
        'total_lembur_hours' => round($totalLemburHours, 1), // Format 1 desimal
        'total_lembur_sessions' => $totalLemburSessions,
        'total_lembur_insentif' => $totalInsentif,
      ];

    } catch (\Exception $e) {
      Log::error("Error calculating lembur for karyawan {$karyawanId}: " . $e->getMessage());

      return [
        'total_lembur_hours' => 0.0,
        'total_lembur_sessions' => 0,
        'total_lembur_insentif' => 0,
      ];
    }
  }

  // TODO: ADD searchbar

  // TODO: ADD enum in absensi for cuti and izin
  /**
   * Calculate salary components using real data from database - WITH BPJS BREAKDOWN
   */
  private function calculateGajiFromDatabase($karyawan, $combinedData): array
  {
    // Ambil gaji pokok dari database karyawan (REAL DATA)
    $gajiPokok = (float) $karyawan->gaji_pokok;

    // *** MENGGUNAKAN TUNJANGAN SERVICE ***
    $tunjanganService = new TunjanganService();
    $tunjanganData = $tunjanganService->calculateAllTunjangan($karyawan);
    $tunjanganTotal = $tunjanganData['total_tunjangan'];

    // Upah lembur (gunakan total_insentif dari database)
    $lemburPay = $combinedData['total_lembur_insentif'] ?? 0;

    // Total penghasilan bruto
    $penghasilanBruto = $gajiPokok + $tunjanganTotal + $lemburPay;

    // *** POTONGAN MENGGUNAKAN SERVICE ***
    $potonganService = new PenaltyService();
    $bpjsService = new BpjsService();
    $pph21Service = new Pph21Service();

    $alfaData = $potonganService->calculateAlfaDeduction($karyawan, $combinedData['total_alfa']);
    $keterlambatanData = $potonganService->calculateKeterlambatanDeduction($karyawan, $combinedData['total_tidak_tepat']);
    $bpjsData = $bpjsService->calculateBpjsDeductions($karyawan);

    // *** CONSISTENT: Menggunakan penghasilan bruto untuk PPh21 ***
    $potonganPph21 = $pph21Service->calculateMonthlyPph21Deduction($karyawan, $penghasilanBruto);

    $potonganAlfa = $alfaData['total_potongan'];
    $potonganTidakTepat = $keterlambatanData['total_potongan'];
    $potonganBPJS = $bpjsData['total_bpjs'];

    // Safety check untuk PPh21
    if ($potonganPph21 > ($penghasilanBruto * 0.3)) {
      Log::warning("PPh21 too high for karyawan {$karyawan->karyawan_id}", [
        'karyawan' => $karyawan->nama_lengkap,
        'penghasilan_bruto' => $penghasilanBruto,
        'pph21_amount' => $potonganPph21,
      ]);
      $potonganPph21 = min($potonganPph21, $penghasilanBruto * 0.15);
    }

    // *** FIXED: Pastikan getPph21Detail menggunakan penghasilan bruto yang sama ***
    $pph21Detail = $this->getPph21Detail($karyawan, $penghasilanBruto);

    // *** CONSISTENCY CHECK: Pastikan nilai sama ***
    if (abs($pph21Detail['jumlah'] - $potonganPph21) > 1) { // Allow 1 rupiah difference due to rounding
      Log::warning("PPh21 calculation inconsistency for karyawan {$karyawan->karyawan_id}", [
        'pph21_detail_jumlah' => $pph21Detail['jumlah'],
        'potongan_pph21' => $potonganPph21,
        'difference' => abs($pph21Detail['jumlah'] - $potonganPph21),
      ]);

      // Use the consistent value
      $pph21Detail['jumlah'] = $potonganPph21;
    }

    // *** FIXED: Hitung total potongan dengan semua komponen yang benar ***
    $potonganTotal = $potonganAlfa + $potonganTidakTepat + $potonganBPJS + $potonganPph21;
    $totalGaji = $penghasilanBruto - $potonganTotal;

    // *** DETAILED DEBUG LOG - BREAKDOWN ALL DEDUCTIONS ***
    Log::info("=== DETAILED POTONGAN BREAKDOWN: {$karyawan->nama_lengkap} ({$karyawan->karyawan_id}) ===", [
      'penghasilan_bruto' => 'Rp ' . number_format($penghasilanBruto, 0, ',', '.'),
      'potongan_breakdown' => [
        'alfa' => 'Rp ' . number_format($potonganAlfa, 0, ',', '.'),
        'terlambat' => 'Rp ' . number_format($potonganTidakTepat, 0, ',', '.'),
        'bpjs_kesehatan' => 'Rp ' . number_format($bpjsData['bpjs_kesehatan'], 0, ',', '.'),
        'bpjs_jht' => 'Rp ' . number_format($bpjsData['bpjs_jht'], 0, ',', '.'),
        'bpjs_jp' => 'Rp ' . number_format($bpjsData['bpjs_jp'], 0, ',', '.'),
        'total_bpjs' => 'Rp ' . number_format($potonganBPJS, 0, ',', '.'),
        'pph21' => 'Rp ' . number_format($potonganPph21, 0, ',', '.'),
      ],
      'calculation_check' => [
        'manual_sum' => 'Rp ' . number_format($potonganAlfa + $potonganTidakTepat + $potonganBPJS + $potonganPph21, 0, ',', '.'),
        'total_potongan' => 'Rp ' . number_format($potonganTotal, 0, ',', '.'),
        'match' => ($potonganTotal == ($potonganAlfa + $potonganTidakTepat + $potonganBPJS + $potonganPph21)) ? 'VALID' : 'ERROR',
      ],
      'final_result' => [
        'total_gaji' => 'Rp ' . number_format($totalGaji, 0, ',', '.'),
        'formula_check' => 'Rp ' . number_format($penghasilanBruto - $potonganTotal, 0, ',', '.'),
      ],
    ]);

    return [
      'gaji_pokok' => $gajiPokok,
      'tunjangan_total' => $tunjanganTotal,
      'tunjangan_detail' => $tunjanganData,
      'tunjangan_breakdown' => $tunjanganService->getTunjanganBreakdown($karyawan),
      'bpjs_breakdown' => $this->getBpjsBreakdown($bpjsData),
      'lembur_pay' => $lemburPay,
      'potongan_total' => $potonganTotal,
      'total_gaji' => max(0, $totalGaji),
      'pph21_detail' => $pph21Detail,
      'potongan_detail' => [
        'alfa' => $alfaData,
        'keterlambatan' => $keterlambatanData,
        'bpjs' => $potonganBPJS,
        'bpjs_full' => $bpjsData,
        'pph21' => $potonganPph21,
        // *** ADD INDIVIDUAL BPJS COMPONENTS FOR DEBUG ***
        'bpjs_kesehatan' => $bpjsData['bpjs_kesehatan'],
        'bpjs_jht' => $bpjsData['bpjs_jht'],
        'bpjs_jp' => $bpjsData['bpjs_jp'],
      ]
    ];
  }

  /**
   * Get BPJS breakdown for display - NEW METHOD
   */
  private function getBpjsBreakdown($bpjsData): array
  {
    $breakdown = [];

    // BPJS Kesehatan
    if ($bpjsData['bpjs_kesehatan'] > 0) {
      $batasKesehatan = $bpjsData['breakdown']['batas_kesehatan'] ?? 12000000;
      $persenKesehatan = ($bpjsData['breakdown']['persen_kesehatan'] ?? 0.01) * 100;

      $breakdown[] = [
        'label' => 'BPJS Kesehatan',
        'amount' => $bpjsData['bpjs_kesehatan'],
        'description' => "{$persenKesehatan}% dari gaji (max Rp " . number_format($batasKesehatan, 0, ',', '.') . ")"
      ];
    }

    // BPJS Jaminan Hari Tua (JHT)
    if ($bpjsData['bpjs_jht'] > 0) {
      $persenJht = ($bpjsData['breakdown']['persen_jht'] ?? 0.02) * 100;

      $breakdown[] = [
        'label' => 'BPJS JHT',
        'amount' => $bpjsData['bpjs_jht'],
        'description' => "{$persenJht}% dari gaji pokok (tanpa batas)"
      ];
    }

    // BPJS Jaminan Pensiun (JP)
    if ($bpjsData['bpjs_jp'] > 0) {
      $batasPensiun = $bpjsData['breakdown']['batas_pensiun'] ?? 10547400;
      $persenJp = ($bpjsData['breakdown']['persen_jp'] ?? 0.01) * 100;

      $breakdown[] = [
        'label' => 'BPJS Jaminan Pensiun',
        'amount' => $bpjsData['bpjs_jp'],
        'description' => "{$persenJp}% dari gaji (max Rp " . number_format($batasPensiun, 0, ',', '.') . ")"
      ];
    }

    return [
      'breakdown' => $breakdown,
      'total_amount' => $bpjsData['total_bpjs'],
      'info' => [
        'gaji_pokok' => $bpjsData['breakdown']['gaji_pokok'] ?? 0,
        'total_percentage' => round(($bpjsData['total_bpjs'] / ($bpjsData['breakdown']['gaji_pokok'] ?? 1)) * 100, 2),
      ]
    ];
  }


  /**
   * Get detailed PPh21 calculation information
   */
  private function getPph21Detail($karyawan, $penghasilanBruto): array
  {
    $golonganPtkp = $karyawan->golonganPtkp;

    if (!$golonganPtkp) {
      return [
        'jumlah' => 0,
        'tarif_persen' => 0,
        'golongan_ptkp' => 'N/A',
        'kategori_ter' => 'N/A',
        'penghasilan_bruto' => $penghasilanBruto,
      ];
    }

    // Cari tarif TER yang sesuai
    $tarifTer = \App\Models\TarifTer::where('kategori_ter_id', $golonganPtkp->kategori_ter_id)
      ->where('batas_bawah', '<=', $penghasilanBruto)
      ->where('batas_atas', '>=', $penghasilanBruto)
      ->first();

    $tarifPersen = 0;
    $kategoriTer = 'N/A';

    if ($tarifTer) {
      $tarifPersen = $tarifTer->tarif * 100;
      $kategoriTer = $golonganPtkp->kategoriTer?->nama_kategori ?? 'TER-' . $golonganPtkp->kategori_ter_id;
    }

    // *** FIXED: Hitung PPh21 menggunakan penghasilan bruto yang benar ***
    $pph21Service = new Pph21Service();
    $jumlahPph21 = $pph21Service->calculateMonthlyPph21Deduction($karyawan, $penghasilanBruto); // ← Pass penghasilan bruto

    // *** DEBUG LOG - Check calculation consistency ***
    Log::info("=== PPh21 DETAIL CHECK: {$karyawan->nama_lengkap} ===", [
      'penghasilan_bruto_passed' => $penghasilanBruto,
      'pph21_calculated' => $jumlahPph21,
      'tarif_persen' => $tarifPersen,
      'tarif_ter_info' => $tarifTer ? [
        'batas_bawah' => $tarifTer->batas_bawah,
        'batas_atas' => $tarifTer->batas_atas,
        'tarif' => $tarifTer->tarif,
      ] : 'null'
    ]);

    return [
      'jumlah' => $jumlahPph21, // ← This should match the calculation
      'tarif_persen' => $tarifPersen,
      'golongan_ptkp' => $golonganPtkp->nama_golongan_ptkp,
      'kategori_ter' => $kategoriTer,
      'penghasilan_bruto' => $penghasilanBruto,
    ];
  }
}
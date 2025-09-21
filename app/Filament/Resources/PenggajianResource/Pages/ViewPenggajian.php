<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\DetailPenggajian;
use App\Services\TunjanganService;
use App\Services\BpjsService;
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

    foreach ($paginatedDetailPenggajian->items() as $detail) {
      $karyawan = $detail->karyawan;

      if (!$karyawan) {
        Log::warning("Karyawan not found for detail penggajian {$detail->id}");
        continue;
      }

      // Generate additional info for display (BPJS breakdown, tunjangan breakdown, etc.)
      $tunjanganBreakdown = $this->getTunjanganBreakdownForDisplay($karyawan);
      $bpjsBreakdown = $this->getBpjsBreakdownForDisplay($detail);
      $pph21Detail = $this->getPph21DetailForDisplay($karyawan, $detail);

      $processedData[] = [
        'karyawan_id' => $karyawan->karyawan_id,
        'nama_lengkap' => $karyawan->nama_lengkap,
        'jabatan' => $karyawan->jabatan,
        'departemen' => $karyawan->departemen ?? 'N/A',
        'total_hadir' => '-', // Will be calculated from absensi if needed
        'total_alfa' => '-',
        'total_tidak_tepat' => '-',
        'total_lembur' => '-',
        'total_lembur_sessions' => '-',
        'gaji_pokok' => $detail->gaji_pokok,
        'tunjangan_total' => $detail->total_tunjangan,
        'tunjangan_breakdown' => $tunjanganBreakdown,
        'bpjs_breakdown' => $bpjsBreakdown,
        'lembur_pay' => $detail->total_lembur,
        'potongan_total' => $detail->total_potongan,
        'total_gaji' => $detail->gaji_bersih,
        'pph21_detail' => $pph21Detail,
        'potongan_detail' => [
          'alfa' => ['total_potongan' => $detail->potongan_alfa],
          'keterlambatan' => ['total_potongan' => $detail->potongan_terlambat],
          'bpjs' => $detail->potongan_bpjs,
          'pph21' => $detail->potongan_pph21,
          'bpjs_kesehatan' => 0, // Could be calculated if needed
          'bpjs_jht' => 0,
          'bpjs_jp' => 0,
        ],
      ];
    }

    return $processedData;
  }

  /**
   * Database-based calculation methods
   */
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
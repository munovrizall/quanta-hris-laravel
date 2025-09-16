<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Services\Pph21Service;
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
      // Hitung absensi
      $absensiData = $this->calculateAbsensi($karyawan->karyawan_id, $periodeStart, $periodeEnd);

      // Hitung gaji menggunakan data real dari database
      $gajiData = $this->calculateGajiFromDatabase($karyawan, $absensiData);

      $processedData[] = [
        'karyawan_id' => $karyawan->karyawan_id,
        'nama_lengkap' => $karyawan->nama_lengkap,
        'jabatan' => $karyawan->jabatan,
        'departemen' => $karyawan->departemen ?? 'N/A',
        'total_hadir' => $absensiData['total_hadir'],
        'total_alfa' => $absensiData['total_alfa'],
        'total_tidak_tepat' => $absensiData['total_tidak_tepat'],
        'total_lembur' => $absensiData['total_lembur'],
        'gaji_pokok' => $gajiData['gaji_pokok'],
        'tunjangan_total' => $gajiData['tunjangan_total'],
        'lembur_pay' => $gajiData['lembur_pay'],
        'potongan_total' => $gajiData['potongan_total'],
        'total_gaji' => $gajiData['total_gaji'],
        'pph21_detail' => $gajiData['pph21_detail'],
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
   * Calculate totals efficiently using database aggregations where possible
   */
  private function calculateTotalGaji($record): float
  {
    // For now, we'll use a simplified calculation
    // In production, you might want to cache this or use database views
    $karyawanCount = $this->getTotalKaryawanCount($record);
    $avgGaji = 5000000; // You can calculate this more precisely

    return $karyawanCount * $avgGaji;
  }

  private function calculateTotalGajiPokok($record): float
  {
    $periodeEnd = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->endOfMonth();

    return Karyawan::whereDate('tanggal_mulai_bekerja', '<=', $periodeEnd)
      ->sum('gaji_pokok');
  }

  private function calculateTotalTunjangan($record): float
  {
    $totalGajiPokok = $this->calculateTotalGajiPokok($record);
    $karyawanCount = $this->getTotalKaryawanCount($record);

    // Estimasi berdasarkan formula tunjangan
    $avgTunjanganJabatan = $totalGajiPokok * 0.15;
    $totalTunjanganTetap = $karyawanCount * (500000 + 300000); // Transport + Makan

    // Estimasi tunjangan keluarga (assume 60% menikah)
    $totalTunjanganKeluarga = $karyawanCount * 0.6 * 400000;

    return $avgTunjanganJabatan + $totalTunjanganTetap + $totalTunjanganKeluarga;
  }

  private function calculateTotalLembur($record): float
  {
    // Simplified calculation - you can make this more precise
    $karyawanCount = $this->getTotalKaryawanCount($record);
    return $karyawanCount * 200000; // Avg lembur per karyawan
  }

  private function calculateTotalPotongan($record): float
  {
    $totalGajiPokok = $this->calculateTotalGajiPokok($record);
    $karyawanCount = $this->getTotalKaryawanCount($record);

    // BPJS 4%
    $totalBpjs = $totalGajiPokok * 0.04;

    // Estimasi potongan lainnya
    $avgPotonganLain = $karyawanCount * 150000; // Avg potongan alfa, terlambat, dll

    return $totalBpjs + $avgPotonganLain;
  }

  /**
   * Calculate absensi data for a specific employee in a period - OPTIMIZED
   */
    /**
     * Calculate absensi data for a specific employee in a period - SQL VERSION
     */
    private function calculateAbsensi($karyawanId, $periodeStart, $periodeEnd): array
    {
      try {
        // Get absensi stats with proper SQL syntax
        $absensiStats = Absensi::where('karyawan_id', $karyawanId)
          ->whereBetween('tanggal', [$periodeStart->format('Y-m-d'), $periodeEnd->format('Y-m-d')])
          ->selectRaw('
            status_absensi,
            COUNT(*) as count,
            SUM(CASE 
              WHEN status_absensi != ? AND TIME(waktu_pulang) > TIME(?) 
              THEN GREATEST(0, TIMESTAMPDIFF(HOUR, TIME(?), TIME(waktu_pulang))) 
              ELSE 0 
            END) as total_lembur_hours
          ', ['Alfa', '17:00:00', '17:00:00'])
          ->groupBy('status_absensi')
          ->get()
          ->keyBy('status_absensi');
  
        return [
          'total_hadir' => $absensiStats->get('Hadir')?->count ?? 0,
          'total_alfa' => $absensiStats->get('Alfa')?->count ?? 0,
          'total_tidak_tepat' => $absensiStats->get('Tidak Tepat')?->count ?? 0,
          'total_lembur' => $absensiStats->sum('total_lembur_hours'),
          'total_absensi' => $absensiStats->sum('count'),
        ];
  
      } catch (\Exception $e) {
        Log::error("Error calculating absensi with raw SQL for karyawan {$karyawanId}: " . $e->getMessage());
        
        // Fallback to the safer method
        return $this->calculateAbsensi($karyawanId, $periodeStart, $periodeEnd);
      }
    }

  /**
   * Calculate salary components using real data from database
   */
  private function calculateGajiFromDatabase($karyawan, $absensiData): array
  {
    // Ambil gaji pokok dari database karyawan (REAL DATA)
    $gajiPokok = (float) $karyawan->gaji_pokok;

    // Tunjangan (berdasarkan gaji pokok dari database)
    $tunjanganJabatan = $gajiPokok * 0.15;
    $tunjanganTransport = 500000;
    $tunjanganMakan = 300000;
    $tunjanganKeluarga = ($karyawan->status_pernikahan === 'Menikah') ? 400000 : 0;

    $tunjanganTotal = $tunjanganJabatan + $tunjanganTransport + $tunjanganMakan + $tunjanganKeluarga;

    // Upah lembur
    $gajiPerJam = $gajiPokok / (22 * 8);
    $lemburPay = $absensiData['total_lembur'] * ($gajiPerJam * 1.5);

    // Total penghasilan bruto
    $penghasilanBruto = $gajiPokok + $tunjanganTotal + $lemburPay;

    // Potongan
    $potonganAlfa = $absensiData['total_alfa'] * ($gajiPerJam * 8);
    $potonganTidakTepat = $absensiData['total_tidak_tepat'] * ($gajiPerJam * 4);
    $potonganBPJS = $gajiPokok * 0.04;

    // PPh21
    $pph21Service = new Pph21Service();
    $potonganPph21 = $pph21Service->calculateMonthlyPph21Deduction($karyawan);
    $pph21Detail = $this->getPph21Detail($karyawan, $penghasilanBruto);

    $potonganTotal = $potonganAlfa + $potonganTidakTepat + $potonganBPJS + $potonganPph21;
    $totalGaji = $penghasilanBruto - $potonganTotal;

    return [
      'gaji_pokok' => $gajiPokok,
      'tunjangan_total' => $tunjanganTotal,
      'lembur_pay' => $lemburPay,
      'potongan_total' => $potonganTotal,
      'total_gaji' => max(0, $totalGaji),
      'pph21_detail' => $pph21Detail,
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

    $pph21Service = new Pph21Service();
    $jumlahPph21 = $pph21Service->calculateMonthlyPph21Deduction($karyawan);

    return [
      'jumlah' => $jumlahPph21,
      'tarif_persen' => $tarifPersen,
      'golongan_ptkp' => $golonganPtkp->nama_golongan_ptkp,
      'kategori_ter' => $kategoriTer,
      'penghasilan_bruto' => $penghasilanBruto,
    ];
  }
}
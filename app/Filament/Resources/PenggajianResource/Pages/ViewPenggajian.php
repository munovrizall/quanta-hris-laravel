<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\Lembur;
use App\Services\LemburService;
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
        'total_lembur_sessions' => $combinedData['total_lembur_sessions'], // Jumlah sesi lembur
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
    $periodeStart = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->startOfMonth();
    $periodeEnd = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->endOfMonth();

    // Get total approved overtime insentif for the period
    return Lembur::whereBetween('tanggal_lembur', [$periodeStart->format('Y-m-d'), $periodeEnd->format('Y-m-d')])
      ->where('status_lembur', 'Disetujui')
      ->sum('total_insentif') ?? 0;
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
   * Calculate absensi data for a specific employee in a period - FIXED
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
   * Calculate lembur data from lembur table - UPDATED to use total_insentif
   */
  private function calculateLembur($karyawanId, $periodeStart, $periodeEnd): array
  {
    try {
      $lemburService = new LemburService(); // Inisialisasi service

      // Get approved overtime data from lembur table
      $lemburData = Lembur::where('karyawan_id', $karyawanId)
        ->whereBetween('tanggal_lembur', [$periodeStart->format('Y-m-d'), $periodeEnd->format('Y-m-d')])
        ->where('status_lembur', 'Disetujui') // Only approved overtime
        ->with('karyawan') // Eager load untuk efisiensi
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

          // *** GUNAKAN SERVICE untuk konsistensi ***
          if ($lembur->total_insentif && $lembur->total_insentif > 0) {
            $totalInsentif += $lembur->total_insentif;
          } else {
            // Fallback: hitung menggunakan service jika total_insentif kosong
            $insentif = $lemburService->calculateInsentifFromLembur($lembur);
            $totalInsentif += $insentif;
          }
        } catch (\Exception $e) {
          Log::warning("Error parsing lembur data for lembur {$lembur->lembur_id}: " . $e->getMessage());
        }
      }

      Log::info("Lembur calculated for karyawan {$karyawanId}: {$totalLemburSessions} sessions, {$totalLemburHours} hours, " . $lemburService->formatRupiah($totalInsentif));

      return [
        'total_lembur_hours' => $totalLemburHours,
        'total_lembur_sessions' => $totalLemburSessions,
        'total_lembur_insentif' => $totalInsentif,
      ];

    } catch (\Exception $e) {
      Log::error("Error calculating lembur for karyawan {$karyawanId}: " . $e->getMessage());

      return [
        'total_lembur_hours' => 0,
        'total_lembur_sessions' => 0,
        'total_lembur_insentif' => 0,
      ];
    }
  }

  /**
   * Calculate salary components using real data from database - UPDATED for insentif
   */
  private function calculateGajiFromDatabase($karyawan, $combinedData): array
  {
    // Ambil gaji pokok dari database karyawan (REAL DATA)
    $gajiPokok = (float) $karyawan->gaji_pokok;

    // Tunjangan (berdasarkan gaji pokok dari database)
    $tunjanganJabatan = $gajiPokok * 0.15;
    $tunjanganTransport = 500000;
    $tunjanganMakan = 300000;
    $tunjanganKeluarga = ($karyawan->status_pernikahan === 'Menikah') ? 400000 : 0;

    $tunjanganTotal = $tunjanganJabatan + $tunjanganTransport + $tunjanganMakan + $tunjanganKeluarga;

    // Upah lembur (gunakan total_insentif dari database)
    $lemburPay = $combinedData['total_lembur_insentif'] ?? 0;

    // Total penghasilan bruto
    $penghasilanBruto = $gajiPokok + $tunjanganTotal + $lemburPay;

    // Potongan
    $gajiPerJam = $gajiPokok / (22 * 8);
    $potonganAlfa = $combinedData['total_alfa'] * ($gajiPerJam * 8);
    $potonganTidakTepat = $combinedData['total_tidak_tepat'] * ($gajiPerJam * 4);
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
      'lembur_pay' => $lemburPay, // Sekarang menggunakan insentif dari database
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
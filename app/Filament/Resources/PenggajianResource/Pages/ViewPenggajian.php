<?php

namespace App\Filament\Resources\PenggajianResource\Pages;

use App\Filament\Resources\PenggajianResource;
use App\Models\Karyawan;
use App\Models\Absensi;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ViewPenggajian extends ViewRecord
{
  protected static string $resource = PenggajianResource::class;

  protected static ?string $title = 'Detail Penggajian';

  protected static ?string $breadcrumb = 'Detail';

  // Add this method to define column span
  public function getColumnSpan(): int|string|array
  {
    return '4'; // or return 12; for full width
  }
  // Add this method to define column span

  public function getColumnStart(): int|string|array
  {
    return 'full'; // or return 12; for full width
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
                return $this->getKaryawanData($record)->count();
              })
              ->badge()
              ->color('info'),

            Infolists\Components\TextEntry::make('estimated_total_gaji')
              ->label('Total Gaji')
              ->getStateUsing(function ($record): string {
                $totalGaji = $this->getKaryawanData($record)->sum('total_gaji');
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
                    $total = $this->getKaryawanData($record)->sum('gaji_pokok');
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('primary'),

                Infolists\Components\TextEntry::make('total_tunjangan')
                  ->label('Total Tunjangan')
                  ->getStateUsing(function ($record): string {
                    $total = $this->getKaryawanData($record)->sum('tunjangan_total');
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('info'),

                Infolists\Components\TextEntry::make('total_lembur_pay')
                  ->label('Total Upah Lembur')
                  ->getStateUsing(function ($record): string {
                    $total = $this->getKaryawanData($record)->sum('lembur_pay');
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('warning'),

                Infolists\Components\TextEntry::make('total_potongan')
                  ->label('Total Potongan')
                  ->getStateUsing(function ($record): string {
                    $total = $this->getKaryawanData($record)->sum('potongan_total');
                    return 'Rp ' . number_format($total, 0, ',', '.');
                  })
                  ->color('danger'),
              ]),

            Infolists\Components\TextEntry::make('grand_total')
              ->label('GRAND TOTAL PENGGAJIAN')
              ->getStateUsing(function ($record): string {
                $total = $this->getKaryawanData($record)->sum('total_gaji');
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
                'karyawanData' => $this->getKaryawanData($this->record)
              ])
          ])
          ->collapsible()
          ->collapsed(false),
      ]);
  }

  /**
   * Get data karyawan beserta detail gaji untuk periode tertentu
   */
  private function getKaryawanData($record): Collection
  {
    $periodeStart = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->startOfMonth();
    $periodeEnd = Carbon::create($record->periode_tahun, $record->periode_bulan, 1)->endOfMonth();

    // Ambil semua karyawan yang aktif di periode tersebut
    $karyawans = Karyawan::whereDate('tanggal_mulai_bekerja', '<=', $periodeEnd)
      ->get();

    return $karyawans->map(function ($karyawan) use ($periodeStart, $periodeEnd) {
      // Hitung absensi
      $absensiData = $this->calculateAbsensi($karyawan->karyawan_id, $periodeStart, $periodeEnd);

      // Hitung gaji
      $gajiData = $this->calculateGaji($karyawan, $absensiData);

      return [
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
      ];
    });
  }

  /**
   * Calculate absensi data for a specific employee in a period
   */
  private function calculateAbsensi($karyawanId, $periodeStart, $periodeEnd): array
  {
    $absensi = Absensi::where('karyawan_id', $karyawanId)
      ->whereBetween('tanggal', [$periodeStart->format('Y-m-d'), $periodeEnd->format('Y-m-d')])
      ->get();

    $totalHadir = $absensi->where('status_absensi', 'Hadir')->count();
    $totalAlfa = $absensi->where('status_absensi', 'Alfa')->count();
    $totalTidakTepat = $absensi->where('status_absensi', 'Tidak Tepat')->count();

    // Hitung lembur (estimasi berdasarkan jam pulang > 18:00)
    $totalLembur = $absensi->filter(function ($abs) {
      if ($abs->waktu_pulang && $abs->status_absensi !== 'Alfa') {
        $jamPulang = Carbon::parse($abs->waktu_pulang)->format('H:i');
        return $jamPulang > '18:00';
      }
      return false;
    })->sum(function ($abs) {
      $jamPulang = Carbon::parse($abs->waktu_pulang);
      $jamStandar = Carbon::parse(date('Y-m-d', strtotime($abs->tanggal)) . ' 17:00:00');
      return max(0, $jamPulang->diffInHours($jamStandar));
    });

    return [
      'total_hadir' => $totalHadir,
      'total_alfa' => $totalAlfa,
      'total_tidak_tepat' => $totalTidakTepat,
      'total_lembur' => $totalLembur,
      'total_absensi' => $absensi->count(),
    ];
  }

  /**
   * Calculate salary components for an employee
   */
  private function calculateGaji($karyawan, $absensiData): array
  {
    // Gaji pokok berdasarkan jabatan/level
    $gajiPokok = $this->getGajiPokok($karyawan->jabatan);

    // Tunjangan
    $tunjanganJabatan = $gajiPokok * 0.15; // 15% dari gaji pokok
    $tunjanganTransport = 500000;
    $tunjanganMakan = 300000;
    $tunjanganKeluarga = ($karyawan->status_pernikahan === 'Menikah') ? 400000 : 0;

    $tunjanganTotal = $tunjanganJabatan + $tunjanganTransport + $tunjanganMakan + $tunjanganKeluarga;

    // Upah lembur (1.5x dari gaji per jam)
    $gajiPerJam = $gajiPokok / (22 * 8); // 22 hari kerja, 8 jam per hari
    $lemburPay = $absensiData['total_lembur'] * ($gajiPerJam * 1.5);

    // Potongan
    $potonganAlfa = $absensiData['total_alfa'] * ($gajiPerJam * 8); // Potongan per hari alfa
    $potonganTidakTepat = $absensiData['total_tidak_tepat'] * ($gajiPerJam * 4); // 50% potongan
    $potonganBPJS = $gajiPokok * 0.04; // 4% BPJS
    $potonganPajak = $this->calculatePajak($gajiPokok + $tunjanganTotal + $lemburPay);

    $potonganTotal = $potonganAlfa + $potonganTidakTepat + $potonganBPJS + $potonganPajak;

    // Total gaji
    $totalGaji = $gajiPokok + $tunjanganTotal + $lemburPay - $potonganTotal;

    return [
      'gaji_pokok' => $gajiPokok,
      'tunjangan_total' => $tunjanganTotal,
      'lembur_pay' => $lemburPay,
      'potongan_total' => $potonganTotal,
      'total_gaji' => max(0, $totalGaji), // Pastikan tidak minus
    ];
  }

  /**
   * Get basic salary based on position
   */
  private function getGajiPokok($jabatan): int
  {
    return match (true) {
      str_contains($jabatan, 'CEO') => 25000000,
      str_contains($jabatan, 'Manager') => 15000000,
      str_contains($jabatan, 'Administrator') => 12000000,
      str_contains($jabatan, 'Staff') => 8000000,
      str_contains($jabatan, 'Officer') => 7000000,
      default => 5000000,
    };
  }

  /**
   * Calculate tax based on total income
   */
  private function calculatePajak($totalIncome): int
  {
    // Simplified tax calculation (PPh 21)
    if ($totalIncome <= 4500000) {
      return 0;
    } elseif ($totalIncome <= 50000000) {
      return ($totalIncome - 4500000) * 0.05; // 5%
    } elseif ($totalIncome <= 250000000) {
      return 2275000 + (($totalIncome - 50000000) * 0.15); // 15%
    } else {
      return 32275000 + (($totalIncome - 250000000) * 0.25); // 25%
    }
  }
}
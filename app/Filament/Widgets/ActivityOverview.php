<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use App\Models\Karyawan;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;

class ActivityOverview extends Widget
{
  protected static string $view = 'filament.widgets.activity-overview';

  protected int|string|array $columnSpan = 'full';

  protected static ?string $pollingInterval = '30s';

  protected static ?int $sort = 2;

  protected static ?string $heading = 'Karyawan yang Sudah Absensi Hari Ini';

  public function getHeading(): string
  {
    return static::$heading;
  }

  protected function getViewData(): array
  {
    return [
      'karyawanAbsensi' => $this->getKaryawanAbsensiHariIni(),
      'totalAbsensi' => $this->getTotalAbsensiHariIni(),
      'totalKaryawan' => $this->getTotalKaryawan(),
      'tanggalHariIni' => now()->locale('id')->translatedFormat('l, d F Y'),
      'heading' => $this->getHeading(),
      'htmlContent' => $this->generateHtmlContent(),
    ];
  }

  public function getKaryawanAbsensiHariIni()
  {
    return Absensi::with('karyawan')
      ->whereDate('tanggal', Carbon::today())
      ->whereNotNull('waktu_masuk')
      ->whereIn('status_absensi', ['Hadir', 'Terlambat'])
      ->orderBy('waktu_masuk', 'asc')
      ->get()
      ->map(function ($absensi) {
        $karyawan = $absensi->karyawan;
        return [
          'nama_lengkap' => $karyawan->nama_lengkap,
          'initial' => $this->getInitials($karyawan->nama_lengkap),
          'waktu_masuk' => $absensi->waktu_masuk->format('H:i'),
          'status' => $absensi->status_absensi,
          'avatar_color' => $this->getAvatarColorHex($karyawan->nama_lengkap),
          'status_color' => $this->getStatusColor($absensi->status_absensi),
        ];
      });
  }

  private function getInitials(string $name): string
  {
    $words = explode(' ', $name);
    $initials = '';

    foreach ($words as $word) {
      if (!empty($word)) {
        $initials .= strtoupper($word[0]);
        if (strlen($initials) >= 2)
          break;
      }
    }

    return $initials ?: 'N/A';
  }

  private function getAvatarColorHex(string $name): string
  {
    $hexColors = [
      '#3b82f6', // blue
      '#10b981', // green
      '#8b5cf6', // purple
      '#ec4899', // pink
      '#6366f1', // indigo
      '#ef4444', // red
      '#f59e0b', // yellow
      '#14b8a6', // teal
      '#f97316', // orange
      '#06b6d4', // cyan
    ];

    $index = strlen($name) % count($hexColors);
    return $hexColors[$index];
  }

  private function getStatusColor(string $status): array
  {
    return match ($status) {
      'Hadir' => [
        'bg' => 'rgb(220 252 231)',
        'text' => 'rgb(22 101 52)',
        'indicator' => '#10b981'
      ],
      'Terlambat' => [
        'bg' => 'rgb(254 249 195)',
        'text' => 'rgb(146 64 14)',
        'indicator' => '#f59e0b'
      ],
      default => [
        'bg' => 'rgb(243 244 246)',
        'text' => 'rgb(55 65 81)',
        'indicator' => '#6b7280'
      ]
    };
  }

  public function getTotalAbsensiHariIni(): int
  {
    return Absensi::whereDate('tanggal', Carbon::today())
      ->whereNotNull('waktu_masuk')
      ->whereIn('status_absensi', ['Hadir', 'Terlambat'])
      ->count();
  }

  public function getTotalKaryawan(): int
  {
    return Karyawan::count();
  }

  private function generateHtmlContent(): string
  {
    $karyawanAbsensi = $this->getKaryawanAbsensiHariIni();
    $totalAbsensi = $this->getTotalAbsensiHariIni();
    $totalKaryawan = $this->getTotalKaryawan();
    $tanggalHariIni = now()->locale('id')->translatedFormat('l, d F Y');
    $heading = $this->getHeading();

    $karyawanCards = '';
    foreach ($karyawanAbsensi as $karyawan) {
      $statusColors = $karyawan['status_color'];

      $karyawanCards .= "
                <div class=\"flex flex-col items-center flex-shrink-0\" style=\"gap: 0.75rem;\">
                    <div class=\"relative\">
                        <div class=\"w-16 h-16 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg ring-2 ring-white\"
                             style=\"background-color: {$karyawan['avatar_color']};\">
                            {$karyawan['initial']}
                        </div>
                        <div class=\"absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-white\"
                             style=\"border-width: 3px; background-color: {$statusColors['indicator']};\">
                        </div>
                    </div>
                    
                    <div class=\"text-center\">
                        <p class=\"text-sm font-medium text-gray-900 dark:text-white truncate\" 
                           style=\"max-width: 6rem;\"
                           title=\"{$karyawan['nama_lengkap']}\">
                            {$karyawan['nama_lengkap']}
                        </p>
                        <p class=\"text-xs text-gray-500 dark:text-gray-400 mt-1\">
                            {$karyawan['waktu_masuk']}
                        </p>
                        <span class=\"inline-flex items-center rounded-full px-2 py-1 text-xs font-medium mt-1\"
                              style=\"background-color: {$statusColors['bg']}; color: {$statusColors['text']};\">
                            {$karyawan['status']}
                        </span>
                    </div>
                </div>
            ";
    }

    $emptyState = "
            <div class=\"text-center py-12\">
                <div class=\"mx-auto h-16 w-16 text-gray-400 mb-4\">
                    <svg fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" aria-hidden=\"true\">
                        <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"1.5\" d=\"M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239\" />
                    </svg>
                </div>
                <h3 class=\"text-lg font-medium text-gray-900 dark:text-white mb-2\">Belum Ada Absensi</h3>
                <p class=\"text-sm text-gray-500 dark:text-gray-400\">
                    Belum ada karyawan yang melakukan absensi hari ini.
                </p>
            </div>
        ";

    $content = count($karyawanAbsensi) > 0 ? "
            <div class=\"w-full overflow-x-auto\">
                <div class=\"flex pb-4\" style=\"gap: 1.5rem; width: max-content;\">
                    {$karyawanCards}
                </div>
            </div>
        " : $emptyState;

    return "
            <div class=\"filament-stats-overview-widget\">
                <div class=\"fi-wi-stats-overview-widget\">
                    <div class=\"rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10\">
                        <div class=\"p-6\">
                            <div class=\"flex justify-between items-center mb-6\">
                                <div class=\"flex items-center space-x-3\">
                                    <h3 class=\"text-lg font-semibold text-gray-900 dark:text-white\">
                                        {$heading}
                                    </h3>
                                    <span class=\"inline-flex items-center rounded-full px-3 py-1 text-sm font-medium\" 
                                          style=\"background-color: rgb(219 234 254); color: rgb(30 64 175);\">
                                        {$totalAbsensi}/{$totalKaryawan}
                                    </span>
                                </div>
                                <div class=\"text-sm text-gray-500 dark:text-gray-400\">
                                    {$tanggalHariIni}
                                </div>
                            </div>

                            {$content}

                            <div class=\"border-t border-gray-200 dark:border-gray-700 pt-4 mt-6\">
                                <div class=\"flex justify-center text-sm\" style=\"gap: 1.5rem;\">
                                    <div class=\"flex items-center\" style=\"gap: 0.5rem;\">
                                        <div class=\"w-3 h-3 rounded-full\" style=\"background-color: #10b981;\"></div>
                                        <span class=\"text-gray-600 dark:text-gray-400\">Hadir</span>
                                    </div>
                                    <div class=\"flex items-center\" style=\"gap: 0.5rem;\">
                                        <div class=\"w-3 h-3 rounded-full\" style=\"background-color: #f59e0b;\"></div>
                                        <span class=\"text-gray-600 dark:text-gray-400\">Terlambat</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ";
  }
}
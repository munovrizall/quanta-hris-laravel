<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use App\Models\Karyawan;
use Carbon\Carbon;
use Filament\Widgets\Widget;

class ActivityOverview extends Widget
{
    protected static string $view = 'filament.widgets.activity-overview';

    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Karyawan yang Sudah Absensi Hari Ini';

    public function getHeading(): string
    {
        return static::$heading;
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
                    // Menggunakan fungsi yang telah diubah
                    'avatar_color_class' => $this->getAvatarColorClass($karyawan->nama_lengkap),
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
                if (strlen($initials) >= 2) break;
            }
        }

        return $initials ?: 'N/A';
    }

    // Mengubah fungsi untuk mengembalikan nama kelas CSS, bukan kelas Tailwind
    private function getAvatarColorClass(string $name): string
    {
        $colors = [
            'primary', 'success', 'purple', 'pink', 'indigo', 
            'danger', 'warning', 'teal', 'orange', 'cyan'
        ];
        $index = strlen($name) % count($colors);
        // Menghasilkan nama kelas seperti 'avatar-color-primary', 'avatar-color-success', dll.
        return 'avatar-color-' . $colors[$index];
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
}
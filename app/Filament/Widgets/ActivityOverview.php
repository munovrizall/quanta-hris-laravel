<?php

namespace App\Filament\Widgets;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Perusahaan;
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
        return Absensi::with(['karyawan.perusahaan'])
            ->whereDate('tanggal', Carbon::today())
            ->whereNotNull('waktu_masuk')
            ->orderBy('waktu_masuk', 'asc')
            ->get()
            ->map(function ($absensi) {
                $karyawan = $absensi->karyawan;
                $perusahaan = $karyawan->perusahaan;

                // Determine attendance status based on company operational hours
                $actualStatus = $this->determineAttendanceStatus($absensi, $perusahaan);

                return [
                    'nama_lengkap' => $karyawan->nama_lengkap,
                    'initial' => $this->getInitials($karyawan->nama_lengkap),
                    'waktu_masuk' => $absensi->waktu_masuk->format('H:i'),
                    'status' => $actualStatus,
                    'original_status' => $absensi->status_absensi,
                    'jam_masuk_perusahaan' => $perusahaan?->jam_masuk,
                    'avatar_color_class' => $this->getAvatarColorClass($karyawan->nama_lengkap),
                ];
            })
            ->filter(function ($karyawan) {
                // Only show employees who are present or late
                return in_array($karyawan['status'], ['Hadir', 'Terlambat']);
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

    private function getAvatarColorClass(string $name): string
    {
        $colors = [
            'primary',
            'success',
            'purple',
            'pink',
            'indigo',
            'danger',
            'warning',
            'teal',
            'orange',
            'cyan'
        ];
        $index = strlen($name) % count($colors);
        return 'avatar-color-' . $colors[$index];
    }

    public function getTotalAbsensiHariIni(): int
    {
        return Absensi::with(['karyawan.perusahaan'])
            ->whereDate('tanggal', Carbon::today())
            ->whereNotNull('waktu_masuk')
            ->get()
            ->filter(function ($absensi) {
                $perusahaan = $absensi->karyawan->perusahaan;
                $actualStatus = $this->determineAttendanceStatus($absensi, $perusahaan);
                return in_array($actualStatus, ['Hadir', 'Terlambat']);
            })
            ->count();
    }

    public function getTotalKaryawan(): int
    {
        return Karyawan::count();
    }

    /**
     * Get company operational hours info for display
     */
    public function getCompanyOperationalHours()
    {
        $companies = Perusahaan::select('perusahaan_id', 'nama_perusahaan', 'jam_masuk', 'jam_pulang')
            ->get()
            ->map(function ($company) {
                return [
                    'nama' => $company->nama_perusahaan,
                    'jam_masuk' => $company->jam_masuk ? Carbon::createFromFormat('H:i:s', $company->jam_masuk)->format('H:i') : 'N/A',
                    'jam_pulang' => $company->jam_pulang ? Carbon::createFromFormat('H:i:s', $company->jam_pulang)->format('H:i') : 'N/A',
                ];
            });

        return $companies;
    }

    /**
     * Determine actual attendance status based on company operational hours
     */
    private function determineAttendanceStatus($absensi, $perusahaan): string
    {
        if (!$perusahaan || !$perusahaan->jam_masuk || !$absensi->waktu_masuk) {
            return $absensi->status_absensi;
        }

        // Get company work start time
        $jamMasukPerusahaan = Carbon::createFromFormat('H:i:s', $perusahaan->jam_masuk);
        $waktuMasukKaryawan = Carbon::parse($absensi->waktu_masuk);

        // Compare only time (ignore date)
        $jamMasukPerusahaanTime = $jamMasukPerusahaan->format('H:i');
        $waktuMasukKaryawanTime = $waktuMasukKaryawan->format('H:i');

        // If employee arrives after company work hours, they are late
        if ($waktuMasukKaryawanTime > $jamMasukPerusahaanTime) {
            return 'Terlambat';
        }

        // If they arrive on time or early, they are present
        return 'Hadir';
    }

}
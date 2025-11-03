<?php

namespace App\Filament\Widgets;

use App\Models\Cuti;
use App\Models\Karyawan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Filament\Widgets\Widget;

class CutiTodayOverview extends Widget
{
    protected static string $view = 'filament.widgets.cuti-today-overview';

    protected static ?string $pollingInterval = '30s';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Karyawan Cuti Hari Ini';

    private ?Collection $cutiHariIniCache = null;

    public function getHeading(): string
    {
        return static::$heading;
    }

    public function getCutiHariIni(): Collection
    {
        if ($this->cutiHariIniCache !== null) {
            return $this->cutiHariIniCache;
        }

        $today = Carbon::today();

        $records = Cuti::with('karyawan')
            ->where('status_cuti', 'Disetujui')
            ->whereDate('tanggal_mulai', '<=', $today)
            ->where(function ($query) use ($today) {
                $query->whereDate('tanggal_selesai', '>=', $today)
                    ->orWhereNull('tanggal_selesai');
            })
            ->get()
            ->filter(fn (Cuti $cuti) => $cuti->karyawan !== null)
            ->map(function (Cuti $cuti) {
                $karyawan = $cuti->karyawan;

                return [
                    'nama_lengkap' => $karyawan->nama_lengkap,
                    'initial' => $this->getInitials($karyawan->nama_lengkap),
                    'periode' => $this->formatDateRange($cuti->tanggal_mulai, $cuti->tanggal_selesai),
                    'jenis' => $cuti->jenis_cuti,
                    'avatar_color_class' => $this->getAvatarColorClass($karyawan->nama_lengkap),
                ];
            })
            ->values();

        return $this->cutiHariIniCache = $records;
    }

    public function getTotalCutiHariIni(): int
    {
        return $this->getCutiHariIni()->count();
    }

    public function getTotalKaryawan(): int
    {
        return Karyawan::count();
    }

    private function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }

            $initials .= strtoupper($word[0]);

            if (strlen($initials) >= 2) {
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
            'cyan',
        ];

        $index = strlen($name) % count($colors);

        return 'avatar-color-' . $colors[$index];
    }

    private function formatDateRange(?Carbon $start, ?Carbon $end): string
    {
        if ($start === null) {
            return '-';
        }

        $startFormatted = $this->formatDate($start);

        if ($end === null || $end->isSameDay($start)) {
            return $startFormatted;
        }

        $endFormatted = $this->formatDate($end);

        return "{$startFormatted} - {$endFormatted}";
    }

    private function formatDate(Carbon $date): string
    {
        return $date->locale('id')->translatedFormat('d M Y');
    }
}

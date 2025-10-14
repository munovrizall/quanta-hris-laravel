<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanKinerjaResource\Pages;
use App\Models\Absensi;
use App\Utils\MonthHelper;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LaporanKinerjaResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Kinerja Karyawan';

    protected static ?string $pluralLabel = 'Laporan Kinerja Karyawan';

    protected static ?string $slug = 'laporan-kinerja';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationGroup = 'Laporan & Analitik';

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getMonthlyAttendanceQuery())
            ->columns([
                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode')
                    ->getStateUsing(fn(Model $record): string => MonthHelper::formatPeriod(
                        (int) $record->periode_bulan,
                        (int) $record->periode_tahun
                    ))
                    ->sortable(query: fn(Builder $query, string $direction): Builder => $query
                        ->orderBy('periode_tahun', $direction)
                        ->orderBy('periode_bulan', $direction)),

                Tables\Columns\TextColumn::make('total_absensi')
                    ->label('Total Absensi')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('on_time_rate')
                    ->label('Kehadiran Tepat Waktu')
                    ->formatStateUsing(fn(?float $state): string => $state !== null
                        ? number_format($state, 2) . '%'
                        : '0%')
                    ->color(fn(?float $state): string => $state >= 85 ? 'success' : ($state >= 70 ? 'warning' : 'danger'))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('late_count')
                    ->label('Keterlambatan')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('early_leave_count')
                    ->label('Pulang Cepat')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('periode_bulan')
                    ->label('Bulan')
                    ->options(MonthHelper::getMonthOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return filled($value)
                            ? $query->having('periode_bulan', '=', $value)
                            : $query;
                    }),
                Tables\Filters\SelectFilter::make('periode_tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $currentYear = Carbon::now()->year;
                        $years = [];
                        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return filled($value)
                            ? $query->having('periode_tahun', '=', $value)
                            : $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->url(fn(Model $record): string => static::getUrl('view', [
                        'tahun' => $record->periode_tahun,
                        'bulan' => $record->periode_bulan,
                    ])),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanKinerjas::route('/'),
            'view' => Pages\ViewLaporanKinerja::route('/{tahun}/{bulan}'),
        ];
    }

    protected static function getMonthlyAttendanceQuery(): Builder
    {
        return Absensi::query()
            ->selectRaw('MIN(absensi_id) as absensi_id')
            ->selectRaw('YEAR(tanggal) as periode_tahun')
            ->selectRaw('MONTH(tanggal) as periode_bulan')
            ->selectRaw('COUNT(*) as total_absensi')
            ->selectRaw('SUM(CASE WHEN status_masuk = "Tepat Waktu" THEN 1 ELSE 0 END) as on_time_count')
            ->selectRaw('SUM(CASE WHEN status_masuk = "Telat" THEN 1 ELSE 0 END) as late_count')
            ->selectRaw('SUM(CASE WHEN status_pulang = "Pulang Cepat" THEN 1 ELSE 0 END) as early_leave_count')
            ->selectRaw('ROUND(SUM(CASE WHEN status_masuk = "Tepat Waktu" THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0) * 100, 2) as on_time_rate')
            ->groupByRaw('YEAR(tanggal), MONTH(tanggal)')
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan');
    }
}

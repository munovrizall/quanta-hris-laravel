<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanKeuanganResource\Pages;
use App\Models\Penggajian;
use App\Utils\MonthHelper;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LaporanKeuanganResource extends Resource
{
    protected static ?string $model = Penggajian::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Laporan Keuangan Penggajian';

    protected static ?string $pluralLabel = 'Laporan Keuangan Penggajian';

    protected static ?string $slug = 'laporan-keuangan-penggajian';

    protected static ?string $navigationGroup = 'Laporan';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('menu_laporan_keuangan');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('view_any_laporan_keuangan');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getMonthlyPayrollQuery())
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

                Tables\Columns\TextColumn::make('total_gaji')
                    ->label('Total Biaya Gaji')
                    ->formatStateUsing(fn(?float $state): string => $state !== null
                        ? 'Rp ' . number_format($state, 0, ',', '.')
                        : 'Rp 0')
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('jumlah_karyawan')
                    ->label('Karyawan')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_potongan')
                    ->label('Total Potongan')
                    ->formatStateUsing(fn(?float $state): string => $state !== null
                        ? 'Rp ' . number_format($state, 0, ',', '.')
                        : 'Rp 0')
                    ->alignEnd(),
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
                        $currentYear = now()->year;
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
            'index' => Pages\ListLaporanKeuangans::route('/'),
            'view' => Pages\ViewLaporanKeuangan::route('/{tahun}/{bulan}'),
        ];
    }

    protected static function getMonthlyPayrollQuery(): Builder
    {
        return Penggajian::query()
            ->selectRaw('MIN(penggajian_id) as penggajian_id')
            ->selectRaw('periode_tahun')
            ->selectRaw('periode_bulan')
            ->selectRaw('COUNT(DISTINCT karyawan_id) as jumlah_karyawan')
            ->selectRaw('SUM(gaji_bersih) as total_gaji')
            ->selectRaw('SUM(total_potongan) as total_potongan')
            ->groupBy('periode_tahun', 'periode_bulan')
            ->orderByDesc('periode_tahun')
            ->orderByDesc('periode_bulan');
    }
}


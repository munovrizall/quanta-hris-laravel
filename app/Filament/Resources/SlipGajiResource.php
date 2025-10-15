<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SlipGajiResource\Pages;
use App\Models\Penggajian;
use App\Utils\MonthHelper;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Carbon\Carbon;

class SlipGajiResource extends Resource
{
    protected static ?string $model = Penggajian::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Slip Gaji';

    protected static ?string $slug = 'slip-gaji';

    protected static ?string $title = 'Slip Gaji';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'Manajemen Penggajian';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode')
                    ->getStateUsing(function ($record): string {
                        return MonthHelper::formatPeriod($record->periode_bulan, $record->periode_tahun);
                    })
                    ->searchable()
                    ->sortable(['periode_bulan', 'periode_tahun'])
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('status_penggajian')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Draf' => 'gray',
                        'Diajukan' => 'primary',
                        'Diverifikasi' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Draf' => 'heroicon-m-pencil-square',
                        'Diajukan' => 'heroicon-m-arrow-up-circle',
                        'Diverifikasi' => 'heroicon-m-check-circle',
                        'Disetujui' => 'heroicon-m-check-badge',
                        'Ditolak' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('detail_count')
                    ->label('Total Karyawan')
                    ->getStateUsing(function (Penggajian $record): string {
                        return (string) ($record->detail_count ?? 0);
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('total_penggajian')
                    ->label('Total Penggajian')
                    ->getStateUsing(function ($record): string {
                        $total = Penggajian::where('periode_bulan', $record->periode_bulan)
                            ->where('periode_tahun', $record->periode_tahun)
                            ->sum('gaji_bersih');
                        return 'Rp ' . number_format($total, 0, ',', '.');
                    })
                    ->alignRight()
                    ->color('success')
                    ->weight(FontWeight::Bold),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_penggajian')
                    ->label('Status')
                    ->options([
                        'Draf' => 'Draf',
                        'Diajukan' => 'Diajukan',
                        'Diverifikasi' => 'Diverifikasi',
                        'Disetujui' => 'Disetujui',
                        'Ditolak' => 'Ditolak',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('periode_bulan')
                    ->label('Bulan')
                    ->options(MonthHelper::getMonthOptions()),

                Tables\Filters\SelectFilter::make('periode_tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $currentYear = Carbon::now()->year;
                        $years = [];
                        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Slip Gaji')
                    ->url(
                        fn(Penggajian $record): string =>
                        static::getUrl('view', [
                            'tahun' => $record->periode_tahun,
                            'bulan' => $record->periode_bulan
                        ])
                    ),
            ])
            ->bulkActions([
                // Tidak ada bulk actions untuk slip gaji
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $model = static::getModel();

        return $model::query()
            ->selectRaw('MIN(penggajian_id) as penggajian_id')
            ->selectRaw('periode_bulan, periode_tahun')
            ->selectRaw('MAX(status_penggajian) as status_penggajian')
            ->selectRaw('MAX(catatan_penolakan_draf) as catatan_penolakan_draf')
            ->selectRaw('MAX(created_at) as created_at')
            ->selectRaw('MAX(updated_at) as updated_at')
            ->selectRaw('MAX(deleted_at) as deleted_at')
            ->selectRaw('COUNT(*) as detail_count')
            ->groupBy('periode_bulan', 'periode_tahun')
            ->whereIn('status_penggajian', ['Disetujui']); // Hanya tampilkan yang sudah disetujui
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSlipGajis::route('/'),
            'view' => Pages\ViewSlipGaji::route('/{tahun}/{bulan}'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['verifier', 'approver', 'processor']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['penggajian_id', 'verifier.nama_lengkap', 'approver.nama_lengkap'];
    }
}
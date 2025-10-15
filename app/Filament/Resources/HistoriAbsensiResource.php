<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistoriAbsensiResource\Pages;
use App\Filament\Resources\HistoriAbsensiResource\RelationManagers;
use App\Models\Karyawan;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HistoriAbsensiResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Histori Absensi';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $pluralModelLabel = 'Histori Absensi';

    protected static ?string $modelLabel = 'Histori Absensi';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('karyawan_id')
                    ->label('ID Karyawan')
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('absensi_count')
                    ->label('Total Absensi')
                    ->counts('absensi')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('lembur_count')
                    ->label('Total Lembur')
                    ->counts('lembur')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('cuti_count')
                    ->label('Total Cuti')
                    ->counts('cuti')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('izin_count')
                    ->label('Total Izin')
                    ->counts('izin')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Tidak ada data histori');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AbsensiRelationManager::class,
            RelationManagers\LemburRelationManager::class,
            RelationManagers\CutiRelationManager::class,
            RelationManagers\IzinRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['absensi', 'lembur', 'cuti', 'izin']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHistoriAbsensis::route('/'),
            'view' => Pages\ViewHistoriAbsensi::route('/{record}'),
        ];
    }
}

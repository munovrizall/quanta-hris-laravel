<?php

namespace App\Filament\Resources\HistoriAbsensiResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class IzinRelationManager extends RelationManager
{
    protected static string $relationship = 'izin';

    protected static ?string $recordTitleAttribute = 'jenis_izin';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jenis_izin')
                    ->label('Jenis Izin')
                    ->wrap(),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_izin')
                    ->label('Durasi')
                    ->suffix(' hari'),
                Tables\Columns\TextColumn::make('status_izin')
                    ->label('Status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Diajukan' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Diproses Pada')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Tidak ada data');
    }
}


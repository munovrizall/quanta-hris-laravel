<?php

namespace App\Filament\Resources\HistoriAbsensiResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CutiRelationManager extends RelationManager
{
    protected static string $relationship = 'cuti';

    protected static ?string $recordTitleAttribute = 'jenis_cuti';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('jenis_cuti')
                    ->label('Jenis Cuti')
                    ->wrap(),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_cuti')
                    ->label('Durasi')
                    ->suffix(' hari'),
                Tables\Columns\TextColumn::make('status_cuti')
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


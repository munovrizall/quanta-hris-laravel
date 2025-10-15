<?php

namespace App\Filament\Resources\HistoriAbsensiResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AbsensiRelationManager extends RelationManager
{
    protected static string $relationship = 'absensi';

    protected static ?string $recordTitleAttribute = 'tanggal';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_absensi')
                    ->label('Status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Tidak Tepat' => 'warning',
                        'Alfa' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('waktu_masuk')
                    ->label('Masuk')
                    ->dateTime('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_masuk')
                    ->label('Status Masuk')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Tepat Waktu' => 'success',
                        'Telat' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('waktu_pulang')
                    ->label('Pulang')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status_pulang')
                    ->label('Status Pulang')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        'Tepat Waktu' => 'success',
                        'Pulang Cepat' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('-'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Tidak ada data');
    }
}

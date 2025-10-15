<?php

namespace App\Filament\Resources\HistoriAbsensiResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class LemburRelationManager extends RelationManager
{
    protected static string $relationship = 'lembur';

    protected static ?string $recordTitleAttribute = 'tanggal_lembur';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_lembur')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_lembur')
                    ->label('Durasi')
                    ->formatStateUsing(fn(?string $state) => $state ? "{$state} jam" : '-'),
                Tables\Columns\TextColumn::make('total_insentif')
                    ->label('Total Insentif')
                    ->money('IDR', true),
                Tables\Columns\TextColumn::make('status_lembur')
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

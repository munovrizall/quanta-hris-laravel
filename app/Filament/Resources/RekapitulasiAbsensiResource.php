<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RekapitulasiAbsensiResource\Pages;
use App\Models\Absensi;
use Filament\Resources\Resource;

class RekapitulasiAbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Rekapitulasi Absensi';

    protected static ?string $pluralModelLabel = 'Rekapitulasi Absensi';

    protected static ?string $modelLabel = 'Rekapitulasi Absensi';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 6;

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form;
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ViewRekapitulasiAbsensi::route('/'),
        ];
    }
}

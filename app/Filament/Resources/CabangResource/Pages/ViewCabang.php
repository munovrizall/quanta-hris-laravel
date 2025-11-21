<?php

namespace App\Filament\Resources\CabangResource\Pages;

use App\Filament\Resources\CabangResource;
use Filament\Actions;
use App\Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewCabang extends ViewRecord
{
  protected static string $resource = CabangResource::class;

  protected static ?string $title = 'Detail Cabang';

  protected static ?string $breadcrumb = 'Detail';

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->label('Ubah'),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Informasi Cabang')
          ->schema([
            Infolists\Components\TextEntry::make('cabang_id')
              ->label('ID Cabang')
              ->badge()
              ->color('primary'),
            Infolists\Components\TextEntry::make('nama_cabang')
              ->label('Nama Cabang'),
            Infolists\Components\TextEntry::make('perusahaan.nama_perusahaan')
              ->label('Perusahaan')
              ->badge()
              ->color('success'),
            Infolists\Components\TextEntry::make('alamat')
              ->label('Alamat')
              ->columnSpanFull(),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Lokasi & Koordinat')
          ->schema([
            Infolists\Components\TextEntry::make('latitude')
              ->label('Latitude')
              ->icon('heroicon-m-map-pin'),
            Infolists\Components\TextEntry::make('longitude')
              ->label('Longitude')
              ->icon('heroicon-m-map-pin'),
            Infolists\Components\TextEntry::make('radius_lokasi')
              ->label('Radius Lokasi')
              ->suffix(' meter')
              ->badge()
              ->color('info')
              ->icon('heroicon-m-globe-alt'),
            Infolists\Components\TextEntry::make('maps_link')
              ->label('Google Maps')
              ->getStateUsing(fn($record) => "https://maps.google.com/?q={$record->latitude},{$record->longitude}")
              ->url(fn($record) => "https://maps.google.com/?q={$record->latitude},{$record->longitude}")
              ->openUrlInNewTab()
              ->icon('heroicon-m-map'),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Statistik')
          ->schema([
            Infolists\Components\TextEntry::make('absensi_count')
              ->label('Total Absensi')
              ->getStateUsing(fn($record) => $record->absensi()->count())
              ->badge()
              ->color('warning'),
            Infolists\Components\TextEntry::make('created_at')
              ->label('Dibuat')
              ->dateTime()
              ->icon('heroicon-m-calendar'),
          ])
          ->columns(2),
      ]);
  }
}


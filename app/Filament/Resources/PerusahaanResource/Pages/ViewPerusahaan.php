<?php

namespace App\Filament\Resources\PerusahaanResource\Pages;

use App\Filament\Resources\PerusahaanResource;
use Filament\Actions;
use App\Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewPerusahaan extends ViewRecord
{
  protected static string $resource = PerusahaanResource::class;

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
        Infolists\Components\Section::make('Informasi Perusahaan')
          ->schema([
            Infolists\Components\TextEntry::make('perusahaan_id')
              ->label('ID Perusahaan')
              ->badge()
              ->color('primary'),
            Infolists\Components\TextEntry::make('nama_perusahaan')
              ->label('Nama Perusahaan'),
            Infolists\Components\TextEntry::make('email')
              ->label('Email')
              ->icon('heroicon-m-envelope'),
            Infolists\Components\TextEntry::make('nomor_telepon')
              ->label('Nomor Telepon')
              ->icon('heroicon-m-phone'),
            Infolists\Components\TextEntry::make('jam_masuk')
              ->label('Jam Masuk Kerja')
              ->icon('heroicon-m-clock'),
            Infolists\Components\TextEntry::make('jam_pulang')
              ->label('Jam Pulang Kerja')
              ->icon('heroicon-m-clock'),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Statistik')
          ->schema([
            Infolists\Components\TextEntry::make('karyawan_count')
              ->label('Total Karyawan')
              ->getStateUsing(fn($record) => $record->karyawan()->count())
              ->badge()
              ->color('success'),
            Infolists\Components\TextEntry::make('cabang_count')
              ->label('Total Cabang')
              ->getStateUsing(fn($record) => $record->cabang()->count())
              ->badge()
              ->color('info'),
          ])
          ->columns(2),
      ]);
  }
}


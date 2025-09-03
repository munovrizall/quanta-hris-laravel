<?php

namespace App\Filament\Resources\AbsensiResource\Pages;

use App\Filament\Resources\AbsensiResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewAbsensi extends ViewRecord
{
  protected static string $resource = AbsensiResource::class;

  protected static ?string $title = 'Detail Absensi';

  protected static ?string $breadcrumb = 'Detail';

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make()
        ->label('Ubah'),
      Actions\DeleteAction::make()
        ->label('Hapus')
        ->requiresConfirmation()
        ->modalHeading('Hapus Absensi')
        ->modalDescription('Apakah Anda yakin ingin menghapus data absensi ini?')
        ->modalSubmitActionLabel('Ya, hapus'),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Informasi Absensi')
          ->schema([
            Infolists\Components\TextEntry::make('absensi_id')
              ->label('ID Absensi')
              ->badge()
              ->color('primary'),
            Infolists\Components\TextEntry::make('karyawan.nama_lengkap')
              ->label('Karyawan'),
            Infolists\Components\TextEntry::make('cabang.nama_cabang')
              ->label('Cabang')
              ->badge()
              ->color('info'),
            Infolists\Components\TextEntry::make('tanggal')
              ->label('Tanggal')
              ->date()
              ->icon('heroicon-m-calendar'),
            Infolists\Components\TextEntry::make('status_absensi')
              ->label('Status Absensi')
              ->badge()
              ->color(fn(?string $state): string => match ($state) {
                'Hadir' => 'success',
                'Tidak Tepat' => 'warning',
                'Alfa' => 'danger',
                default => 'gray',
              }),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Waktu Masuk')
          ->schema([
            Infolists\Components\TextEntry::make('waktu_masuk')
              ->label('Waktu Masuk')
              ->dateTime()
              ->icon('heroicon-m-clock'),
            Infolists\Components\TextEntry::make('status_masuk')
              ->label('Status Masuk')
              ->badge()
              ->color(fn(?string $state): string => match ($state) {
                'Tepat Waktu' => 'success',
                'Telat' => 'warning',
                default => 'gray',
              }),
            Infolists\Components\TextEntry::make('durasi_telat')
              ->label('Durasi Telat')
              ->formatStateUsing(fn($state) => $state ?: '-'),
            Infolists\Components\TextEntry::make('koordinat_masuk')
              ->label('Koordinat Masuk')
              ->icon('heroicon-m-map-pin'),
            Infolists\Components\ImageEntry::make('foto_masuk')
              ->label('Foto Masuk')
              ->size(200),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Waktu Pulang')
          ->schema([
            Infolists\Components\TextEntry::make('waktu_pulang')
              ->label('Waktu Pulang')
              ->dateTime()
              ->placeholder('-')
              ->icon('heroicon-m-clock'),
            Infolists\Components\TextEntry::make('status_pulang')
              ->label('Status Pulang')
              ->badge()
              ->color(fn(?string $state): string => match ($state) {
                'Tepat Waktu' => 'success',
                'Pulang Cepat' => 'warning',
                default => 'gray',
              })
              ->placeholder('-'),
            Infolists\Components\TextEntry::make('durasi_pulang_cepat')
              ->label('Durasi Pulang Cepat')
              ->formatStateUsing(fn($state) => $state ?: '-'),
            Infolists\Components\TextEntry::make('koordinat_pulang')
              ->label('Koordinat Pulang')
              ->icon('heroicon-m-map-pin')
              ->placeholder('-'),
            Infolists\Components\ImageEntry::make('foto_pulang')
              ->label('Foto Pulang')
              ->size(200)
              ->placeholder('Belum ada foto'),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Informasi Sistem')
          ->schema([
            Infolists\Components\TextEntry::make('created_at')
              ->label('Dibuat')
              ->dateTime(),
            Infolists\Components\TextEntry::make('updated_at')
              ->label('Diubah')
              ->dateTime(),
          ])
          ->columns(2)
          ->collapsed(),
      ]);
  }
}
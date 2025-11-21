<?php

namespace App\Filament\Resources\HistoriAbsensiResource\Pages;

use App\Filament\Resources\HistoriAbsensiResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Filament\Resources\Pages\ViewRecord;

class ViewHistoriAbsensi extends ViewRecord
{
    protected static string $resource = HistoriAbsensiResource::class;

    protected static ?string $title = 'Detail Histori Absensi';

    protected static ?string $breadcrumb = 'Detail';

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Karyawan')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->weight('bold'),
                        Infolists\Components\TextEntry::make('nik')
                            ->label('NIK')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('departemen')
                            ->label('Departemen')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('jabatan')
                            ->label('Jabatan')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('nomor_telepon')
                            ->label('Nomor Telepon')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Ringkasan Histori')
                    ->schema([
                        Infolists\Components\TextEntry::make('absensi_count')
                            ->label('Total Absensi')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('lembur_count')
                            ->label('Total Lembur')
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('cuti_count')
                            ->label('Total Cuti')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('izin_count')
                            ->label('Total Izin')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y H:i')
                            ->columnSpan(2),
                    ])
                    ->columns(2),
            ]);
    }
}


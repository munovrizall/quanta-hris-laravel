<?php

namespace App\Filament\Resources\KaryawanResource\Pages;

use App\Filament\Resources\KaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewKaryawan extends ViewRecord
{
    protected static string $resource = KaryawanResource::class;

    protected static ?string $title = 'Detail Karyawan';

    protected static ?string $breadcrumb = 'Detail';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Personal')
                    ->schema([
                        Infolists\Components\TextEntry::make('karyawan_id')
                            ->label('ID Karyawan')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('nik')
                            ->label('NIK')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('nama_lengkap')
                            ->label('Nama Lengkap'),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->date()
                            ->icon('heroicon-m-calendar'),
                        Infolists\Components\TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'Laki-laki' => 'blue',
                                'Perempuan' => 'pink',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('alamat')
                            ->label('Alamat')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('nomor_telepon')
                            ->label('Nomor Telepon')
                            ->icon('heroicon-m-phone')
                            ->default('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Informasi Kepegawaian')
                    ->schema([
                        Infolists\Components\TextEntry::make('perusahaan.nama_perusahaan')
                            ->label('Perusahaan')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('role.name')
                            ->label('Role')
                            ->badge()
                            ->color(fn($record) => $record->role_color)
                            ->default('-'),
                        Infolists\Components\TextEntry::make('jabatan')
                            ->label('Jabatan')
                            ->default('-'),
                        Infolists\Components\TextEntry::make('departemen')
                            ->label('Departemen')
                            ->default('-'),
                        Infolists\Components\TextEntry::make('status_kepegawaian')
                            ->label('Status Kepegawaian')
                            ->badge()
                            ->color(fn(?string $state): string => match ($state) {
                                'Tetap' => 'success',
                                'Kontrak' => 'warning',
                                'Magang' => 'info',
                                'Freelance' => 'gray',
                                default => 'gray',
                            })
                            ->default('-'),
                        Infolists\Components\TextEntry::make('tanggal_mulai_bekerja')
                            ->label('Tanggal Mulai Bekerja')
                            ->date()
                            ->icon('heroicon-m-calendar')
                            ->default('-'),
                        Infolists\Components\TextEntry::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->money('IDR')
                            ->default('-'),
                        Infolists\Components\TextEntry::make('golonganPtkp.nama_golongan_ptkp')
                            ->label('Golongan PTKP')
                            ->badge()
                            ->color('info')
                            ->default('-'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Informasi Rekening & BPJS')
                    ->schema([
                        Infolists\Components\TextEntry::make('nomor_rekening')
                            ->label('Nomor Rekening')
                            ->default('-'),
                        Infolists\Components\TextEntry::make('nama_pemilik_rekening')
                            ->label('Nama Pemilik Rekening')
                            ->default('-'),
                        Infolists\Components\TextEntry::make('nomor_bpjs_kesehatan')
                            ->label('Nomor BPJS Kesehatan')
                            ->default('-'),
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
                            ->label('Bergabung')
                            ->dateTime()
                            ->icon('heroicon-m-calendar'),
                    ])
                    ->columns(2),
            ]);
    }
}
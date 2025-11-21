<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IzinResource\Pages;
use App\Filament\Resources\IzinResource\RelationManagers;
use App\Models\Izin;
use App\Models\Karyawan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class IzinResource extends Resource
{
    protected static ?string $model = Izin::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Kelola Izin';

    protected static ?string $pluralModelLabel = 'Izin';

    protected static ?string $modelLabel = 'Izin';

    protected static ?string $navigationGroup = 'Manajemen Absensi';

    protected static ?int $navigationSort = 4;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Izin')
                    ->schema([
                        Forms\Components\Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->options(Karyawan::all()->pluck('nama_lengkap', 'karyawan_id'))
                            ->searchable()
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Select::make('jenis_izin')
                            ->label('Jenis Izin')
                            ->options([
                                'Izin Keperluan Keluarga' => 'Izin Keperluan Keluarga',
                                'Izin Keperluan Pribadi' => 'Izin Keperluan Pribadi',
                                'Izin Sakit' => 'Izin Sakit',
                                'Izin Cuti Mendadak' => 'Izin Cuti Mendadak',
                                'Izin Keperluan Dinas' => 'Izin Keperluan Dinas',
                                'Izin Menghadiri Acara' => 'Izin Menghadiri Acara',
                                'Izin Datang Terlambat' => 'Izin Datang Terlambat',
                                'Izin Pulang Lebih Awal' => 'Izin Pulang Lebih Awal',
                                'Izin Tidak Masuk' => 'Izin Tidak Masuk',
                                'Izin Keluar Kantor' => 'Izin Keluar Kantor',
                            ])
                            ->required()
                            ->searchable()
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('tanggal_mulai')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                $tanggalSelesai = $get('tanggal_selesai');
                                if ($state && $tanggalSelesai && $state > $tanggalSelesai) {
                                    $set('tanggal_selesai', $state);
                                }
                            })
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('tanggal_selesai')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state, callable $get) {
                                $tanggalMulai = $get('tanggal_mulai');
                                if ($state && $tanggalMulai && $state < $tanggalMulai) {
                                    $set('tanggal_mulai', $state);
                                }
                            })
                            ->columnSpan(1),
                        Forms\Components\Placeholder::make('durasi_info')
                            ->label('Durasi Izin')
                            ->content(function (callable $get) {
                                $tanggalMulai = $get('tanggal_mulai');
                                $tanggalSelesai = $get('tanggal_selesai');

                                if ($tanggalMulai && $tanggalSelesai) {
                                    $durasi = \Carbon\Carbon::parse($tanggalMulai)
                                        ->diffInDays(\Carbon\Carbon::parse($tanggalSelesai)) + 1;
                                    return $durasi . ' hari';
                                }

                                return 'Pilih tanggal mulai dan selesai';
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Detail Izin')
                    ->schema([
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->required()
                            ->rows(4)
                            ->placeholder('Jelaskan alasan izin secara detail...'),
                        Forms\Components\FileUpload::make('dokumen_pendukung')
                            ->label('Dokumen Pendukung')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(5120) // 5MB
                            ->helperText('Upload dokumen pendukung seperti surat keterangan, undangan, dll (jika ada)'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Status Persetujuan')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status_izin')
                                    ->label('Status Izin')
                                    ->options([
                                        'Diajukan' => 'Diajukan',
                                        'Disetujui' => 'Disetujui',
                                        'Ditolak' => 'Ditolak',
                                    ])
                                    ->default('Diajukan')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state === 'Disetujui') {
                                            $set('processed_at', now());
                                            $set('alasan_penolakan', null);
                                        } elseif ($state === 'Ditolak') {
                                            $set('processed_at', now());
                                        } else {
                                            $set('processed_at', null);
                                            $set('alasan_penolakan', null);
                                        }
                                    })
                                    ->columnSpan(1),
                                Forms\Components\Select::make('approver_id')
                                    ->label('Disetujui Oleh')
                                    ->options(function () {
                                        return Karyawan::whereHas('role', function ($query) {
                                            $query->whereIn('name', ['Admin', 'CEO', 'Manager HRD']);
                                        })->pluck('nama_lengkap', 'karyawan_id');
                                    })
                                    ->searchable()
                                    ->visible(fn(callable $get) => in_array($get('status_izin'), ['Disetujui', 'Ditolak']))
                                    ->required(fn(callable $get) => in_array($get('status_izin'), ['Disetujui', 'Ditolak']))
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Waktu Persetujuan')
                            ->visible(fn(callable $get) => in_array($get('status_izin'), ['Disetujui', 'Ditolak']))
                            ->required(fn(callable $get) => in_array($get('status_izin'), ['Disetujui', 'Ditolak']))
                            ->helperText('Otomatis terisi saat mengubah status. Anda dapat mengubahnya secara manual jika diperlukan.')
                            ->seconds(false)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->rows(3)
                            ->visible(fn(callable $get) => $get('status_izin') === 'Ditolak')
                            ->required(fn(callable $get) => $get('status_izin') === 'Ditolak')
                            ->helperText('Berikan alasan yang jelas mengapa izin ditolak.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('izin_id')
                    ->label('ID Izin')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('karyawan.nama_lengkap')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis_izin')
                    ->label('Jenis Izin')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->wrap(),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_izin')
                    ->label('Durasi')
                    ->formatStateUsing(fn($record) => $record->durasi_izin . ' hari')
                    ->badge()
                    ->color('secondary'),
                Tables\Columns\TextColumn::make('status_izin')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Diajukan' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('approver.nama_lengkap')
                    ->label('Disetujui Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Waktu Persetujuan')
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status_izin')
                    ->label('Status Izin')
                    ->options([
                        'Diajukan' => 'Diajukan',
                        'Disetujui' => 'Disetujui',
                        'Ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('jenis_izin')
                    ->label('Jenis Izin')
                    ->options([
                        'Izin Keperluan Keluarga' => 'Izin Keperluan Keluarga',
                        'Izin Keperluan Pribadi' => 'Izin Keperluan Pribadi',
                        'Izin Sakit' => 'Izin Sakit',
                        'Izin Cuti Mendadak' => 'Izin Cuti Mendadak',
                        'Izin Keperluan Dinas' => 'Izin Keperluan Dinas',
                        'Izin Menghadiri Acara' => 'Izin Menghadiri Acara',
                        'Izin Datang Terlambat' => 'Izin Datang Terlambat',
                        'Izin Pulang Lebih Awal' => 'Izin Pulang Lebih Awal',
                        'Izin Tidak Masuk' => 'Izin Tidak Masuk',
                        'Izin Keluar Kantor' => 'Izin Keluar Kantor',
                    ]),
                Tables\Filters\Filter::make('tanggal_izin')
                    ->form([
                        Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_mulai', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_selesai', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([])
                    ->visible(fn(Izin $record): bool => $record->status_izin === 'Diajukan')
                    ->modalHeading('Yakin Setujui Izin?')
                    ->modalDescription(
                        fn(Izin $record): string =>
                            "Apakah Anda yakin ingin menyetujui pengajuan izin untuk {$record->karyawan->nama_lengkap} pada {$record->tanggal_mulai->translatedFormat('d F Y')} sampai {$record->tanggal_selesai->translatedFormat('d F Y')}?"
                    )
                    ->modalSubmitActionLabel('Ya')
                    ->action(function (Izin $record): void {
                        try {
                            $record->update([
                                'status_izin' => 'Disetujui',
                                'approver_id' => Auth::user()?->karyawan_id ?? 'SYSTEM',
                                'processed_at' => now(),
                                'alasan_penolakan' => null,
                            ]);

                            Notification::make()
                                ->title('Izin Berhasil Disetujui')
                                ->body("Pengajuan izin untuk {$record->karyawan->nama_lengkap} telah disetujui.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Terjadi kesalahan saat menyetujui izin: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Izin $record): bool => $record->status_izin === 'Diajukan')
                    ->modalHeading('Tolak Pengajuan Izin')
                    ->modalDescription(
                        fn(Izin $record): string =>
                            "Apakah Anda yakin ingin menolak pengajuan izin untuk {$record->karyawan->nama_lengkap} pada {$record->tanggal_mulai->translatedFormat('d F Y')} sampai {$record->tanggal_selesai->translatedFormat('d F Y')}?"
                    )
                    ->modalSubmitActionLabel('Simpan')
                    ->form([
                        Forms\Components\Textarea::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->helperText('Berikan alasan yang jelas untuk penolakan ini.'),
                    ])
                    ->action(function (Izin $record, array $data): void {
                        try {
                            $record->update([
                                'status_izin' => 'Ditolak',
                                'alasan_penolakan' => $data['alasan_penolakan'],
                                'approver_id' => Auth::user()?->karyawan_id ?? 'SYSTEM',
                                'processed_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Izin Berhasil Ditolak')
                                ->body("Pengajuan izin untuk {$record->karyawan->nama_lengkap} telah ditolak.")
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Terjadi kesalahan saat menolak izin: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan'),
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Pulihkan Terpilih'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen Terpilih'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIzins::route('/'),
            'create' => Pages\CreateIzin::route('/create'),
            'view' => Pages\ViewIzin::route('/{record}'),
            'edit' => Pages\EditIzin::route('/{record}/edit'),
        ];
    }
}


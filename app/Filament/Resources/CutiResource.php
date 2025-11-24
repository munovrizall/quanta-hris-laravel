<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CutiResource\Pages;
use App\Filament\Resources\CutiResource\RelationManagers;
use App\Models\Cuti;
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
use Illuminate\Support\Facades\DB;

class CutiResource extends Resource
{
    protected static ?string $model = Cuti::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Kelola Cuti';

    protected static ?string $navigationGroup = 'Manajemen Absensi';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Cuti')
                    ->schema([
                        Forms\Components\Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->options(Karyawan::all()->pluck('nama_lengkap', 'karyawan_id'))
                            ->searchable()
                            ->required()
                            ->columnSpan(2),
                        Forms\Components\Select::make('jenis_cuti')
                            ->label('Jenis Cuti')
                            ->options([
                                'Cuti Tahunan' => 'Cuti Tahunan',
                                'Cuti Sakit' => 'Cuti Sakit',
                                'Cuti Melahirkan' => 'Cuti Melahirkan',
                                'Cuti Menikah' => 'Cuti Menikah',
                                'Cuti Besar' => 'Cuti Besar',
                                'Cuti Khusus' => 'Cuti Khusus',
                                'Cuti Tanpa Gaji' => 'Cuti Tanpa Gaji',
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
                            ->label('Durasi Cuti')
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

                Forms\Components\Section::make('Detail Cuti')
                    ->schema([
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->required()
                            ->rows(4)
                            ->placeholder('Jelaskan alasan cuti...'),
                        Forms\Components\FileUpload::make('dokumen_pendukung')
                            ->label('Dokumen Pendukung')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(5120) // 5MB
                            ->helperText('Upload dokumen pendukung seperti surat dokter, undangan, dll (jika ada)'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Status Persetujuan')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status_cuti')
                                    ->label('Status Cuti')
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
                                    ->visible(fn(callable $get) => in_array($get('status_cuti'), ['Disetujui', 'Ditolak']))
                                    ->required(fn(callable $get) => in_array($get('status_cuti'), ['Disetujui', 'Ditolak']))
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Waktu Persetujuan')
                            ->visible(fn(callable $get) => in_array($get('status_cuti'), ['Disetujui', 'Ditolak']))
                            ->required(fn(callable $get) => in_array($get('status_cuti'), ['Disetujui', 'Ditolak']))
                            ->helperText('Otomatis terisi saat mengubah status. Anda dapat mengubahnya secara manual jika diperlukan.')
                            ->seconds(false)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->rows(3)
                            ->visible(fn(callable $get) => $get('status_cuti') === 'Ditolak')
                            ->required(fn(callable $get) => $get('status_cuti') === 'Ditolak')
                            ->helperText('Berikan alasan yang jelas mengapa cuti ditolak.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cuti_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('karyawan.nama_lengkap')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis_cuti')
                    ->label('Jenis Cuti')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_cuti')
                    ->label('Durasi')
                    ->formatStateUsing(fn($record) => $record->durasi_cuti . ' hari')
                    ->badge()
                    ->color('secondary'),
                Tables\Columns\TextColumn::make('status_cuti')
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
                Tables\Filters\SelectFilter::make('status_cuti')
                    ->label('Status Cuti')
                    ->options([
                        'Diajukan' => 'Diajukan',
                        'Disetujui' => 'Disetujui',
                        'Ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('jenis_cuti')
                    ->label('Jenis Cuti')
                    ->options([
                        'Cuti Tahunan' => 'Cuti Tahunan',
                        'Cuti Sakit' => 'Cuti Sakit',
                        'Cuti Melahirkan' => 'Cuti Melahirkan',
                        'Cuti Menikah' => 'Cuti Menikah',
                        'Cuti Besar' => 'Cuti Besar',
                        'Cuti Khusus' => 'Cuti Khusus',
                        'Cuti Tanpa Gaji' => 'Cuti Tanpa Gaji',
                    ]),
                Tables\Filters\Filter::make('tanggal_cuti')
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
                    ->visible(fn(Cuti $record): bool => $record->status_cuti === 'Diajukan')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pengajuan Cuti')
                    ->modalDescription(
                        fn(Cuti $record): string =>
                            "Apakah Anda yakin ingin menyetujui pengajuan cuti untuk {$record->karyawan->nama_lengkap} pada {$record->tanggal_mulai->translatedFormat('d F Y')} sampai {$record->tanggal_selesai->translatedFormat('d F Y')}?"
                    )
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->action(function (Cuti $record): void {
                        try {
                            DB::transaction(function () use ($record) {
                                $record->update([
                                    'status_cuti' => 'Disetujui',
                                    'approver_id' => Auth::user()?->karyawan_id ?? 'SYSTEM',
                                    'processed_at' => now(),
                                    'alasan_penolakan' => null,
                                ]);

                                $durasi = (int) ($record->durasi_cuti ?? 0);
                                if ($durasi <= 0) {
                                    return;
                                }

                                $karyawan = $record->karyawan()->lockForUpdate()->first();
                                if (!$karyawan) {
                                    return;
                                }

                                $kuotaSaatIni = (int) ($karyawan->kuota_cuti_tahunan ?? 0);
                                $karyawan->kuota_cuti_tahunan = max(0, $kuotaSaatIni - $durasi);
                                $karyawan->save();
                            });

                            Notification::make()
                                ->title('Cuti Berhasil Disetujui')
                                ->body("Pengajuan cuti untuk {$record->karyawan->nama_lengkap} telah disetujui.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Terjadi kesalahan saat menyetujui cuti: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Cuti $record): bool => $record->status_cuti === 'Diajukan')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pengajuan Cuti')
                    ->modalDescription(
                        fn(Cuti $record): string =>
                            "Apakah Anda yakin ingin menolak pengajuan cuti untuk {$record->karyawan->nama_lengkap} pada {$record->tanggal_mulai->translatedFormat('d F Y')} sampai {$record->tanggal_selesai->translatedFormat('d F Y')}?"
                    )
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->form([
                        Forms\Components\Textarea::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->helperText('Berikan alasan yang jelas untuk penolakan ini.'),
                    ])
                    ->action(function (Cuti $record, array $data): void {
                        try {
                            $record->update([
                                'status_cuti' => 'Ditolak',
                                'alasan_penolakan' => $data['alasan_penolakan'],
                                'approver_id' => Auth::user()?->karyawan_id ?? 'SYSTEM',
                                'processed_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Cuti Berhasil Ditolak')
                                ->body("Pengajuan cuti untuk {$record->karyawan->nama_lengkap} telah ditolak.")
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Terjadi kesalahan saat menolak cuti: ' . $e->getMessage())
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
            'index' => Pages\ListCutis::route('/'),
            'create' => Pages\CreateCuti::route('/create'),
            'view' => Pages\ViewCuti::route('/{record}'),
            'edit' => Pages\EditCuti::route('/{record}/edit'),
        ];
    }
}

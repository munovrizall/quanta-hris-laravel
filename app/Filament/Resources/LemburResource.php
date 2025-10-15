<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LemburResource\Pages;
use App\Filament\Resources\LemburResource\RelationManagers;
use App\Models\Lembur;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Services\LemburService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class LemburResource extends Resource
{
    protected static ?string $model = Lembur::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Kelola Lembur';

    protected static ?string $pluralModelLabel = 'Lembur';

    protected static ?string $modelLabel = 'Lembur';

    protected static ?string $navigationGroup = 'Manajemen Absensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Lembur')
                    ->schema([
                        Forms\Components\Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->options(Karyawan::all()->pluck('nama_lengkap', 'karyawan_id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Reset absensi ketika karyawan berubah
                                $set('absensi_id', null);
                            }),
                        Forms\Components\Select::make('absensi_id')
                            ->label('Data Absensi')
                            ->options(function (callable $get) {
                                $karyawanId = $get('karyawan_id');
                                if (!$karyawanId)
                                    return [];

                                return Absensi::where('karyawan_id', $karyawanId)
                                    ->whereDate('tanggal', '>=', now()->subDays(30)) // Filter 30 hari terakhir
                                    ->get()
                                    ->mapWithKeys(function ($absensi) {
                                        $tanggal = $absensi->tanggal ? $absensi->tanggal->format('Y-m-d') : 'N/A';
                                        $status = $absensi->status_absensi ?? 'N/A';
                                        $label = $absensi->absensi_id . ' - ' . $tanggal . ' (' . $status . ')';
                                        return [$absensi->absensi_id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->placeholder('Pilih karyawan terlebih dahulu'),
                        Forms\Components\DatePicker::make('tanggal_lembur')
                            ->label('Tanggal Lembur')
                            ->required()
                            ->default(now()),
                        Forms\Components\TimePicker::make('durasi_lembur')
                            ->label('Durasi Lembur')
                            ->required()
                            ->seconds(false),
                        Forms\Components\TextInput::make('total_insentif')
                            ->label('Total Insentif (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('Akan dihitung otomatis saat disetujui')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : null),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Detail Pekerjaan')
                    ->schema([
                        Forms\Components\Textarea::make('deskripsi_pekerjaan')
                            ->label('Deskripsi Pekerjaan')
                            ->required()
                            ->rows(4),
                        Forms\Components\FileUpload::make('dokumen_pendukung')
                            ->label('Dokumen Pendukung')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(5120), // 5MB
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Status Persetujuan')
                    ->schema([
                        Forms\Components\Select::make('status_lembur')
                            ->label('Status Lembur')
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
                                    $set('processed_at', null);
                                } else {
                                    $set('processed_at', null);
                                    $set('alasan_penolakan', null);
                                }
                            }),
                        Forms\Components\Select::make('approver_id')
                            ->label('Disetujui Oleh')
                            ->options(function () {
                                return Karyawan::whereHas('role', function ($query) {
                                    $query->whereIn('name', ['Admin', 'CEO', 'Manager HRD']);
                                })->pluck('nama_lengkap', 'karyawan_id');
                            })
                            ->searchable()
                            ->visible(fn(callable $get) => in_array($get('status_lembur'), ['Disetujui', 'Ditolak'])),
                        Forms\Components\DateTimePicker::make('processed_at')
                            ->label('Waktu Persetujuan')
                            ->visible(fn(callable $get) => in_array($get('status_lembur'), ['Disetujui', 'Ditolak']))
                            ->required(fn(callable $get) => in_array($get('status_lembur'), ['Disetujui', 'Ditolak']))
                            ->helperText('Otomatis terisi saat mengubah status. Anda dapat mengubahnya secara manual.')
                            ->seconds(false)
                            ->default(fn(callable $get) => $get('status_lembur') !== 'Diajukan' ? now() : null),
                        Forms\Components\Textarea::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->rows(3)
                            ->visible(fn(callable $get) => $get('status_lembur') === 'Ditolak')
                            ->required(fn(callable $get) => $get('status_lembur') === 'Ditolak'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lembur_id')
                    ->label('ID Lembur')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('karyawan.nama_lengkap')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_lembur')
                    ->label('Tanggal Lembur')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_lembur')
                    ->label('Durasi')
                    ->formatStateUsing(function ($state) {
                        return $state ? date('H:i', strtotime($state)) . ' jam' : '-';
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('total_insentif')
                    ->label('Insentif')
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->badge()
                    ->color('success')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status_lembur')
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
                Tables\Filters\SelectFilter::make('status_lembur')
                    ->label('Status Lembur')
                    ->options([
                        'Diajukan' => 'Diajukan',
                        'Disetujui' => 'Disetujui',
                        'Ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\Filter::make('tanggal_lembur')
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
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_lembur', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_lembur', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Lembur $record): bool => $record->status_lembur === 'Diajukan')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pengajuan Lembur')
                    ->modalDescription(
                        fn(Lembur $record): string =>
                        "Apakah Anda yakin ingin menyetujui pengajuan lembur untuk {$record->karyawan->nama_lengkap} pada tanggal {$record->tanggal_lembur->format('d F Y')}?"
                    )
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->form([
                        Forms\Components\Placeholder::make('preview')
                            ->label('Preview Perhitungan Insentif')
                            ->content(function (Lembur $record): string {
                                $lemburService = new LemburService();
                                $insentif = $lemburService->calculateInsentifFromLembur($record);
                                return "Insentif yang akan diberikan: " . $lemburService->formatRupiah($insentif);
                            }),
                    ])
                    ->action(function (Lembur $record, array $data): void {
                        try {
                            $lemburService = new LemburService();
                            $insentif = $lemburService->calculateInsentifFromLembur($record);

                            // Update status dan data persetujuan
                            $record->update([
                                'status_lembur' => 'Disetujui',
                                'total_insentif' => $insentif,
                                'approver_id' => Auth::user()->karyawan_id ?? 'SYSTEM',
                                'processed_at' => now(),
                                'alasan_penolakan' => null,
                            ]);

                            Notification::make()
                                ->title('Lembur Berhasil Disetujui')
                                ->body("Pengajuan lembur untuk {$record->karyawan->nama_lengkap} telah disetujui dengan insentif " . $lemburService->formatRupiah($insentif))
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Terjadi kesalahan saat menyetujui lembur: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }

                    }),

                // ACTION BARU: Tolak Lembur
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Lembur $record): bool => $record->status_lembur === 'Diajukan')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pengajuan Lembur')
                    ->modalDescription(
                        fn(Lembur $record): string =>
                        "Apakah Anda yakin ingin menolak pengajuan lembur untuk {$record->karyawan->nama_lengkap} pada tanggal {$record->tanggal_lembur->format('d F Y')}?"
                    )
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->form([
                        Forms\Components\Textarea::make('alasan_penolakan')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->rows(3)
                            ->helperText('Berikan alasan yang jelas untuk penolakan ini.'),
                    ])
                    ->action(function (Lembur $record, array $data): void {
                        try {
                            // Update status dan data penolakan
                            $record->update([
                                'status_lembur' => 'Ditolak',
                                'alasan_penolakan' => $data['alasan_penolakan'],
                                'approver_id' => Auth::user()->karyawan_id ?? 'SYSTEM',
                                'processed_at' => now(),
                                'total_insentif' => null, // Clear insentif jika ada
                            ]);

                            Notification::make()
                                ->title('Lembur Berhasil Ditolak')
                                ->body("Pengajuan lembur untuk {$record->karyawan->nama_lengkap} telah ditolak.")
                                ->warning()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error')
                                ->body('Terjadi kesalahan saat menolak lembur: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // ACTION: Reset Status (untuk admin)
                Tables\Actions\Action::make('reset')
                    ->label('Reset Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(
                        fn(Lembur $record): bool =>
                        in_array($record->status_lembur, ['Disetujui', 'Ditolak']) &&
                        Auth::user()?->hasRole(['Admin', 'CEO'])
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Reset Status Lembur')
                    ->modalDescription('Apakah Anda yakin ingin mereset status lembur ini kembali ke "Diajukan"?')
                    ->modalSubmitActionLabel('Ya, Reset')
                    ->action(function (Lembur $record): void {
                        $record->update([
                            'status_lembur' => 'Diajukan',
                            'total_insentif' => null,
                            'approver_id' => null,
                            'processed_at' => null,
                            'alasan_penolakan' => null,
                        ]);

                        Notification::make()
                            ->title('Status Berhasil Direset')
                            ->body('Status lembur telah direset ke "Diajukan".')
                            ->info()
                            ->send();
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
                    // BULK ACTION: Setujui Banyak Lembur
                    Tables\Actions\BulkAction::make('approve_bulk')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Setujui Pengajuan Lembur')
                        ->modalDescription('Apakah Anda yakin ingin menyetujui semua pengajuan lembur yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Setujui Semua')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $approved = 0;
                            $errors = [];

                            foreach ($records as $record) {
                                if ($record->status_lembur === 'Diajukan') {
                                    try {
                                        $insentif = $record->calculateInsentif();
                                        $record->update([
                                            'status_lembur' => 'Disetujui',
                                            'total_insentif' => $insentif,
                                            'approver_id' => Auth::user()->karyawan_id ?? 'SYSTEM',
                                            'processed_at' => now(),
                                            'alasan_penolakan' => null,
                                        ]);
                                        $approved++;
                                    } catch (\Exception $e) {
                                        $errors[] = "Error untuk {$record->lembur_id}: " . $e->getMessage();
                                    }
                                }
                            }

                            if ($approved > 0) {
                                Notification::make()
                                    ->title('Bulk Approval Selesai')
                                    ->body("{$approved} pengajuan lembur berhasil disetujui.")
                                    ->success()
                                    ->send();
                            }

                            if (!empty($errors)) {
                                Notification::make()
                                    ->title('Beberapa Error Terjadi')
                                    ->body(implode(', ', $errors))
                                    ->warning()
                                    ->send();
                            }
                        }),

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
            'index' => Pages\ListLemburs::route('/'),
            'create' => Pages\CreateLembur::route('/create'),
            'view' => Pages\ViewLembur::route('/{record}'),
            'edit' => Pages\EditLembur::route('/{record}/edit'),
        ];
    }
}
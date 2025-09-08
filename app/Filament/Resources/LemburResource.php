<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LemburResource\Pages;
use App\Filament\Resources\LemburResource\RelationManagers;
use App\Models\Lembur;
use App\Models\Karyawan;
use App\Models\Absensi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LemburResource extends Resource
{
    protected static ?string $model = Lembur::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Lembur';

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
                                    $set('processed_at', now()); // Changed from approved_at
                                    $set('alasan_penolakan', null);
                                } elseif ($state === 'Ditolak') {
                                    $set('processed_at', null); // Changed from approved_at
                                } else {
                                    $set('processed_at', null); // Changed from approved_at
                                    $set('alasan_penolakan', null);
                                }
                            }),
                        Forms\Components\Select::make('approver_id') // Changed from approved_by
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
                        Forms\Components\DateTimePicker::make('processed_at') // Changed from approved_at
                            ->label('Waktu Persetujuan')
                            ->visible(fn(callable $get) => $get('status_lembur') === 'Disetujui'),
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
                Tables\Columns\TextColumn::make('absensi.tanggal')
                    ->label('Tanggal Absensi')
                    ->date()
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
                Tables\Columns\TextColumn::make('status_lembur')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Diajukan' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('approver.nama_lengkap') // Changed from approver.nama_lengkap
                    ->label('Disetujui Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('processed_at') // Changed from approved_at
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
            ->defaultSort('lembur_id', 'desc');
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
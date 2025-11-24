<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbsensiResource\Pages;
use App\Filament\Resources\AbsensiResource\RelationManagers;
use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\Cabang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AbsensiResource extends Resource
{
    protected static ?string $model = Absensi::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Kelola Absensi';

    protected static ?string $pluralModelLabel = 'Absensi';

    protected static ?string $navigationGroup = 'Manajemen Absensi';

    protected static ?string $modelLabel = 'Absensi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Absensi')
                    ->schema([
                        Forms\Components\Select::make('karyawan_id')
                            ->label('Karyawan')
                            ->options(Karyawan::all()->pluck('nama_lengkap', 'karyawan_id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('cabang_id')
                            ->label('Cabang')
                            ->options(Cabang::all()->pluck('nama_cabang', 'cabang_id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('status_absensi')
                            ->label('Status Absensi')
                            ->options([
                                'Hadir' => 'Hadir',
                                'Tidak Tepat' => 'Tidak Tepat',
                                'Alfa' => 'Alfa',
                            ])
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Waktu Masuk')
                    ->schema([
                        Forms\Components\DateTimePicker::make('waktu_masuk')
                            ->label('Waktu Masuk')
                            ->required(),
                        Forms\Components\Select::make('status_masuk')
                            ->label('Status Masuk')
                            ->options([
                                'Tepat Waktu' => 'Tepat Waktu',
                                'Telat' => 'Telat',
                            ]),
                        Forms\Components\TimePicker::make('durasi_telat')
                            ->label('Durasi Telat'),
                        Forms\Components\TextInput::make('koordinat_masuk')
                            ->label('Koordinat Masuk')
                            ->required()
                            ->placeholder('latitude,longitude'),
                        Forms\Components\FileUpload::make('foto_masuk')
                            ->label('Foto Masuk')
                            ->image()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Waktu Pulang')
                    ->schema([
                        Forms\Components\DateTimePicker::make('waktu_pulang')
                            ->label('Waktu Pulang'),
                        Forms\Components\Select::make('status_pulang')
                            ->label('Status Pulang')
                            ->options([
                                'Tepat Waktu' => 'Tepat Waktu',
                                'Pulang Cepat' => 'Pulang Cepat',
                            ]),
                        Forms\Components\TimePicker::make('durasi_pulang_cepat')
                            ->label('Durasi Pulang Cepat'),
                        Forms\Components\TextInput::make('koordinat_pulang')
                            ->label('Koordinat Pulang')
                            ->placeholder('latitude,longitude'),
                        Forms\Components\FileUpload::make('foto_pulang')
                            ->label('Foto Pulang')
                            ->image(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('absensi_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('karyawan.nama_lengkap')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu_masuk')
                    ->label('Waktu Masuk')
                    ->dateTime('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('waktu_pulang')
                    ->label('Waktu Pulang')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status_absensi')
                    ->label('Status Absensi')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Tidak Tepat' => 'warning',
                        'Alfa' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status_absensi')
                    ->label('Status Absensi')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Tidak Tepat' => 'Tidak Tepat',
                        'Alfa' => 'Alfa',
                    ]),
                Tables\Filters\Filter::make('tanggal')
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
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date),
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
            'index' => Pages\ListAbsensis::route('/'),
            'create' => Pages\CreateAbsensi::route('/create'),
            'view' => Pages\ViewAbsensi::route('/{record}'),
            'edit' => Pages\EditAbsensi::route('/{record}/edit'),
        ];
    }
}
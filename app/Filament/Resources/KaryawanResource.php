<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KaryawanResource\Pages;
use App\Filament\Resources\KaryawanResource\RelationManagers;
use App\Models\Karyawan;
use App\Models\Role;
use App\Models\Perusahaan;
use App\Models\GolonganPtkp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationLabel = 'Kelola Karyawan';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('karyawan_id'),

                Forms\Components\Section::make('Informasi Personal')
                    ->schema([
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->required(),

                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nomor_telepon')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Akun & Otentikasi')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn(string $context): bool => $context === 'create')
                            ->dehydrated(fn($state) => filled($state))
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->minLength(8),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Kepegawaian')
                    ->schema([
                        Forms\Components\Select::make('perusahaan_id')
                            ->label('Perusahaan')
                            ->relationship('perusahaan', 'nama_perusahaan')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('role_id')
                            ->label('Role')
                            ->relationship('role', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('golongan_ptkp_id')
                            ->label('Golongan PTKP')
                            ->relationship('golonganPtkp', 'nama_golongan_ptkp')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('departemen')
                            ->label('Departemen')
                            ->maxLength(100),

                        Forms\Components\Select::make('status_kepegawaian')
                            ->label('Status Kepegawaian')
                            ->options([
                                'Tetap' => 'Tetap',
                                'Kontrak' => 'Kontrak',
                                'Magang' => 'Magang',
                                'Freelance' => 'Freelance',
                            ]),

                        Forms\Components\DatePicker::make('tanggal_mulai_bekerja')
                            ->label('Tanggal Mulai Bekerja')
                            ->native(false),

                        Forms\Components\TextInput::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Rekening & BPJS')
                    ->schema([
                        Forms\Components\TextInput::make('nomor_rekening')
                            ->label('Nomor Rekening')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('nama_pemilik_rekening')
                            ->label('Nama Pemilik Rekening')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nomor_bpjs_kesehatan')
                            ->label('Nomor BPJS Kesehatan')
                            ->maxLength(50),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('karyawan_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('perusahaan.nama_perusahaan')
                    ->label('Perusahaan')
                    ->searchable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Kontak')
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        $email = $record->email;
                        return $email;
                    })
                    ->html()
                    ->description(fn($record): string => $record->nomor_telepon ?: '-'),

                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->badge()
                    ->color(fn($record) => $record->role_color)
                    ->default('-'),

                Tables\Columns\TextColumn::make('tanggal_mulai_bekerja')
                    ->label('Mulai Bekerja')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('gaji_pokok')
                    ->label('Gaji Pokok')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('karyawan_id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('perusahaan_id')
                    ->label('Perusahaan')
                    ->relationship('perusahaan', 'nama_perusahaan')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Role')
                    ->relationship('role', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status_kepegawaian')
                    ->label('Status Kepegawaian')
                    ->options([
                        'Tetap' => 'Tetap',
                        'Kontrak' => 'Kontrak',
                        'Magang' => 'Magang',
                        'Freelance' => 'Freelance',
                    ]),

                Tables\Filters\TrashedFilter::make()
                    ->label('Status')
                    ->placeholder('-- Pilih Status --')
                    ->trueLabel('Dihapus')
                    ->falseLabel('Aktif')
                    ->queries(
                        true: fn(Builder $query) => $query->onlyTrashed(),
                        false: fn(Builder $query) => $query->withoutTrashed(),
                        blank: fn(Builder $query) => $query->withoutTrashed(),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat'),
                Tables\Actions\EditAction::make()
                    ->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Karyawan')
                    ->modalDescription('Apakah Anda yakin ingin menghapus karyawan ini? Data akan diarsipkan dan dapat dipulihkan kembali.')
                    ->modalSubmitActionLabel('Ya, hapus'),
                Tables\Actions\RestoreAction::make()
                    ->label('Pulihkan')
                    ->color('success'),
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Hapus Permanen')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Permanen')
                    ->modalDescription('Apakah Anda yakin ingin menghapus permanen? Tindakan ini tidak dapat dibatalkan!')
                    ->modalSubmitActionLabel('Ya, hapus permanen')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->requiresConfirmation(),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Pulihkan Terpilih'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen Terpilih')
                        ->requiresConfirmation(),
                ]),
            ]);
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
            'index' => Pages\ListKaryawans::route('/'),
            'create' => Pages\CreateKaryawan::route('/create'),
            'view' => Pages\ViewKaryawan::route('/{record}'),
            'edit' => Pages\EditKaryawan::route('/{record}/edit'),
        ];
    }
}
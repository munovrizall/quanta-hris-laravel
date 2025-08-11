<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KaryawanResource\Pages;
use App\Models\Karyawan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class KaryawanResource extends Resource
{
    protected static ?string $model = Karyawan::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Manajemen Karyawan';

    protected static ?string $navigationLabel = 'Data Karyawan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pribadi')
                    ->schema([
                        Forms\Components\TextInput::make('karyawan_id')
                            ->label('ID Karyawan')
                            ->required()
                            ->maxLength(5)
                            ->disabled()
                            ->dehydrated() 
                            ->default(fn() => 'K' . str_pad(Karyawan::count() + 1, 4, '0', STR_PAD_LEFT)),
                        Forms\Components\TextInput::make('nama_lengkap')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nomor_telepon')
                            ->tel()
                            ->maxLength(20), // nullable
                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->required(),
                        Forms\Components\Select::make('jenis_kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('alamat')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Kepegawaian')
                    ->schema([
                        Forms\Components\Select::make('perusahaan_id')
                            ->relationship('perusahaan', 'nama_perusahaan')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('role_id')
                            ->relationship('role', 'role_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('jabatan')
                            ->maxLength(100), // nullable
                        Forms\Components\TextInput::make('departemen')
                            ->maxLength(100), // nullable
                        Forms\Components\Select::make('status_kepegawaian')
                            ->options([
                                'Tetap' => 'Karyawan Tetap',
                                'Kontrak' => 'Kontrak',
                                'Magang' => 'Magang',
                                'Freelance' => 'Freelance'
                            ]), // nullable
                        Forms\Components\DatePicker::make('tanggal_mulai_bekerja'), // nullable
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Finansial & BPJS')
                    ->schema([
                        Forms\Components\Select::make('golongan_ptkp_id')
                            ->relationship('golonganPtkp', 'nama_golongan_ptkp')
                            ->label('Golongan PTKP'), // nullable
                        Forms\Components\TextInput::make('gaji_pokok')
                            ->numeric()
                            ->prefix('Rp'), // nullable
                        Forms\Components\TextInput::make('nomor_rekening')
                            ->maxLength(50), // nullable
                        Forms\Components\TextInput::make('nama_pemilik_rekening')
                            ->maxLength(255), // nullable
                        Forms\Components\TextInput::make('nomor_bpjs_kesehatan')
                            ->maxLength(50), // nullable
                    ])->columns(2),

                Forms\Components\Section::make('Autentikasi')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('karyawan_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('perusahaan.nama_perusahaan')
                    ->label('Perusahaan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jabatan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.role_name')
                    ->label('Role')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_kepegawaian')
                    ->badge(),
            ])
            ->defaultSort('karyawan_id', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle('Karyawan berhasil dihapus')
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Di sini Anda bisa menambahkan Relation Manager nanti, misalnya untuk absensi
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKaryawans::route('/'),
            'create' => Pages\CreateKaryawan::route('/create'),
            'edit' => Pages\EditKaryawan::route('/{record}/edit'),
        ];
    }
}
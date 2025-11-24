<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PerusahaanResource\Pages;
use App\Filament\Resources\PerusahaanResource\RelationManagers;
use App\Models\Perusahaan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PerusahaanResource extends Resource
{
    protected static ?string $model = Perusahaan::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Kelola Perusahaan';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('perusahaan_id'),

                Forms\Components\TextInput::make('nama_perusahaan')
                    ->label('Nama Perusahaan')
                    ->required()
                    ->maxLength(255)
                    ->autocomplete(false)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->autocomplete(false)
                    ->maxLength(255),

                Forms\Components\TextInput::make('nomor_telepon')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->required()
                    ->autocomplete(false)

                    ->maxLength(20),

                Forms\Components\TimePicker::make('jam_masuk')
                    ->label('Jam Masuk Kerja')
                    ->required()
                    ->default('09:00')
                    ->seconds(false),

                Forms\Components\TimePicker::make('jam_pulang')
                    ->label('Jam Pulang Kerja')
                    ->required()
                    ->default('17:00')
                    ->seconds(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('perusahaan_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('nama_perusahaan')
                    ->label('Nama Perusahaan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('kontak')
                    ->label('Kontak')
                    ->getStateUsing(function ($record) {
                        return "<div style='font-weight: 500;'>{$record->email}</div>
                        <small style='color: #6b7280; font-size: 0.75rem;'>{$record->nomor_telepon}</small>";
                    })
                    ->html()
                    ->searchable(['email', 'nomor_telepon']),

                Tables\Columns\TextColumn::make('jam_operasional')
                    ->label('Jam Operasional')
                    ->getStateUsing(function ($record) {
                        return "<div style='font-size: 0.875rem;'>
                        <span style='color: #22c55e;'>{$record->jam_masuk}</span> - 
                        <span style='color: #f43f5e;'>{$record->jam_pulang}</span>
                        </div>";
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('karyawan_count')
                    ->label('Karyawan')
                    ->counts('karyawan')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('cabang_count')
                    ->label('Cabang')
                    ->counts('cabang')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime()
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
            ])
            ->defaultSort('perusahaan_id', 'desc')
            ->filters([
                Tables\Filters\Filter::make('has_employees')
                    ->label('Memiliki Karyawan')
                    ->query(fn(Builder $query): Builder => $query->has('karyawan')),

                Tables\Filters\Filter::make('has_branches')
                    ->label('Memiliki Cabang')
                    ->query(fn(Builder $query): Builder => $query->has('cabang')),

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
                    ->modalHeading('Yakin Hapus?')
                    ->modalDescription('Apakah Anda yakin ingin menghapus perusahaan ini? Data akan diarsipkan dan dapat dipulihkan kembali.')
                    ->modalSubmitActionLabel('Hapus'),
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
            'index' => Pages\ListPerusahaans::route('/'),
            'create' => Pages\CreatePerusahaan::route('/create'),
            'view' => Pages\ViewPerusahaan::route('/{record}'),
            'edit' => Pages\EditPerusahaan::route('/{record}/edit'),
        ];
    }
}


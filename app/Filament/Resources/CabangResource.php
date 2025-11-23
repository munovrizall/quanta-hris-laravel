<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CabangResource\Pages;
use App\Filament\Resources\CabangResource\RelationManagers;
use App\Models\Cabang;
use App\Models\Perusahaan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CabangResource extends Resource
{
    protected static ?string $model = Cabang::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Kelola Cabang';

    protected static ?string $navigationGroup = 'Data Master';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('cabang_id'),

                Forms\Components\Select::make('perusahaan_id')
                    ->label('Perusahaan')
                    ->relationship('perusahaan', 'nama_perusahaan')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('nama_cabang')
                    ->label('Nama Cabang')
                    ->required()
                    ->maxLength(255)
                    ->autocomplete(false)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('alamat')
                    ->label('Alamat')
                    ->required()
                    ->rows(3)
                    ->autocomplete(false)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric()
                            ->step('any')
                            ->required()
                            ->autocomplete(false)
                            ->placeholder('-6.2088'),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric()
                            ->step('any')
                            ->required()
                            ->autocomplete(false)
                            ->placeholder('106.8456'),
                    ]),

                Forms\Components\TextInput::make('radius_lokasi')
                    ->label('Radius Lokasi (meter)')
                    ->numeric()
                    ->required()
                    ->default(100)
                    ->suffix('meter')
                    ->autocomplete(false)
                    ->minValue(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cabang_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('perusahaan.nama_perusahaan')
                    ->label('Perusahaan')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('nama_cabang')
                    ->label('Nama Cabang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(20)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 20 ? $state : null;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('koordinat')
                    ->label('Koordinat')
                    ->getStateUsing(function ($record) {
                        return "<div style='font-size: 0.875rem;'>
                        <div>Lat: {$record->latitude}</div>
                        <div>Lng: {$record->longitude}</div>
                        <div>Radius: {$record->radius_lokasi} m</div>
                        </div>";
                    })
                    ->html(),

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
            ->defaultSort('cabang_id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('perusahaan_id')
                    ->label('Perusahaan')
                    ->relationship('perusahaan', 'nama_perusahaan')
                    ->searchable()
                    ->preload(),

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
                    ->modalDescription('Apakah Anda yakin ingin menghapus cabang ini? Data akan diarsipkan dan dapat dipulihkan kembali.')
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
            'index' => Pages\ListCabangs::route('/'),
            'create' => Pages\CreateCabang::route('/create'),
            'view' => Pages\ViewCabang::route('/{record}'),
            'edit' => Pages\EditCabang::route('/{record}/edit'),
        ];
    }
}


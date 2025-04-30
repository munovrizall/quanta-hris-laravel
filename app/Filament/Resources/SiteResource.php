<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Filament\Resources\SiteResource\RelationManagers;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Client Management';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->required()
                    ->placeholder('Select a Company'),
                Forms\Components\TextInput::make('name')
                    ->label('Site Name')
                    ->required()
                    ->autocomplete(false)
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->label('Address')
                    ->required()
                    ->autocomplete(false)
                    ->maxLength(255),
                Forms\Components\TextInput::make('latitude')
                    ->label('Latitude')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('longitude')
                    ->label('Longitude')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('radius_in_m')
                    ->label('Radius (m)')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label("Site Name")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('address')
                    ->label("Address")
                    ->searchable()
                    ->sortable()
                    ->limit(10),
                Tables\Columns\TextColumn::make('coordinate')
                    ->label('Site Coordinate')
                    ->getStateUsing(function ($record) {
                        return "{$record->latitude}, {$record->longitude}";
                    })
                    ->limit(10)
                    ->html(),
                Tables\Columns\TextColumn::make('radius_in_m')
                    ->label("Radius (m)"),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}

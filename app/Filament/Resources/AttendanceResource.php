<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Attendance Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name')
                    ->placeholder('Select an Employee')
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->required()
                    ->displayFormat('d-m-Y')
                    ->format('Y-m-d'),
                Forms\Components\TimePicker::make('time_in')
                    ->label('Time In')
                    ->seconds(false)
                    ->required(),
                Forms\Components\TimePicker::make('time_out')
                    ->label('Time Out')
                    ->seconds(false)
                    ->nullable(),
                Forms\Components\TextInput::make('latlon_in')
                    ->label('LatLon In')
                    ->autocomplete(false)
                    ->required(),
                Forms\Components\TextInput::make('latlon_out')
                    ->label('LatLon Out')
                    ->autocomplete(false)
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->searchable()
                    ->sortable()
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('attendance_time')
                    ->label('Time')
                    ->getStateUsing(function ($record) {
                        return "{$record->time_in} - {$record->time_out}";
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('attendance_position')
                    ->label('Position')
                    ->getStateUsing(function ($record) {
                        return "In: {$record->latlon_in}<br>Out: {$record->latlon_out}";
                    })
                    ->html(),
            ])
            ->defaultSort('date', 'desc')
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}

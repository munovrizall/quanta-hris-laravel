<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Filament\Resources\PermissionResource\RelationManagers;
use App\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Attendance Management';

     public static function getNavigationBadge(): ?string
     {
         return static::getModel()::where('approval_status', 'pending')->count() ?: null;
     }
 
     public static function getNavigationBadgeColor(): ?string
     {
         return static::getModel()::where('approval_status', 'pending')->count() > 0 ? 'warning' : null;
     }
 

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\DatePicker::make('date_permission')
                    ->label('Permission Date')
                    ->required(),

                Forms\Components\Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('image')
                    ->label('Supporting Document')
                    ->image()
                    ->directory('permission-documents')
                    ->visibility('public')
                    ->maxSize(10240) // 10MB
                    ->columnSpanFull(),

                Forms\Components\Hidden::make('approval_status')
                    ->default('Pending'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('Position')
                    ->label('Employee Info')
                    ->getStateUsing(function ($record) {
                        return "<div>{$record->user->name}</div>
                <small style='color: #6b7280; font-size: 0.75rem;'>{$record->user->department} • {$record->user->position}</small>";
                    })
                    ->html()
                    ->searchable(),

                Tables\Columns\TextColumn::make('date_permission')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->size(40)
                    ->disk('public')
                    ->url(fn($state) => '/storage/' . $state)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return match (strtolower((string) $state)) {
                            'pending', null, 0 => 'Pending',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                            default => 'Unknown',
                        };
                    })
                    ->color(function ($state) {
                        return match ($state) {
                            null, 'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            default => 'secondary',
                        };
                    }),
            ])
            ->defaultSort('date_permission', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Permission $record): void {
                        $record->approval_status = 'approved';
                        $record->save();
                        Notification::make()
                            ->title('Permission approved')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Permission $record): void {
                        $record->approval_status = 'rejected';
                        $record->save();
                        Notification::make()
                            ->title('Permission Rejected')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}

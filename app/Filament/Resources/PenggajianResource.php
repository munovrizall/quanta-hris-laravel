<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenggajianResource\Pages;
use App\Models\Penggajian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Carbon\Carbon;

class PenggajianResource extends Resource
{
    protected static ?string $model = Penggajian::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Penggajian';

    protected static ?string $slug = 'penggajian';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Manajemen Penggajian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Periode')
                    ->schema([
                        Forms\Components\Select::make('periode_bulan')
                            ->label('Bulan')
                            ->options([
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember'
                            ])
                            ->default(Carbon::now()->month)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('periode_tahun')
                            ->label('Tahun')
                            ->options(function () {
                                $currentYear = Carbon::now()->year;
                                $years = [];
                                for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->default(Carbon::now()->year)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status dan Persetujuan')
                    ->schema([
                        Forms\Components\Select::make('status_penggajian')
                            ->label('Status Penggajian')
                            ->options([
                                'Draf' => 'Draf',
                                'Diverifikasi' => 'Diverifikasi',
                                'Disetujui' => 'Disetujui',
                                'Ditolak' => 'Ditolak',
                            ])
                            ->default('Draf')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state === 'Draf') {
                                    $set('verified_by', null);
                                    $set('approved_by', null);
                                    $set('processed_by', null);
                                }
                            }),

                        Forms\Components\Select::make('verified_by')
                            ->label('Diverifikasi Oleh (Staff HRD)')
                            ->relationship('verifier', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->visible(fn(Forms\Get $get) => in_array($get('status_penggajian'), ['Diverifikasi', 'Disetujui']))
                            ->columnSpanFull(),

                        Forms\Components\Select::make('approved_by')
                            ->label('Disetujui Oleh (Manager Finance)')
                            ->relationship('approver', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->visible(fn(Forms\Get $get) => $get('status_penggajian') === 'Disetujui')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('processed_by')
                            ->label('Diproses Oleh (Account Payment)')
                            ->relationship('processor', 'nama_lengkap')
                            ->searchable()
                            ->preload()
                            ->visible(fn(Forms\Get $get) => $get('status_penggajian') === 'Disetujui')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('catatan_penolakan_draf')
                            ->label('Catatan Penolakan')
                            ->rows(3)
                            ->visible(fn(Forms\Get $get) => $get('status_penggajian') === 'Ditolak')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn(Forms\Get $get) => $get('status_penggajian') === 'Ditolak'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode')
                    ->getStateUsing(function (Penggajian $record): string {
                        $namaBulan = [
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember'
                        ];
                        return $namaBulan[$record->periode_bulan] . ' ' . $record->periode_tahun;
                    })
                    ->searchable(['periode_bulan', 'periode_tahun'])
                    ->sortable(['periode_bulan', 'periode_tahun'])
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('status_penggajian')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Draf' => 'gray',
                        'Diverifikasi' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Draf' => 'heroicon-m-pencil-square',
                        'Diverifikasi' => 'heroicon-m-check-circle',
                        'Disetujui' => 'heroicon-m-check-badge',
                        'Ditolak' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    }),
                // Temporary columns untuk menggantikan slip gaji dependency
                Tables\Columns\TextColumn::make('detail_count')
                    ->label('Total Karyawan')
                    ->getStateUsing(function (Penggajian $record): string {
                        return (string) ($record->detail_count ?? 0);
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('verifier.nama_lengkap')
                    ->label('Diverifikasi Oleh')
                    ->default('-'),

                Tables\Columns\TextColumn::make('approver.nama_lengkap')
                    ->label('Disetujui Oleh')
                    ->default('-'),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_penggajian')
                    ->label('Status')
                    ->options([
                        'Draf' => 'Draf',
                        'Diverifikasi' => 'Diverifikasi',
                        'Disetujui' => 'Disetujui',
                        'Ditolak' => 'Ditolak',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('periode_bulan')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember'
                    ]),

                Tables\Filters\SelectFilter::make('periode_tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $currentYear = Carbon::now()->year;
                        $years = [];
                        for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++) {
                            $years[$i] = $i;
                        }
                        return $years;
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->url(
                        fn(Penggajian $record): string =>
                        static::getUrl('view', [
                            'tahun' => $record->periode_tahun,
                            'bulan' => $record->periode_bulan
                        ])
                    ),
                Tables\Actions\EditAction::make()
                    ->label('Ubah')
                    ->url(
                        fn(Penggajian $record): string =>
                        static::getUrl('edit', [
                            'tahun' => $record->periode_tahun,
                            'bulan' => $record->periode_bulan
                        ])
                    ),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->action(function (Penggajian $record) {
                        Penggajian::forPeriode($record->periode_bulan, $record->periode_tahun)->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->action(function ($records) {
                            $records->each(function (Penggajian $record) {
                                Penggajian::forPeriode($record->periode_bulan, $record->periode_tahun)->delete();
                            });
                        }),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Hapus Permanen')
                        ->action(function ($records) {
                            $records->each(function (Penggajian $record) {
                                Penggajian::forPeriode($record->periode_bulan, $record->periode_tahun)->withTrashed()->forceDelete();
                            });
                        }),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Pulihkan')
                        ->action(function ($records) {
                            $records->each(function (Penggajian $record) {
                                Penggajian::forPeriode($record->periode_bulan, $record->periode_tahun)->withTrashed()->restore();
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $model = static::getModel();

        return $model::query()
            ->selectRaw('MIN(tabel_id) as tabel_id')
            ->selectRaw('periode_bulan, periode_tahun')
            ->selectRaw('MAX(status_penggajian) as status_penggajian')
            ->selectRaw('MAX(verified_by) as verified_by')
            ->selectRaw('MAX(approved_by) as approved_by')
            ->selectRaw('MAX(processed_by) as processed_by')
            ->selectRaw('MAX(catatan_penolakan_draf) as catatan_penolakan_draf')
            ->selectRaw('MAX(created_at) as created_at')
            ->selectRaw('MAX(updated_at) as updated_at')
            ->selectRaw('MAX(deleted_at) as deleted_at')
            ->selectRaw('COUNT(*) as detail_count')
            ->groupBy('periode_bulan', 'periode_tahun');
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
            'index' => Pages\ListPenggajians::route('/'),
            'create' => Pages\CreatePenggajian::route('/create'),
            'view' => Pages\ViewPenggajian::route('/{tahun}/{bulan}'),
            'edit' => Pages\EditPenggajian::route('/{tahun}/{bulan}/edit'),
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['verifier', 'approver', 'processor']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['tabel_id', 'verifier.nama_lengkap', 'approver.nama_lengkap'];
    }
}

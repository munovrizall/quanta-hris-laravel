<?php

namespace App\Filament\Resources\PenggajianResource\Actions;

use App\Models\DetailPenggajian;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EditGajiKaryawanAction
{
  public static function make(): Action
  {
    return Action::make('editKaryawanGaji')
      ->label('Edit Gaji Karyawan')
      ->icon('heroicon-o-pencil-square')
      ->color('warning')
      ->form([
        Forms\Components\Hidden::make('detail_penggajian_id'),

        Forms\Components\Section::make('Informasi Karyawan')
          ->schema([
            Forms\Components\TextInput::make('karyawan_id')
              ->label('ID Karyawan')
              ->disabled()
              ->dehydrated(false),

            Forms\Components\TextInput::make('nama_karyawan')
              ->label('Nama Karyawan')
              ->disabled()
              ->dehydrated(false),
          ])
          ->columns(2),

        Forms\Components\Section::make('Penghasilan')
          ->schema([
            Forms\Components\TextInput::make('gaji_pokok')
              ->label('Gaji Pokok')
              ->numeric()
              ->prefix('Rp')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0'),

            Forms\Components\TextInput::make('total_tunjangan')
              ->label('Total Tunjangan')
              ->numeric()
              ->prefix('Rp')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0'),

            Forms\Components\TextInput::make('total_lembur')
              ->label('Upah Lembur')
              ->numeric()
              ->prefix('Rp')
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0')
              ->dehydrateStateUsing(fn($state) => $state ? (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)) : 0)
              ->reactive()
              ->afterStateUpdated(fn($state, $set, $get) => static::recalculateGajiBersih($set, $get)),

            Forms\Components\TextInput::make('penghasilan_bruto')
              ->label('Penghasilan Bruto')
              ->numeric()
              ->prefix('Rp')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0'),
          ])
          ->columns(2),

        Forms\Components\Section::make('Potongan')
          ->schema([
            Forms\Components\TextInput::make('potongan_alfa')
              ->label('Potongan Alfa')
              ->numeric()
              ->prefix('Rp')
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0')
              ->dehydrateStateUsing(fn($state) => $state ? (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)) : 0)
              ->reactive()
              ->afterStateUpdated(fn($state, $set, $get) => static::recalculateGajiBersih($set, $get)),

            Forms\Components\TextInput::make('potongan_terlambat')
              ->label('Potongan Terlambat')
              ->numeric()
              ->prefix('Rp')
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0')
              ->dehydrateStateUsing(fn($state) => $state ? (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)) : 0)
              ->reactive()
              ->afterStateUpdated(fn($state, $set, $get) => static::recalculateGajiBersih($set, $get)),

            Forms\Components\TextInput::make('potongan_bpjs')
              ->label('Potongan BPJS')
              ->numeric()
              ->prefix('Rp')
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0')
              ->dehydrateStateUsing(fn($state) => $state ? (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)) : 0)
              ->reactive()
              ->afterStateUpdated(fn($state, $set, $get) => static::recalculateGajiBersih($set, $get)),

            Forms\Components\TextInput::make('potongan_pph21')
              ->label('PPh21')
              ->numeric()
              ->prefix('Rp')
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0')
              ->dehydrateStateUsing(fn($state) => $state ? (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)) : 0)
              ->reactive()
              ->afterStateUpdated(fn($state, $set, $get) => static::recalculateGajiBersih($set, $get)),
          ])
          ->columns(2),

        Forms\Components\Section::make('Penyesuaian')
          ->schema([
            Forms\Components\TextInput::make('penyesuaian')
              ->label('Penyesuaian')
              ->numeric()
              ->prefix('Rp')
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0')
              ->dehydrateStateUsing(fn($state) => $state ? (float) str_replace(['.', ','], ['', '.'], str_replace('Rp ', '', $state)) : 0)
              ->reactive()
              ->afterStateUpdated(fn($state, $set, $get) => static::recalculateGajiBersih($set, $get))
              ->helperText('Masukkan nilai positif untuk penambahan, negatif untuk pengurangan'),

            Forms\Components\Textarea::make('catatan_penyesuaian')
              ->label('Catatan Penyesuaian')
              ->rows(3)
              ->columnSpanFull()
              ->placeholder('Jelaskan alasan penyesuaian...')
              ->maxLength(500),
          ])
          ->columns(1),

        Forms\Components\Section::make('Total')
          ->schema([
            Forms\Components\TextInput::make('total_potongan')
              ->label('Total Potongan')
              ->numeric()
              ->prefix('Rp')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0'),

            Forms\Components\TextInput::make('gaji_bersih')
              ->label('Gaji Bersih')
              ->numeric()
              ->prefix('Rp')
              ->disabled()
              ->dehydrated(false)
              ->formatStateUsing(fn($state) => $state ? number_format($state, 0, ',', '.') : '0')
              ->extraAttributes(['style' => 'font-weight: bold; color: #059669;']),
          ])
          ->columns(2),
      ])
      ->fillForm(function (array $arguments): array {
        $detailId = $arguments['detailId'] ?? null;

        if (!$detailId) {
          Notification::make()
            ->title('Error')
            ->body('ID detail penggajian tidak ditemukan.')
            ->danger()
            ->send();
          return [];
        }

        $detail = DetailPenggajian::with(['karyawan'])
          ->find($detailId);

        if (!$detail) {
          Notification::make()
            ->title('Error')
            ->body('Data detail penggajian tidak ditemukan.')
            ->danger()
            ->send();
          return [];
        }

        // Ensure all values are properly formatted
        return [
          'detail_penggajian_id' => $detail->id,
          'karyawan_id' => $detail->karyawan_id,
          'nama_karyawan' => $detail->karyawan->nama_lengkap ?? 'N/A',
          'gaji_pokok' => $detail->gaji_pokok ?? 0,
          'total_tunjangan' => $detail->total_tunjangan ?? 0,
          'total_lembur' => $detail->total_lembur ?? 0,
          'penghasilan_bruto' => $detail->penghasilan_bruto ?? 0,
          'potongan_alfa' => $detail->potongan_alfa ?? 0,
          'potongan_terlambat' => $detail->potongan_terlambat ?? 0,
          'potongan_bpjs' => $detail->potongan_bpjs ?? 0,
          'potongan_pph21' => $detail->potongan_pph21 ?? 0,
          'penyesuaian' => $detail->penyesuaian ?? 0,
          'catatan_penyesuaian' => $detail->catatan_penyesuaian ?? '',
          'total_potongan' => $detail->total_potongan ?? 0,
          'gaji_bersih' => $detail->gaji_bersih ?? 0,
        ];
      })
      ->action(function (array $data): void {
        static::handleSave($data);
      })
      ->modalHeading('Edit Gaji Karyawan')
      ->modalSubmitActionLabel('Simpan Perubahan')
      ->modalCancelActionLabel('Batal')
      ->modalWidth('7xl')
      ->slideOver();
  }

  /**
   * Recalculate gaji bersih when form values change
   */
  protected static function recalculateGajiBersih($set, $get): void
  {
    try {
      // Get current values and convert to float
      $gajiPokok = static::parseNumber($get('gaji_pokok') ?? 0);
      $totalTunjangan = static::parseNumber($get('total_tunjangan') ?? 0);
      $totalLembur = static::parseNumber($get('total_lembur') ?? 0);

      $potonganAlfa = static::parseNumber($get('potongan_alfa') ?? 0);
      $potonganTerlambat = static::parseNumber($get('potongan_terlambat') ?? 0);
      $potonganBpjs = static::parseNumber($get('potongan_bpjs') ?? 0);
      $potonganPph21 = static::parseNumber($get('potongan_pph21') ?? 0);
      $penyesuaian = static::parseNumber($get('penyesuaian') ?? 0);

      // Calculate totals
      $penghasilanBruto = $gajiPokok + $totalTunjangan + $totalLembur;
      $totalPotongan = $potonganAlfa + $potonganTerlambat + $potonganBpjs + $potonganPph21;
      $gajiBersih = $penghasilanBruto - $totalPotongan + $penyesuaian;

      // Update form fields with formatted values
      $set('penghasilan_bruto', number_format($penghasilanBruto, 0, ',', '.'));
      $set('total_potongan', number_format($totalPotongan, 0, ',', '.'));
      $set('gaji_bersih', number_format(max(0, $gajiBersih), 0, ',', '.'));

    } catch (\Exception $e) {
      Log::error('Error recalculating gaji bersih: ' . $e->getMessage());
    }
  }

  /**
   * Parse number from formatted string
   */
  protected static function parseNumber($value): float
  {
    if (is_numeric($value)) {
      return (float) $value;
    }

    // Remove currency formatting
    $cleaned = str_replace(['Rp ', '.', ','], ['', '', '.'], $value);
    return (float) $cleaned;
  }

  /**
   * Handle form submission
   */
  protected static function handleSave(array $data): void
  {
    try {
      $detail = DetailPenggajian::find($data['detail_penggajian_id']);

      if (!$detail) {
        Notification::make()
          ->title('Error')
          ->body('Data detail penggajian tidak ditemukan.')
          ->danger()
          ->send();
        return;
      }

      // Parse and calculate values
      $gajiPokok = $detail->gaji_pokok;
      $totalTunjangan = $detail->total_tunjangan;
      $totalLembur = $data['total_lembur'] ?? 0;

      $potonganAlfa = $data['potongan_alfa'] ?? 0;
      $potonganTerlambat = $data['potongan_terlambat'] ?? 0;
      $potonganBpjs = $data['potongan_bpjs'] ?? 0;
      $potonganPph21 = $data['potongan_pph21'] ?? 0;
      $penyesuaian = $data['penyesuaian'] ?? 0;

      // Calculate totals
      $penghasilanBruto = $gajiPokok + $totalTunjangan + $totalLembur;
      $totalPotongan = $potonganAlfa + $potonganTerlambat + $potonganBpjs + $potonganPph21;
      $gajiBersih = $penghasilanBruto - $totalPotongan + $penyesuaian;

      // Update the record
      $detail->update([
        'total_lembur' => $totalLembur,
        'penghasilan_bruto' => $penghasilanBruto,
        'potongan_alfa' => $potonganAlfa,
        'potongan_terlambat' => $potonganTerlambat,
        'potongan_bpjs' => $potonganBpjs,
        'potongan_pph21' => $potonganPph21,
        'penyesuaian' => $penyesuaian,
        'catatan_penyesuaian' => $data['catatan_penyesuaian'] ?? null,
        'total_potongan' => $totalPotongan,
        'gaji_bersih' => max(0, $gajiBersih), // Prevent negative salary
      ]);

      Notification::make()
        ->title('Berhasil!')
        ->body('Data gaji karyawan berhasil diperbarui.')
        ->success()
        ->send();

      // Refresh the page to show updated data
      redirect()->refresh();

    } catch (\Exception $e) {
      Log::error('Error updating detail penggajian: ' . $e->getMessage());

      Notification::make()
        ->title('Error')
        ->body('Terjadi kesalahan saat memperbarui data: ' . $e->getMessage())
        ->danger()
        ->send();
    }
  }
}
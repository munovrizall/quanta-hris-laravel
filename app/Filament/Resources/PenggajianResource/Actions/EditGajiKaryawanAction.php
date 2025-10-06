<?php

namespace App\Filament\Resources\PenggajianResource\Actions;

use App\Models\Penggajian;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
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
        Forms\Components\Hidden::make('penggajian_id'),

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
            Forms\Components\TextInput::make('gaji_pokok_display')
              ->label('Gaji Pokok')
              ->disabled()
              ->dehydrated(false)
              ->prefix('Rp'),

            Forms\Components\TextInput::make('total_tunjangan_display')
              ->label('Total Tunjangan')
              ->disabled()
              ->dehydrated(false)
              ->prefix('Rp'),

            Forms\Components\TextInput::make('total_lembur')
              ->label('Upah Lembur')
              ->numeric()
              ->prefix('Rp')
              ->step(1)
              ->minValue(0)
              ->extraAttributes([
                'data-field' => 'total_lembur',
                'onchange' => 'recalculateClientSide()'
              ]),

            Forms\Components\TextInput::make('penghasilan_bruto_display')
              ->label('Penghasilan Bruto')
              ->disabled()
              ->dehydrated(false)
              ->prefix('Rp'),
          ])
          ->columns(2),

        Forms\Components\Section::make('Potongan')
          ->schema([
            Forms\Components\TextInput::make('potongan_alfa')
              ->label('Potongan Alfa')
              ->numeric()
              ->prefix('Rp')
              ->step(1)
              ->minValue(0)
              ->extraAttributes([
                'data-field' => 'potongan_alfa',
                'onchange' => 'recalculateClientSide()'
              ]),

            Forms\Components\TextInput::make('potongan_terlambat')
              ->label('Potongan Terlambat')
              ->numeric()
              ->prefix('Rp')
              ->step(1)
              ->minValue(0)
              ->extraAttributes([
                'data-field' => 'potongan_terlambat',
                'onchange' => 'recalculateClientSide()'
              ]),

            Forms\Components\TextInput::make('potongan_bpjs')
              ->label('Potongan BPJS')
              ->numeric()
              ->prefix('Rp')
              ->step(1)
              ->minValue(0)
              ->extraAttributes([
                'data-field' => 'potongan_bpjs',
                'onchange' => 'recalculateClientSide()'
              ]),

            Forms\Components\TextInput::make('potongan_pph21')
              ->label('PPh21')
              ->numeric()
              ->prefix('Rp')
              ->step(1)
              ->minValue(0)
              ->extraAttributes([
                'data-field' => 'potongan_pph21',
                'onchange' => 'recalculateClientSide()'
              ]),
          ])
          ->columns(2),

        Forms\Components\Section::make('Penyesuaian')
          ->schema([
            Forms\Components\TextInput::make('penyesuaian')
              ->label('Penyesuaian')
              ->numeric()
              ->prefix('Rp')
              ->step(1)
              ->helperText('Masukkan nilai positif untuk penambahan, negatif untuk pengurangan')
              ->extraAttributes([
                'data-field' => 'penyesuaian',
                'onchange' => 'recalculateClientSide()'
              ]),

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
            Forms\Components\TextInput::make('total_potongan_display')
              ->label('Total Potongan')
              ->disabled()
              ->dehydrated(false)
              ->prefix('Rp'),

            Forms\Components\TextInput::make('gaji_bersih_display')
              ->label('Gaji Bersih')
              ->disabled()
              ->dehydrated(false)
              ->prefix('Rp')
              ->extraAttributes([
                'style' => 'font-weight: bold; color: #059669;'
              ]),
          ])
          ->columns(2),

        // Hidden fields to store actual calculation values
        Forms\Components\Hidden::make('gaji_pokok'),
        Forms\Components\Hidden::make('total_tunjangan'),
        Forms\Components\Hidden::make('penghasilan_bruto'),
        Forms\Components\Hidden::make('total_potongan'),
        Forms\Components\Hidden::make('gaji_bersih'),

        // Client-side calculator
        Forms\Components\Placeholder::make('calculator')
          ->label('')
          ->content(new \Illuminate\Support\HtmlString('
                        <script>
                        function recalculateClientSide() {
                            setTimeout(() => {
                                const gajiPokok = parseFloat(document.querySelector("input[name=\\"gaji_pokok\\"]")?.value || 0);
                                const totalTunjangan = parseFloat(document.querySelector("input[name=\\"total_tunjangan\\"]")?.value || 0);
                                const totalLembur = parseFloat(document.querySelector("input[name=\\"total_lembur\\"]")?.value || 0);
                                
                                const potonganAlfa = parseFloat(document.querySelector("input[name=\\"potongan_alfa\\"]")?.value || 0);
                                const potonganTerlambat = parseFloat(document.querySelector("input[name=\\"potongan_terlambat\\"]")?.value || 0);
                                const potonganBpjs = parseFloat(document.querySelector("input[name=\\"potongan_bpjs\\"]")?.value || 0);
                                const potonganPph21 = parseFloat(document.querySelector("input[name=\\"potongan_pph21\\"]")?.value || 0);
                                const penyesuaian = parseFloat(document.querySelector("input[name=\\"penyesuaian\\"]")?.value || 0);
                                
                                const penghasilanBruto = gajiPokok + totalTunjangan + totalLembur;
                                const totalPotongan = potonganAlfa + potonganTerlambat + potonganBpjs + potonganPph21;
                                const gajiBersih = penghasilanBruto - totalPotongan + penyesuaian;
                                
                                // Update display fields
                                const penghasilanBrutoDisplay = document.querySelector("input[name=\\"penghasilan_bruto_display\\"]");
                                const totalPotonganDisplay = document.querySelector("input[name=\\"total_potongan_display\\"]");
                                const gajiBersihDisplay = document.querySelector("input[name=\\"gaji_bersih_display\\"]");
                                
                                if (penghasilanBrutoDisplay) penghasilanBrutoDisplay.value = formatNumber(penghasilanBruto);
                                if (totalPotonganDisplay) totalPotonganDisplay.value = formatNumber(totalPotongan);
                                if (gajiBersihDisplay) gajiBersihDisplay.value = formatNumber(Math.max(0, gajiBersih));
                                
                                // Update hidden fields for submission
                                const penghasilanBrutoHidden = document.querySelector("input[name=\\"penghasilan_bruto\\"]");
                                const totalPotonganHidden = document.querySelector("input[name=\\"total_potongan\\"]");
                                const gajiBersihHidden = document.querySelector("input[name=\\"gaji_bersih\\"]");
                                
                                if (penghasilanBrutoHidden) penghasilanBrutoHidden.value = penghasilanBruto;
                                if (totalPotonganHidden) totalPotonganHidden.value = totalPotongan;
                                if (gajiBersihHidden) gajiBersihHidden.value = Math.max(0, gajiBersih);
                                
                            }, 100);
                        }
                        
                        function formatNumber(num) {
                            if (isNaN(num) || num === null || num === undefined) return "0";
                            return Math.round(num).toLocaleString("id-ID");
                        }
                        
                        document.addEventListener("DOMContentLoaded", function() {
                            setTimeout(recalculateClientSide, 1000);
                        });
                        </script>
                    ')),
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

        $detail = Penggajian::query()
          ->with(['karyawan'])
          ->where('penggajian_id', $detailId)
          ->first();

        if (!$detail) {
          Notification::make()
            ->title('Error')
            ->body('Data detail penggajian tidak ditemukan.')
            ->danger()
            ->send();
          return [];
        }

        // Debug log to check raw values from database
        Log::info('Raw Detail Penggajian Data:', [
          'periode_bulan' => $detail->periode_bulan,
          'periode_tahun' => $detail->periode_tahun,
          'penggajian_id' => $detail->penggajian_id,
          'gaji_pokok_raw' => $detail->getAttributes()['gaji_pokok'],
          'total_tunjangan_raw' => $detail->getAttributes()['total_tunjangan'],
          'total_lembur_raw' => $detail->getAttributes()['total_lembur'],
          'penghasilan_bruto_raw' => $detail->getAttributes()['penghasilan_bruto'],
          'potongan_alfa_raw' => $detail->getAttributes()['potongan_alfa'],
          'potongan_terlambat_raw' => $detail->getAttributes()['potongan_terlambat'],
          'potongan_bpjs_raw' => $detail->getAttributes()['potongan_bpjs'],
          'potongan_pph21_raw' => $detail->getAttributes()['potongan_pph21'],
          'penyesuaian_raw' => $detail->getAttributes()['penyesuaian'],
          'total_potongan_raw' => $detail->getAttributes()['total_potongan'],
          'gaji_bersih_raw' => $detail->getAttributes()['gaji_bersih'],
        ]);

        // Convert to floats and ensure values
        $gajiPokok = (float) ($detail->gaji_pokok ?? 0);
        $totalTunjangan = (float) ($detail->total_tunjangan ?? 0);
        $totalLembur = (float) ($detail->total_lembur ?? 0);
        $penghasilanBruto = (float) ($detail->penghasilan_bruto ?? 0);
        $potonganAlfa = (float) ($detail->potongan_alfa ?? 0);
        $potonganTerlambat = (float) ($detail->potongan_terlambat ?? 0);
        $potonganBpjs = (float) ($detail->potongan_bpjs ?? 0);
        $potonganPph21 = (float) ($detail->potongan_pph21 ?? 0);
        $penyesuaian = (float) ($detail->penyesuaian ?? 0);
        $totalPotongan = (float) ($detail->total_potongan ?? 0);
        $gajiBersih = (float) ($detail->gaji_bersih ?? 0);

        return [
          'penggajian_id' => $detail->penggajian_id,
          'karyawan_id' => $detail->karyawan_id,
          'nama_karyawan' => $detail->karyawan->nama_lengkap ?? 'N/A',

          // Actual values for calculation (hidden fields)
          'gaji_pokok' => $gajiPokok,
          'total_tunjangan' => $totalTunjangan,
          'penghasilan_bruto' => $penghasilanBruto,
          'total_potongan' => $totalPotongan,
          'gaji_bersih' => $gajiBersih,

          // Display values (formatted)
          'gaji_pokok_display' => number_format($gajiPokok, 0, ',', '.'),
          'total_tunjangan_display' => number_format($totalTunjangan, 0, ',', '.'),
          'penghasilan_bruto_display' => number_format($penghasilanBruto, 0, ',', '.'),
          'total_potongan_display' => number_format($totalPotongan, 0, ',', '.'),
          'gaji_bersih_display' => number_format($gajiBersih, 0, ',', '.'),

          // Editable fields
          'total_lembur' => $totalLembur,
          'potongan_alfa' => $potonganAlfa,
          'potongan_terlambat' => $potonganTerlambat,
          'potongan_bpjs' => $potonganBpjs,
          'potongan_pph21' => $potonganPph21,
          'penyesuaian' => $penyesuaian,
          'catatan_penyesuaian' => $detail->catatan_penyesuaian ?? '',
        ];
      })
      ->action(function (array $data): void {
        static::handleSave($data);
      })
      ->modalHeading('Edit Gaji Karyawan')
      ->modalSubmitActionLabel('Simpan Perubahan')
      ->modalCancelActionLabel('Batal')
      ->modalWidth('4xl')
      ->modalSubmitAction(fn ($action) => $action->color('cyan'))
      ->modalFooterActionsAlignment(Alignment::End);
  }

  /**
   * Handle form submission
   */
  protected static function handleSave(array $data): void
  {
    try {
      $detail = Penggajian::query()
        ->where('penggajian_id', $data['penggajian_id'])
        ->first();

      if (!$detail) {
        Notification::make()
          ->title('Error')
          ->body('Data detail penggajian tidak ditemukan.')
          ->danger()
          ->send();
        return;
      }

      // Get original values
      $gajiPokok = (float) $detail->gaji_pokok;
      $totalTunjangan = (float) $detail->total_tunjangan;

      // Get new values from form
      $totalLembur = (float) ($data['total_lembur'] ?? 0);
      $potonganAlfa = (float) ($data['potongan_alfa'] ?? 0);
      $potonganTerlambat = (float) ($data['potongan_terlambat'] ?? 0);
      $potonganBpjs = (float) ($data['potongan_bpjs'] ?? 0);
      $potonganPph21 = (float) ($data['potongan_pph21'] ?? 0);
      $penyesuaian = (float) ($data['penyesuaian'] ?? 0);

      // Calculate totals
      $penghasilanBruto = $gajiPokok + $totalTunjangan + $totalLembur;
      $totalPotongan = $potonganAlfa + $potonganTerlambat + $potonganBpjs + $potonganPph21;
      $gajiBersih = $penghasilanBruto - $totalPotongan + $penyesuaian;

      Log::info('Saving EditGaji with calculated values:', [
        'detail_id' => $detail->penggajian_id,
        'original_gaji_bersih' => $detail->gaji_bersih,
        'new_penghasilan_bruto' => $penghasilanBruto,
        'new_total_potongan' => $totalPotongan,
        'new_gaji_bersih' => $gajiBersih,
        'form_data' => $data
      ]);

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

      // Don't use redirect() - let Filament handle the modal closing
      // Instead, we can emit an event or just let it close naturally

    } catch (\Exception $e) {
      Log::error('Error updating detail penggajian: ' . $e->getMessage(), [
        'data' => $data,
        'trace' => $e->getTraceAsString()
      ]);

      Notification::make()
        ->title('Error')
        ->body('Terjadi kesalahan saat memperbarui data: ' . $e->getMessage())
        ->danger()
        ->send();
    }
  }
}

<?php

use App\Http\Controllers\LaporanKeuanganController;
use App\Http\Controllers\LaporanKinerjaController;
use App\Http\Controllers\RekapitulasiAbsensiController;
use App\Http\Controllers\SlipGajiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Print routes expose payroll & PII, so they require the same permission as the Filament page that links to them.
Route::middleware('auth')->group(function () {
    Route::get('/slip-gaji/cetak/{tahun}/{bulan}', [SlipGajiController::class, 'cetakSemuaSlipGaji'])
        ->middleware('can:view_penggajian')
        ->name('slip-gaji.cetak');

    Route::get('/slip-gaji/cetak-individual/{karyawan_id}/{tahun}/{bulan}', [SlipGajiController::class, 'cetakSlipGajiIndividual'])
        ->middleware('can:view_penggajian')
        ->name('slip-gaji.cetak-individual');

    Route::get('/laporan-keuangan/cetak/{tahun}/{bulan}', [LaporanKeuanganController::class, 'cetak'])
        ->middleware('can:view_laporan_keuangan')
        ->name('laporan-keuangan.cetak');

    Route::get('/laporan-kinerja/cetak/{tahun}/{bulan}', [LaporanKinerjaController::class, 'cetak'])
        ->middleware('can:view_laporan_kinerja')
        ->name('laporan-kinerja.cetak');

    Route::get('/rekapitulasi-absensi/cetak', [RekapitulasiAbsensiController::class, 'cetak'])
        ->middleware('can:view_any_absensi')
        ->name('rekapitulasi-absensi.pdf');
});

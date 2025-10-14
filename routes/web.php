<?php

use App\Http\Controllers\SlipGajiController;
use App\Http\Controllers\LaporanKeuanganController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/slip-gaji/cetak/{tahun}/{bulan}', [SlipGajiController::class, 'cetakSemuaSlipGaji'])
    ->name('slip-gaji.cetak');

Route::get('/slip-gaji/cetak-individual/{karyawan_id}/{tahun}/{bulan}', [SlipGajiController::class, 'cetakSlipGajiIndividual'])
    ->name('slip-gaji.cetak-individual');

Route::get('/laporan-keuangan/cetak/{tahun}/{bulan}', [LaporanKeuanganController::class, 'cetak'])
    ->name('laporan-keuangan.cetak');

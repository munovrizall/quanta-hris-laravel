<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/slip-gaji/cetak/{tahun}/{bulan}', [PenggajianCon::class, 'cetakSemuaSlipGaji'])
    ->name('slip-gaji.cetak');

Route::get('/slip-gaji/cetak-individual/{karyawan_id}/{tahun}/{bulan}', [SlipGajiController::class, 'cetakSlipGajiIndividual'])
    ->name('slip-gaji.cetak-individual');

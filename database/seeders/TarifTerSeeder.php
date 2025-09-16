<?php

namespace Database\Seeders;

use App\Models\TarifTer;
use Illuminate\Database\Seeder;

class TarifTerSeeder extends Seeder
{
  /**
   * Run the database seeds.
   * Data tarif efektif rata-rata (TER) berdasarkan PMK No. 168 Tahun 2023.
   */
  public function run(): void
  {
    // Data untuk Kategori A (TK/0, TK/1, K/0)
    $ratesA = [
      ['batas_bawah' => 0, 'batas_atas' => 5400000, 'tarif' => 0.0000],
      ['batas_bawah' => 5400001, 'batas_atas' => 5650000, 'tarif' => 0.0025],
      ['batas_bawah' => 5650001, 'batas_atas' => 5950000, 'tarif' => 0.0050],
      ['batas_bawah' => 5950001, 'batas_atas' => 6300000, 'tarif' => 0.0075],
      ['batas_bawah' => 6300001, 'batas_atas' => 6750000, 'tarif' => 0.0100],
      ['batas_bawah' => 6750001, 'batas_atas' => 7500000, 'tarif' => 0.0125],
      ['batas_bawah' => 7500001, 'batas_atas' => 8550000, 'tarif' => 0.0150],
      ['batas_bawah' => 8550001, 'batas_atas' => 9650000, 'tarif' => 0.0175],
      ['batas_bawah' => 9650001, 'batas_atas' => 10050000, 'tarif' => 0.0200],
      ['batas_bawah' => 10050001, 'batas_atas' => 10350000, 'tarif' => 0.0225],
      ['batas_bawah' => 10350001, 'batas_atas' => 10700000, 'tarif' => 0.0250],
      ['batas_bawah' => 10700001, 'batas_atas' => 11050000, 'tarif' => 0.0300],
      ['batas_bawah' => 11050001, 'batas_atas' => 11600000, 'tarif' => 0.0350],
      ['batas_bawah' => 11600001, 'batas_atas' => 12500000, 'tarif' => 0.0400],
      ['batas_bawah' => 12500001, 'batas_atas' => 13750000, 'tarif' => 0.0500],
      ['batas_bawah' => 13750001, 'batas_atas' => 15100000, 'tarif' => 0.0600],
      ['batas_bawah' => 15100001, 'batas_atas' => 16950000, 'tarif' => 0.0700],
      ['batas_bawah' => 16950001, 'batas_atas' => 19750000, 'tarif' => 0.0800],
      ['batas_bawah' => 19750001, 'batas_atas' => 24150000, 'tarif' => 0.0900],
      ['batas_bawah' => 24150001, 'batas_atas' => 26450000, 'tarif' => 0.1000],
      ['batas_bawah' => 26450001, 'batas_atas' => 28000000, 'tarif' => 0.1100],
      ['batas_bawah' => 28000001, 'batas_atas' => 30050000, 'tarif' => 0.1200],
      ['batas_bawah' => 30050001, 'batas_atas' => 32400000, 'tarif' => 0.1300],
      ['batas_bawah' => 32400001, 'batas_atas' => 35400000, 'tarif' => 0.1400],
      ['batas_bawah' => 35400001, 'batas_atas' => 39100000, 'tarif' => 0.1500],
      ['batas_bawah' => 39100001, 'batas_atas' => 43850000, 'tarif' => 0.1600],
      ['batas_bawah' => 43850001, 'batas_atas' => 47800000, 'tarif' => 0.1700],
      ['batas_bawah' => 47800001, 'batas_atas' => 51400000, 'tarif' => 0.1800],
      ['batas_bawah' => 51400001, 'batas_atas' => 56300000, 'tarif' => 0.1900],
      ['batas_bawah' => 56300001, 'batas_atas' => 62200000, 'tarif' => 0.2000],
      ['batas_bawah' => 62200001, 'batas_atas' => 68600000, 'tarif' => 0.2100],
      ['batas_bawah' => 68600001, 'batas_atas' => 77500000, 'tarif' => 0.2200],
      ['batas_bawah' => 77500001, 'batas_atas' => 89000000, 'tarif' => 0.2300],
      ['batas_bawah' => 89000001, 'batas_atas' => 103000000, 'tarif' => 0.2400],
      ['batas_bawah' => 103000001, 'batas_atas' => 125000000, 'tarif' => 0.2500],
      ['batas_bawah' => 125000001, 'batas_atas' => 157000000, 'tarif' => 0.2600],
      ['batas_bawah' => 157000001, 'batas_atas' => 206000000, 'tarif' => 0.2700],
      ['batas_bawah' => 206000001, 'batas_atas' => 337000000, 'tarif' => 0.2800],
      ['batas_bawah' => 337000001, 'batas_atas' => 454000000, 'tarif' => 0.2900],
      ['batas_bawah' => 454000001, 'batas_atas' => 550000000, 'tarif' => 0.3000],
      ['batas_bawah' => 550000001, 'batas_atas' => 695000000, 'tarif' => 0.3100],
      ['batas_bawah' => 695000001, 'batas_atas' => 910000000, 'tarif' => 0.3200],
      ['batas_bawah' => 910000001, 'batas_atas' => 1400000000, 'tarif' => 0.3300],
      ['batas_bawah' => 1400000001, 'batas_atas' => PHP_INT_MAX, 'tarif' => 0.3400],
    ];

    // Data untuk Kategori B (TK/2, K/1, TK/3, K/2)
    $ratesB = [
      ['batas_bawah' => 0, 'batas_atas' => 6200000, 'tarif' => 0.0000],
      ['batas_bawah' => 6200001, 'batas_atas' => 6500000, 'tarif' => 0.0025],
      ['batas_bawah' => 6500001, 'batas_atas' => 6850000, 'tarif' => 0.0050],
      ['batas_bawah' => 6850001, 'batas_atas' => 7300000, 'tarif' => 0.0075],
      ['batas_bawah' => 7300001, 'batas_atas' => 9200000, 'tarif' => 0.0100],
      ['batas_bawah' => 9200001, 'batas_atas' => 10750000, 'tarif' => 0.0150],
      ['batas_bawah' => 10750001, 'batas_atas' => 11250000, 'tarif' => 0.0200],
      ['batas_bawah' => 11250001, 'batas_atas' => 11600000, 'tarif' => 0.0250],
      ['batas_bawah' => 11600001, 'batas_atas' => 12600000, 'tarif' => 0.0300],
      ['batas_bawah' => 12600001, 'batas_atas' => 13600000, 'tarif' => 0.0400],
      ['batas_bawah' => 13600001, 'batas_atas' => 14950000, 'tarif' => 0.0500],
      ['batas_bawah' => 14950001, 'batas_atas' => 16400000, 'tarif' => 0.0600],
      ['batas_bawah' => 16400001, 'batas_atas' => 18450000, 'tarif' => 0.0700],
      ['batas_bawah' => 18450001, 'batas_atas' => 21850000, 'tarif' => 0.0800],
      ['batas_bawah' => 21850001, 'batas_atas' => 26000000, 'tarif' => 0.0900],
      ['batas_bawah' => 26000001, 'batas_atas' => 27700000, 'tarif' => 0.1000],
      ['batas_bawah' => 27700001, 'batas_atas' => 29350000, 'tarif' => 0.1100],
      ['batas_bawah' => 29350001, 'batas_atas' => 31450000, 'tarif' => 0.1200],
      ['batas_bawah' => 31450001, 'batas_atas' => 33950000, 'tarif' => 0.1300],
      ['batas_bawah' => 33950001, 'batas_atas' => 37100000, 'tarif' => 0.1400],
      ['batas_bawah' => 37100001, 'batas_atas' => 41100000, 'tarif' => 0.1500],
      ['batas_bawah' => 41100001, 'batas_atas' => 45800000, 'tarif' => 0.1600],
      ['batas_bawah' => 45800001, 'batas_atas' => 49500000, 'tarif' => 0.1700],
      ['batas_bawah' => 49500001, 'batas_atas' => 53800000, 'tarif' => 0.1800],
      ['batas_bawah' => 53800001, 'batas_atas' => 58500000, 'tarif' => 0.1900],
      ['batas_bawah' => 58500001, 'batas_atas' => 64000000, 'tarif' => 0.2000],
      ['batas_bawah' => 64000001, 'batas_atas' => 71000000, 'tarif' => 0.2100],
      ['batas_bawah' => 71000001, 'batas_atas' => 80000000, 'tarif' => 0.2200],
      ['batas_bawah' => 80000001, 'batas_atas' => 93000000, 'tarif' => 0.2300],
      ['batas_bawah' => 93000001, 'batas_atas' => 109000000, 'tarif' => 0.2400],
      ['batas_bawah' => 109000001, 'batas_atas' => 129000000, 'tarif' => 0.2500],
      ['batas_bawah' => 129000001, 'batas_atas' => 163000000, 'tarif' => 0.2600],
      ['batas_bawah' => 163000001, 'batas_atas' => 211000000, 'tarif' => 0.2700],
      ['batas_bawah' => 211000001, 'batas_atas' => 374000000, 'tarif' => 0.2800],
      ['batas_bawah' => 374000001, 'batas_atas' => 459000000, 'tarif' => 0.2900],
      ['batas_bawah' => 459000001, 'batas_atas' => 555000000, 'tarif' => 0.3000],
      ['batas_bawah' => 555000001, 'batas_atas' => 704000000, 'tarif' => 0.3100],
      ['batas_bawah' => 704000001, 'batas_atas' => 957000000, 'tarif' => 0.3200],
      ['batas_bawah' => 957000001, 'batas_atas' => 1405000000, 'tarif' => 0.3300],
      ['batas_bawah' => 1405000001, 'batas_atas' => PHP_INT_MAX, 'tarif' => 0.3400],
    ];

    // Data untuk Kategori C (K/3)
    $ratesC = [
      ['batas_bawah' => 0, 'batas_atas' => 6600000, 'tarif' => 0.0000],
      ['batas_bawah' => 6600001, 'batas_atas' => 6950000, 'tarif' => 0.0025],
      ['batas_bawah' => 6950001, 'batas_atas' => 7350000, 'tarif' => 0.0050],
      ['batas_bawah' => 7350001, 'batas_atas' => 7800000, 'tarif' => 0.0075],
      ['batas_bawah' => 7800001, 'batas_atas' => 8850000, 'tarif' => 0.0100],
      ['batas_bawah' => 8850001, 'batas_atas' => 9800000, 'tarif' => 0.0125],
      ['batas_bawah' => 9800001, 'batas_atas' => 10950000, 'tarif' => 0.0150],
      ['batas_bawah' => 10950001, 'batas_atas' => 11200000, 'tarif' => 0.0175],
      ['batas_bawah' => 11200001, 'batas_atas' => 12050000, 'tarif' => 0.0200],
      ['batas_bawah' => 12050001, 'batas_atas' => 12950000, 'tarif' => 0.0300],
      ['batas_bawah' => 12950001, 'batas_atas' => 14150000, 'tarif' => 0.0400],
      ['batas_bawah' => 14150001, 'batas_atas' => 15550000, 'tarif' => 0.0500],
      ['batas_bawah' => 15550001, 'batas_atas' => 17050000, 'tarif' => 0.0600],
      ['batas_bawah' => 17050001, 'batas_atas' => 19500000, 'tarif' => 0.0700],
      ['batas_bawah' => 19500001, 'batas_atas' => 22700000, 'tarif' => 0.0800],
      ['batas_bawah' => 22700001, 'batas_atas' => 26600000, 'tarif' => 0.0900],
      ['batas_bawah' => 26600001, 'batas_atas' => 28100000, 'tarif' => 0.1000],
      ['batas_bawah' => 28100001, 'batas_atas' => 30100000, 'tarif' => 0.1100],
      ['batas_bawah' => 30100001, 'batas_atas' => 32600000, 'tarif' => 0.1200],
      ['batas_bawah' => 32600001, 'batas_atas' => 35400000, 'tarif' => 0.1300],
      ['batas_bawah' => 35400001, 'batas_atas' => 38900000, 'tarif' => 0.1400],
      ['batas_bawah' => 38900001, 'batas_atas' => 43000000, 'tarif' => 0.1500],
      ['batas_bawah' => 43000001, 'batas_atas' => 47400000, 'tarif' => 0.1600],
      ['batas_bawah' => 47400001, 'batas_atas' => 51200000, 'tarif' => 0.1700],
      ['batas_bawah' => 51200001, 'batas_atas' => 55800000, 'tarif' => 0.1800],
      ['batas_bawah' => 55800001, 'batas_atas' => 60400000, 'tarif' => 0.1900],
      ['batas_bawah' => 60400001, 'batas_atas' => 66700000, 'tarif' => 0.2000],
      ['batas_bawah' => 66700001, 'batas_atas' => 74500000, 'tarif' => 0.2100],
      ['batas_bawah' => 74500001, 'batas_atas' => 83200000, 'tarif' => 0.2200],
      ['batas_bawah' => 83200001, 'batas_atas' => 95600000, 'tarif' => 0.2300],
      ['batas_bawah' => 95600001, 'batas_atas' => 110000000, 'tarif' => 0.2400],
      ['batas_bawah' => 110000001, 'batas_atas' => 134000000, 'tarif' => 0.2500],
      ['batas_bawah' => 134000001, 'batas_atas' => 169000000, 'tarif' => 0.2600],
      ['batas_bawah' => 169000001, 'batas_atas' => 221000000, 'tarif' => 0.2700],
      ['batas_bawah' => 221000001, 'batas_atas' => 390000000, 'tarif' => 0.2800],
      ['batas_bawah' => 390000001, 'batas_atas' => 463000000, 'tarif' => 0.2900],
      ['batas_bawah' => 463000001, 'batas_atas' => 561000000, 'tarif' => 0.3000],
      ['batas_bawah' => 561000001, 'batas_atas' => 709000000, 'tarif' => 0.3100],
      ['batas_bawah' => 709000001, 'batas_atas' => 965000000, 'tarif' => 0.3200],
      ['batas_bawah' => 965000001, 'batas_atas' => 1419000000, 'tarif' => 0.3300],
      ['batas_bawah' => 1419000001, 'batas_atas' => PHP_INT_MAX, 'tarif' => 0.3400],
    ];

    // Mengosongkan tabel sebelum mengisi untuk menghindari duplikasi
    TarifTer::query()->truncate();

    $counter = 1;

    // Loop untuk mengisi data Kategori A
    foreach ($ratesA as $rate) {
      TarifTer::create([
        'tarif_ter_id' => 'T' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
        'kategori_ter_id' => 'K01', // ID untuk Kategori A
        'batas_bawah' => $rate['batas_bawah'],
        'batas_atas' => $rate['batas_atas'],
        'tarif' => $rate['tarif'],
      ]);
    }

    // Loop untuk mengisi data Kategori B
    foreach ($ratesB as $rate) {
      TarifTer::create([
        'tarif_ter_id' => 'T' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
        'kategori_ter_id' => 'K02', // ID untuk Kategori B
        'batas_bawah' => $rate['batas_bawah'],
        'batas_atas' => $rate['batas_atas'],
        'tarif' => $rate['tarif'],
      ]);
    }

    // Loop untuk mengisi data Kategori C
    foreach ($ratesC as $rate) {
      TarifTer::create([
        'tarif_ter_id' => 'T' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
        'kategori_ter_id' => 'K03', // ID untuk Kategori C
        'batas_bawah' => $rate['batas_bawah'],
        'batas_atas' => $rate['batas_atas'],
        'tarif' => $rate['tarif'],
      ]);
    }
  }
}

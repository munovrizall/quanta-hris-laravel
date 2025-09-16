<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GolonganPtkpSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('golongan_ptkp')->insert([
            ['golongan_ptkp_id' => 'G01', 'nama_golongan_ptkp' => 'TK/0', 'deskripsi' => 'Tidak Kawin, tanpa tanggungan', 'ptkp_tahunan' => 54000000, 'kategori_ter_id' => 'K01'],
            ['golongan_ptkp_id' => 'G02', 'nama_golongan_ptkp' => 'TK/1', 'deskripsi' => 'Tidak Kawin, 1 tanggungan', 'ptkp_tahunan' => 58500000, 'kategori_ter_id' => 'K01'],
            ['golongan_ptkp_id' => 'G03', 'nama_golongan_ptkp' => 'TK/2', 'deskripsi' => 'Tidak Kawin, 2 tanggungan', 'ptkp_tahunan' => 63000000, 'kategori_ter_id' => 'K02'],
            ['golongan_ptkp_id' => 'G04', 'nama_golongan_ptkp' => 'TK/3', 'deskripsi' => 'Tidak Kawin, 3 tanggungan', 'ptkp_tahunan' => 67500000, 'kategori_ter_id' => 'K02'],
            ['golongan_ptkp_id' => 'G05', 'nama_golongan_ptkp' => 'K/0', 'deskripsi' => 'Kawin, tanpa tanggungan', 'ptkp_tahunan' => 58500000, 'kategori_ter_id' => 'K01'],
            ['golongan_ptkp_id' => 'G06', 'nama_golongan_ptkp' => 'K/1', 'deskripsi' => 'Kawin, 1 tanggungan', 'ptkp_tahunan' => 63000000, 'kategori_ter_id' => 'K02'],
            ['golongan_ptkp_id' => 'G07', 'nama_golongan_ptkp' => 'K/2', 'deskripsi' => 'Kawin, 2 tanggungan', 'ptkp_tahunan' => 67500000, 'kategori_ter_id' => 'K02'],
            ['golongan_ptkp_id' => 'G08', 'nama_golongan_ptkp' => 'K/3', 'deskripsi' => 'Kawin, 3 tanggungan', 'ptkp_tahunan' => 72000000, 'kategori_ter_id' => 'K03'],
        ]);
    }
}
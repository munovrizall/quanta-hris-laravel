<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriTerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('kategori_ter')->insert([
            ['kategori_ter_id' => 'K01', 'nama_kategori' => 'Kategori A', 'deskripsi' => 'PTKP TK/0, K/0, TK/1'],
            ['kategori_ter_id' => 'K02', 'nama_kategori' => 'Kategori B', 'deskripsi' => 'PTKP TK/2, K/1, TK/3, K/2'],
            ['kategori_ter_id' => 'K03', 'nama_kategori' => 'Kategori C', 'deskripsi' => 'PTKP K/3'],
        ]);
    }
}
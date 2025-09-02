<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GolonganPtkp;

class GolonganPtkpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $golongan = [
            ['golongan_ptkp_id' => 'G01', 'nama_golongan_ptkp' => 'TK/0'],
            ['golongan_ptkp_id' => 'G02', 'nama_golongan_ptkp' => 'K/0'],
            ['golongan_ptkp_id' => 'G03', 'nama_golongan_ptkp' => 'K/1'],
            ['golongan_ptkp_id' => 'G04', 'nama_golongan_ptkp' => 'K/2'],
            ['golongan_ptkp_id' => 'G05', 'nama_golongan_ptkp' => 'K/3'],
        ];

        foreach ($golongan as $data) {
            GolonganPtkp::updateOrCreate(['golongan_ptkp_id' => $data['golongan_ptkp_id']], $data);
        }
    }
}
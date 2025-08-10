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
            ['golongan_ptkp_id' => 'TK0', 'nama_golongan_ptkp' => 'TK/0'],
            ['golongan_ptkp_id' => 'K0', 'nama_golongan_ptkp' => 'K/0'],
            ['golongan_ptkp_id' => 'K1', 'nama_golongan_ptkp' => 'K/1'],
            ['golongan_ptkp_id' => 'K2', 'nama_golongan_ptkp' => 'K/2'],
            ['golongan_ptkp_id' => 'K3', 'nama_golongan_ptkp' => 'K/3'],
        ];

        foreach ($golongan as $data) {
            GolonganPtkp::updateOrCreate(['golongan_ptkp_id' => $data['golongan_ptkp_id']], $data);
        }
    }
}
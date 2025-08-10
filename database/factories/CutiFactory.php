<?php

namespace Database\Factories;

use App\Models\Cuti;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class CutiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Cuti::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Seeder akan menyediakan cuti_id, karyawan_id, dan data approval.
        $tanggalMulai = Carbon::instance($this->faker->dateTimeBetween('-1 month', '+1 month'));
        $tanggalSelesai = $tanggalMulai->copy()->addDays($this->faker->numberBetween(1, 5));

        return [
            'jenis_cuti' => $this->faker->randomElement(['Cuti Tahunan', 'Cuti Sakit']),
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'keterangan' => $this->faker->sentence,
            'status_cuti' => 'Diajukan', // Default state
        ];
    }
}
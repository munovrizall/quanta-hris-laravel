<?php

namespace Database\Factories;

use App\Models\Izin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class IzinFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Izin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Seeder akan menyediakan izin_id, karyawan_id, dan data approval.
        $tanggalMulai = Carbon::instance($this->faker->dateTimeBetween('-1 month', '+1 month'));
        $tanggalSelesai = $tanggalMulai->copy()->addDays($this->faker->numberBetween(0, 2));

        return [
            'jenis_izin' => $this->faker->randomElement(['Izin Sakit', 'Izin Keperluan Pribadi']),
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
            'keterangan' => $this->faker->sentence,
            'status_izin' => 'Diajukan', // Default state
        ];
    }
}
<?php

namespace Database\Factories;

use App\Models\Absensi;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AbsensiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Absensi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Seeder akan menyediakan absensi_id, karyawan_id, cabang_id, dan tanggal.
        // Factory ini mengisi sisa data dummy.
        $waktuMasuk = Carbon::now()->setHour(8)->setMinute($this->faker->numberBetween(0, 30));
        $waktuPulang = Carbon::now()->setHour(17)->setMinute($this->faker->numberBetween(0, 59));

        return [
            'waktu_masuk' => $waktuMasuk,
            'waktu_pulang' => $waktuPulang,
            'status_masuk' => $this->faker->randomElement(['Tepat Waktu', 'Telat']),
            'status_pulang' => $this->faker->randomElement(['Tepat Waktu', 'Pulang Cepat']),
            'koordinat_masuk' => $this->faker->latitude() . ',' . $this->faker->longitude(),
            'koordinat_pulang' => $this->faker->latitude() . ',' . $this->faker->longitude(),
            'foto_masuk' => $this->faker->imageUrl(640, 480, 'people', true),
            'foto_pulang' => $this->faker->imageUrl(640, 480, 'people', true),
            'status_absensi' => 'Hadir', // Default ke Hadir untuk data dummy
        ];
    }
}
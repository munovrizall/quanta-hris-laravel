<?php

namespace Database\Factories;

use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

class KaryawanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Karyawan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Seeder akan menyediakan ID kustom, role, perusahaan, dan ptkp.
        // Factory ini menyediakan data dummy untuk kolom sisanya.
        return [
            'nik' => $this->faker->unique()->numerify('3171##############'),
            'nama_lengkap' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => bcrypt('password'), 
            'tanggal_lahir' => $this->faker->date(),
            'jenis_kelamin' => $this->faker->randomElement(['Laki-laki', 'Perempuan']),
            'alamat' => $this->faker->address(),
            'nomor_telepon' => $this->faker->phoneNumber(),
            'jabatan' => $this->faker->randomElement(['Staff Keuangan', 'Staff Marketing', 'Developer']),
            'departemen' => $this->faker->randomElement(['Finance', 'Marketing', 'IT']),
            'status_kepegawaian' => $this->faker->randomElement(['Tetap', 'Kontrak', 'Magang', 'Freelance']),
            'tanggal_mulai_bekerja' => $this->faker->date(),
            'gaji_pokok' => $this->faker->randomFloat(2, 4000000, 9000000),
            'nomor_rekening' => $this->faker->creditCardNumber(),
            'nama_pemilik_rekening' => function (array $attributes) {
                // Menggunakan nama lengkap yang sudah di-generate untuk karyawan ini
                return $attributes['nama_lengkap'];
            },
            'nomor_bpjs_kesehatan' => $this->faker->numerify('000#############'),
        ];
    }
}
<?php

namespace Database\Factories;

use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class KaryawanFactory extends Factory
{
    protected $model = Karyawan::class;

    public function definition(): array
    {
        // Generate realistic gaji pokok
        $gajiPokok = $this->faker->numberBetween(4500000, 12000000);
        
        // Calculate tunjangan using the same 75% rule - BULANAN
        $tunjangan = $this->calculateRealisticTunjanganBulanan($gajiPokok);

        return [
            'nik' => $this->faker->unique()->numerify('31710########'),
            'nama_lengkap' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('karyawan123'),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-45 years', '-22 years')->format('Y-m-d'),
            'jenis_kelamin' => $this->faker->randomElement(['Laki-laki', 'Perempuan']),
            'alamat' => $this->faker->address(),
            'nomor_telepon' => $this->faker->phoneNumber(),
            'jabatan' => $this->faker->randomElement([
                'Software Developer', 'System Analyst', 'Quality Assurance',
                'Marketing Specialist', 'Sales Executive', 'Customer Service',
                'Accounting Staff', 'Administrative Staff', 'Operations Staff',
                'Technical Support', 'Data Analyst', 'Project Coordinator'
            ]),
            'departemen' => $this->faker->randomElement([
                'Information Technology', 'Marketing', 'Sales',
                'Finance & Accounting', 'Operations', 'Customer Service'
            ]),
            'status_kepegawaian' => $this->faker->randomElement(['Tetap', 'Kontrak']),
            'tanggal_mulai_bekerja' => $this->faker->dateTimeBetween('-8 years', '-6 months')->format('Y-m-d'),
            'gaji_pokok' => $gajiPokok,
            'tunjangan_jabatan' => $tunjangan['jabatan'],
            'tunjangan_makan_bulanan' => $tunjangan['makan'],
            'tunjangan_transport_bulanan' => $tunjangan['transport'],
            'kuota_cuti_tahunan' => 12,
            'nomor_rekening' => $this->faker->numerify('##########'),
            'nama_pemilik_rekening' => $this->faker->name(),
            'remember_token' => \Illuminate\Support\Str::random(10),
        ];
    }

    /**
     * Calculate realistic tunjangan bulanan with 75% rule
     */
    private function calculateRealisticTunjanganBulanan(float $gajiPokok): array
    {
        // Max total tunjangan to maintain 75% base salary rule
        $maxTotalTunjangan = $gajiPokok * (1/0.75 - 1);

        // Distribution for regular staff - LANGSUNG BULANAN
        $tunjanganJabatan = round($maxTotalTunjangan * 0.4, -3); // 40%
        $tunjanganMakanBulanan = round(min(770000, $maxTotalTunjangan * 0.3), -3); // 30% or max 770k/bulan
        $tunjanganTransportBulanan = round($maxTotalTunjangan - $tunjanganJabatan - $tunjanganMakanBulanan, -3);

        // Ensure no negative values
        return [
            'jabatan' => max(0, $tunjanganJabatan),
            'makan' => max(0, $tunjanganMakanBulanan),
            'transport' => max(0, $tunjanganTransportBulanan),
        ];
    }
}
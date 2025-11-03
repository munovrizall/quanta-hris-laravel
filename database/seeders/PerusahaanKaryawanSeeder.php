<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Perusahaan;
use App\Models\Cabang;
use App\Models\Karyawan;
use App\Models\Role;
use App\Models\GolonganPtkp;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class PerusahaanKaryawanSeeder extends Seeder
{
    public function run(): void
    {
        // 2. Buat permissions untuk Role resource jika belum ada
        $permissions = [
            ['permission_id' => 'P0001', 'name' => 'view_any_role'],
            ['permission_id' => 'P0002', 'name' => 'view_role'],
            ['permission_id' => 'P0003', 'name' => 'create_role'],
            ['permission_id' => 'P0004', 'name' => 'update_role'],
            ['permission_id' => 'P0005', 'name' => 'delete_role'],
            ['permission_id' => 'P0006', 'name' => 'delete_any_role'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'web'],
                ['permission_id' => $permission['permission_id']]
            );
        }

        // 5. Buat Perusahaan
        $perusahaan = Perusahaan::create([
            'perusahaan_id' => 'P0001',
            'nama_perusahaan' => 'PT. Quanta Teknik Gemilang',
            'email' => 'herein@smartcool.id',
            'nomor_telepon' => '0215550123',
            'jam_masuk' => '09:00:00',
            'jam_pulang' => '17:00:00',
            'potongan_keterlambatan' => 50000,

            // Konfigurasi Potongan BPJS Karyawan
            'persen_bpjs_kesehatan' => 0.01, // 1%
            'persen_bpjs_jht' => 0.02, // 2%
            'persen_bpjs_jp' => 0.01, // 1%

            // Batas Atas Gaji (Salary Cap) BPJS
            'batas_gaji_bpjs_kesehatan' => 12000000,
            'batas_gaji_bpjs_pensiun' => 10547400,
        ]);

        // 6. Buat Cabang untuk Perusahaan tersebut
        $cabangUtama = Cabang::create([
            'cabang_id' => 'C0001',
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'nama_cabang' => 'Kantor Pusat Jakarta',
            'alamat' => 'Jl. Taman Margasatwa Raya, Jakarta Selatan',
            'latitude' => -6.2916353910230844,
            'longitude' => 106.82278662270117,
            'radius_lokasi' => 50,
        ]);

        $cabangCiputat = Cabang::create([
            'cabang_id' => 'C0002',
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'nama_cabang' => 'Kantor Cabang Ciputat',
            'alamat' => 'Jl. Ir H. Juanda, Ciputat',
            'latitude' => -6.306088065952266,
            'longitude' => 106.75274304726672,
            'radius_lokasi' => 100,
        ]);

        $cabangWijaya = Cabang::create([
            'cabang_id' => 'C0003',
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'nama_cabang' => 'Kantor Cabang Wijaya',
            'alamat' => 'Jl. Wijaya 1, Jakarta Selatan',
            'latitude' => -6.243999920356959,
            'longitude' => 106.81263872377586,
            'radius_lokasi' => 75, // 75 meter
        ]);

        // 7. Ambil data master yang sudah ada
        $ptkps = GolonganPtkp::all();
        $counter = 1;

        // 8. Buat 1 Admin dengan Tunjangan Realistic
        $adminGajiPokok = 20000000;
        $adminTunjangan = $this->calculateRealisticTunjanganBulanan($adminGajiPokok, 'Executive');

        $adminKaryawan = Karyawan::create([
            'karyawan_id' => 'K' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'golongan_ptkp_id' => $ptkps->random()->golongan_ptkp_id,
            'nik' => '3171010101800001',
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'admin@smartcool.id',
            'password' => Hash::make('admin123'),
            'tanggal_lahir' => '1980-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Kebagusan Raya No. 10, Jakarta Selatan',
            'nomor_telepon' => '081234567890',
            'jabatan' => 'System Administrator',
            'departemen' => 'Information Technology',
            'status_kepegawaian' => 'Tetap',
            'tanggal_mulai_bekerja' => '2015-01-15',
            'gaji_pokok' => $adminGajiPokok,
            'tunjangan_jabatan' => $adminTunjangan['jabatan'],
            'tunjangan_makan_bulanan' => $adminTunjangan['makan'],
            'tunjangan_transport_bulanan' => $adminTunjangan['transport'],
            'kuota_cuti_tahunan' => 12,
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Budi Santoso',
            'role_id' => 'R01',
        ]);
        $adminKaryawan->assignRole('Admin');

        // 9. Buat 1 CEO dengan Tunjangan
        $ceoGajiPokok = 50000000;
        $ceoTunjangan = $this->calculateRealisticTunjanganBulanan($ceoGajiPokok, 'Executive');

        $ceoKaryawan = Karyawan::create([
            'karyawan_id' => 'K' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'golongan_ptkp_id' => $ptkps->random()->golongan_ptkp_id,
            'nik' => '3171010201750001',
            'nama_lengkap' => 'Dr. Ahmad Wijaya',
            'email' => 'ceo@smartcool.id',
            'password' => Hash::make('ceo123'),
            'tanggal_lahir' => '1975-02-20',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Pondok Indah Raya No. 25, Jakarta Selatan',
            'nomor_telepon' => '081234567891',
            'jabatan' => 'Chief Executive Officer',
            'departemen' => 'Executive',
            'status_kepegawaian' => 'Tetap',
            'tanggal_mulai_bekerja' => '2010-03-01',
            'gaji_pokok' => $ceoGajiPokok,
            'tunjangan_jabatan' => $ceoTunjangan['jabatan'],
            'tunjangan_makan_bulanan' => $ceoTunjangan['makan'],
            'tunjangan_transport_bulanan' => $ceoTunjangan['transport'],
            'kuota_cuti_tahunan' => 12,
            'nomor_rekening' => '1234567891',
            'nama_pemilik_rekening' => 'Dr. Ahmad Wijaya',
            'role_id' => 'R06',
        ]);
        $ceoKaryawan->assignRole('CEO');

        // 10. Buat 2 Manager HRD dengan Tunjangan
        $managerHRDData = [
            [
                'nik' => '3171010301780001',
                'nama_lengkap' => 'Sari Indrawati',
                'email' => 'manager.hrd1@smartcool.id',
                'password' => 'managerhrd123',
                'tanggal_lahir' => '1978-03-15',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'Jl. Kemang Raya No. 12, Jakarta Selatan',
                'nomor_telepon' => '081234567892',
                'jabatan' => 'Manager Human Resources',
                'departemen' => 'Human Resources',
                'tanggal_mulai_bekerja' => '2016-06-01',
                'gaji_pokok' => 18000000,
                'nomor_rekening' => '1234567892',
                'nama_pemilik_rekening' => 'Sari Indrawati',
            ],
            [
                'nik' => '3271010401790001',
                'nama_lengkap' => 'Rizki Pratama',
                'email' => 'manager.hrd2@smartcool.id',
                'password' => 'managerhrd123',
                'tanggal_lahir' => '1979-04-10',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Jl. Boulevard Raya No. 5, Bekasi',
                'nomor_telepon' => '081234567893',
                'jabatan' => 'Manager Human Resources',
                'departemen' => 'Human Resources',
                'tanggal_mulai_bekerja' => '2017-09-15',
                'gaji_pokok' => 17000000,
                'nomor_rekening' => '1234567893',
                'nama_pemilik_rekening' => 'Rizki Pratama',
            ]
        ];

        foreach ($managerHRDData as $data) {
            $tunjangan = $this->calculateRealisticTunjanganBulanan($data['gaji_pokok'], 'Manager');

            $managerHRD = Karyawan::create([
                'karyawan_id' => 'K' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
                'perusahaan_id' => $perusahaan->perusahaan_id,
                'golongan_ptkp_id' => $ptkps->random()->golongan_ptkp_id,
                'nik' => $data['nik'],
                'nama_lengkap' => $data['nama_lengkap'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'tanggal_lahir' => $data['tanggal_lahir'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'alamat' => $data['alamat'],
                'nomor_telepon' => $data['nomor_telepon'],
                'jabatan' => $data['jabatan'],
                'departemen' => $data['departemen'],
                'status_kepegawaian' => 'Tetap',
                'tanggal_mulai_bekerja' => $data['tanggal_mulai_bekerja'],
                'gaji_pokok' => $data['gaji_pokok'],
                'tunjangan_jabatan' => $tunjangan['jabatan'],
                'tunjangan_makan_bulanan' => $tunjangan['makan'],
                'tunjangan_transport_bulanan' => $tunjangan['transport'],
                'kuota_cuti_tahunan' => 12,
                'nomor_rekening' => $data['nomor_rekening'],
                'nama_pemilik_rekening' => $data['nama_pemilik_rekening'],
                'role_id' => 'R03',
            ]);
            $managerHRD->assignRole('Manager HRD');
        }

        // 11. Buat 5 Staff HRD dengan Tunjangan
        $staffHRDData = [
            [
                'nik' => '3171010501820001',
                'nama_lengkap' => 'Maya Kusuma',
                'email' => 'staff.hrd1@smartcool.id',
                'password' => 'staffhrd123',
                'tanggal_lahir' => '1982-05-20',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'Jl. Pahlawan No. 15, Jakarta Timur',
                'nomor_telepon' => '081234567894',
                'jabatan' => 'Staff Human Resources',
                'departemen' => 'Human Resources',
                'tanggal_mulai_bekerja' => '2018-02-01',
                'gaji_pokok' => 8500000,
                'nomor_rekening' => '1234567894',
                'nama_pemilik_rekening' => 'Maya Kusuma',
            ],
            [
                'nik' => '3271010601830001',
                'nama_lengkap' => 'Doni Setiawan',
                'email' => 'staff.hrd2@smartcool.id',
                'password' => 'staffhrd123',
                'tanggal_lahir' => '1983-06-18',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Jl. Merdeka No. 8, Depok',
                'nomor_telepon' => '081234567895',
                'jabatan' => 'Staff Human Resources',
                'departemen' => 'Human Resources',
                'tanggal_mulai_bekerja' => '2019-07-10',
                'gaji_pokok' => 8000000,
                'nomor_rekening' => '1234567895',
                'nama_pemilik_rekening' => 'Doni Setiawan',
            ],
            [
                'nik' => '3371010701840001',
                'nama_lengkap' => 'Putri Maharani',
                'email' => 'staff.hrd3@smartcool.id',
                'password' => 'staffhrd123',
                'tanggal_lahir' => '1984-07-25',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'Jl. Asia Afrika No. 20, Bandung',
                'nomor_telepon' => '081234567896',
                'jabatan' => 'Staff Human Resources',
                'departemen' => 'Human Resources',
                'tanggal_mulai_bekerja' => '2020-01-15',
                'gaji_pokok' => 8200000,
                'nomor_rekening' => '1234567896',
                'nama_pemilik_rekening' => 'Putri Maharani',
            ],
            [
                'nik' => '3171010801850001',
                'nama_lengkap' => 'Andi Nugroho',
                'email' => 'staff.hrd4@smartcool.id',
                'password' => 'staffhrd123',
                'tanggal_lahir' => '1985-08-12',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Jl. Sudirman No. 45, Jakarta Pusat',
                'nomor_telepon' => '081234567897',
                'jabatan' => 'Staff Human Resources',
                'departemen' => 'Human Resources',
                'tanggal_mulai_bekerja' => '2020-11-01',
                'gaji_pokok' => 7800000,
                'nomor_rekening' => '1234567897',
                'nama_pemilik_rekening' => 'Andi Nugroho',
            ],
            [
                'nik' => '3271010901860001',
                'nama_lengkap' => 'Lisa Handayani',
                'email' => 'staff.hrd5@smartcool.id',
                'password' => 'staffhrd123',
                'tanggal_lahir' => '1986-09-08',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'Jl. Raya Bogor No. 30, Bogor',
                'nomor_telepon' => '081234567898',
                'jabatan' => 'Staff Human Resources',
                'departemen' => 'Human Resources',
                'tanggal_mulai_bekerja' => '2021-03-20',
                'gaji_pokok' => 8100000,
                'nomor_rekening' => '1234567898',
                'nama_pemilik_rekening' => 'Lisa Handayani',
            ]
        ];

        foreach ($staffHRDData as $data) {
            $tunjangan = $this->calculateRealisticTunjanganBulanan($data['gaji_pokok'], 'Staff');

            $staffHRD = Karyawan::create([
                'karyawan_id' => 'K' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
                'perusahaan_id' => $perusahaan->perusahaan_id,
                'golongan_ptkp_id' => $ptkps->random()->golongan_ptkp_id,
                'nik' => $data['nik'],
                'nama_lengkap' => $data['nama_lengkap'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'tanggal_lahir' => $data['tanggal_lahir'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'alamat' => $data['alamat'],
                'nomor_telepon' => $data['nomor_telepon'],
                'jabatan' => $data['jabatan'],
                'departemen' => $data['departemen'],
                'status_kepegawaian' => 'Tetap',
                'tanggal_mulai_bekerja' => $data['tanggal_mulai_bekerja'],
                'gaji_pokok' => $data['gaji_pokok'],
                'tunjangan_jabatan' => $tunjangan['jabatan'],
                'tunjangan_makan_bulanan' => $tunjangan['makan'],
                'tunjangan_transport_bulanan' => $tunjangan['transport'],
                'kuota_cuti_tahunan' => 12,
                'nomor_rekening' => $data['nomor_rekening'],
                'nama_pemilik_rekening' => $data['nama_pemilik_rekening'],
                'role_id' => 'R02',
            ]);
            $staffHRD->assignRole('Staff HRD');
        }

        // 12. Buat 1 Manager Finance dengan Tunjangan
        $managerFinanceGaji = 19000000;
        $managerFinanceTunjangan = $this->calculateRealisticTunjanganBulanan($managerFinanceGaji, 'Manager');

        $managerFinance = Karyawan::create([
            'karyawan_id' => 'K' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'golongan_ptkp_id' => $ptkps->random()->golongan_ptkp_id,
            'nik' => '3171011001770001',
            'nama_lengkap' => 'Rini Setyowati',
            'email' => 'manager.finance@smartcool.id',
            'password' => Hash::make('managerfinance123'),
            'tanggal_lahir' => '1977-10-12',
            'jenis_kelamin' => 'Perempuan',
            'alamat' => 'Jl. Senayan No. 18, Jakarta Selatan',
            'nomor_telepon' => '081234567899',
            'jabatan' => 'Manager Finance',
            'departemen' => 'Finance & Accounting',
            'status_kepegawaian' => 'Tetap',
            'tanggal_mulai_bekerja' => '2014-08-01',
            'gaji_pokok' => $managerFinanceGaji,
            'tunjangan_jabatan' => $managerFinanceTunjangan['jabatan'],
            'tunjangan_makan_bulanan' => $managerFinanceTunjangan['makan'],
            'tunjangan_transport_bulanan' => $managerFinanceTunjangan['transport'],
            'kuota_cuti_tahunan' => 12,
            'nomor_rekening' => '1234567899',
            'nama_pemilik_rekening' => 'Rini Setyowati',
            'role_id' => 'R04',
        ]);
        $managerFinance->assignRole('Manager Finance');

        // 13. Buat 2 Account Payment dengan Tunjangan
        $accountPaymentData = [
            [
                'nik' => '3171011101810001',
                'nama_lengkap' => 'Tono Wijaya',
                'email' => 'account.payment1@smartcool.id',
                'password' => 'accountpayment123',
                'tanggal_lahir' => '1981-11-05',
                'jenis_kelamin' => 'Laki-laki',
                'alamat' => 'Jl. Gatot Subroto No. 22, Jakarta Selatan',
                'nomor_telepon' => '081234567900',
                'jabatan' => 'Account Payment Officer',
                'departemen' => 'Finance & Accounting',
                'tanggal_mulai_bekerja' => '2019-04-15',
                'gaji_pokok' => 9500000,
                'nomor_rekening' => '1234567900',
                'nama_pemilik_rekening' => 'Tono Wijaya',
            ],
            [
                'nik' => '3271011201820002',
                'nama_lengkap' => 'Sinta Dewi',
                'email' => 'account.payment2@smartcool.id',
                'password' => 'accountpayment123',
                'tanggal_lahir' => '1982-12-18',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'Jl. HR Rasuna Said No. 35, Jakarta Selatan',
                'nomor_telepon' => '081234567901',
                'jabatan' => 'Account Payment Officer',
                'departemen' => 'Finance & Accounting',
                'tanggal_mulai_bekerja' => '2020-08-01',
                'gaji_pokok' => 9200000,
                'nomor_rekening' => '1234567901',
                'nama_pemilik_rekening' => 'Sinta Dewi',
            ]
        ];

        foreach ($accountPaymentData as $data) {
            $tunjangan = $this->calculateRealisticTunjanganBulanan($data['gaji_pokok'], 'Staff');

            $accountPayment = Karyawan::create([
                'karyawan_id' => 'K' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
                'perusahaan_id' => $perusahaan->perusahaan_id,
                'golongan_ptkp_id' => $ptkps->random()->golongan_ptkp_id,
                'nik' => $data['nik'],
                'nama_lengkap' => $data['nama_lengkap'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'tanggal_lahir' => $data['tanggal_lahir'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'alamat' => $data['alamat'],
                'nomor_telepon' => $data['nomor_telepon'],
                'jabatan' => $data['jabatan'],
                'departemen' => $data['departemen'],
                'status_kepegawaian' => 'Tetap',
                'tanggal_mulai_bekerja' => $data['tanggal_mulai_bekerja'],
                'gaji_pokok' => $data['gaji_pokok'],
                'tunjangan_jabatan' => $tunjangan['jabatan'],
                'tunjangan_makan_bulanan' => $tunjangan['makan'],
                'tunjangan_transport_bulanan' => $tunjangan['transport'],
                'kuota_cuti_tahunan' => 12,
                'nomor_rekening' => $data['nomor_rekening'],
                'nama_pemilik_rekening' => $data['nama_pemilik_rekening'],
                'role_id' => 'R05',
            ]);
            $accountPayment->assignRole('Account Payment');
        }

        // 14. Buat 100 Karyawan dengan Faker dan Tunjangan
        $cabangIds = [$cabangUtama->cabang_id, $cabangCiputat->cabang_id, $cabangWijaya->cabang_id];

        for ($i = 1; $i <= 100; $i++) {
            // Distribusi cabang: 50% Jakarta, 30% Depok, 20% Bandung
            $cabangDistribution = rand(1, 100);
            if ($cabangDistribution <= 50) {
                $selectedCabang = $cabangUtama->cabang_id;
            } elseif ($cabangDistribution <= 80) {
                $selectedCabang = $cabangCiputat->cabang_id;
            } else {
                $selectedCabang = $cabangWijaya->cabang_id;
            }

            // Generate gaji pokok yang realistis untuk karyawan biasa
            $gajiPokok = rand(4500000, 12000000); // 4.5 juta - 12 juta
            $tunjangan = $this->calculateRealisticTunjanganBulanan($gajiPokok, 'Staff');

            $staffKaryawan = Karyawan::factory()->create([
                'karyawan_id' => 'K' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
                'perusahaan_id' => $perusahaan->perusahaan_id,
                'golongan_ptkp_id' => $ptkps->random()->golongan_ptkp_id,
                'gaji_pokok' => $gajiPokok,
                'tunjangan_jabatan' => $tunjangan['jabatan'],
                'tunjangan_makan_bulanan' => $tunjangan['makan'],
                'tunjangan_transport_bulanan' => $tunjangan['transport'],
                'kuota_cuti_tahunan' => 12,
                'role_id' => 'R07', // Role Karyawan
                // Jika ada kolom cabang_id, uncomment line berikut:
                // 'cabang_id' => $selectedCabang,
            ]);

            // Assign role Karyawan
            $staffKaryawan->assignRole('Karyawan');
        }

        $rizalGajiPokok = 6000000; // Gaji pokok 6 juta
        $rizalTunjangan = $this->calculateRealisticTunjanganBulanan($rizalGajiPokok, 'Staff');

        $rizalKaryawan = Karyawan::create([
            'karyawan_id' => 'K' . str_pad($counter++, 4, '0', STR_PAD_LEFT),
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'golongan_ptkp_id' => $ptkps->random()->golongan_ptkp_id,
            'nik' => '3171011301900001',
            'nama_lengkap' => 'Mohammad Novrizal',
            'email' => 'rizal@smartcool.id',
            'password' => Hash::make('rizal123'),
            'tanggal_lahir' => '2002-11-13',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Wijaya 1, Jakarta Selatan',
            'nomor_telepon' => '081234567902',
            'jabatan' => 'Staff IT Support',
            'departemen' => 'Information Technology',
            'status_kepegawaian' => 'Tetap',
            'tanggal_mulai_bekerja' => '2024-01-15',
            'gaji_pokok' => $rizalGajiPokok,
            'tunjangan_jabatan' => $rizalTunjangan['jabatan'],
            'tunjangan_makan_bulanan' => $rizalTunjangan['makan'],
            'tunjangan_transport_bulanan' => $rizalTunjangan['transport'],
            'kuota_cuti_tahunan' => 12,
            'nomor_rekening' => '1234567902',
            'nama_pemilik_rekening' => 'Mohammad Novrizal Sugiarto',
            'role_id' => 'R07', // Role Karyawan
        ]);
        $rizalKaryawan->assignRole('Karyawan');

        $this->command->info('Berhasil membuat karyawan dengan tunjangan bulanan:');
        $this->command->info('- Admin: 1');
        $this->command->info('- CEO: 1');
        $this->command->info('- Manager HRD: 2');
        $this->command->info('- Staff HRD: 5');
        $this->command->info('- Manager Finance: 1');
        $this->command->info('- Account Payment: 2');
        $this->command->info('- Karyawan: 100');
        $this->command->info('Total: 112 karyawan dengan kuota cuti 12 hari');

        // Log contoh perhitungan tunjangan
        $this->command->info('');
        $this->command->info('Contoh Perhitungan Tunjangan BULANAN (75% Rule):');
        $this->command->info('- Gaji 10jt → Max Tunjangan: 3.33jt/bulan (Total: 13.33jt, Gaji=75%)');
        $this->command->info('- Gaji 20jt → Max Tunjangan: 6.67jt/bulan (Total: 26.67jt, Gaji=75%)');
    }

    /**
     * Hitung tunjangan bulanan yang realistis dengan rule: Gaji Pokok ≥ 75% dari Total
     * Formula: Gaji Pokok ≥ 0.75 * (Gaji Pokok + Total Tunjangan)
     * Atau: Total Tunjangan ≤ (Gaji Pokok / 0.75) - Gaji Pokok = Gaji Pokok * (1/0.75 - 1) = Gaji Pokok * 0.3333
     */
    private function calculateRealisticTunjanganBulanan(float $gajiPokok, string $level): array
    {
        // Maksimal total tunjangan agar gaji pokok tetap 75%
        $maxTotalTunjangan = $gajiPokok * (1 / 0.75 - 1); // = $gajiPokok * 0.3333

        // Distribusi tunjangan berdasarkan level jabatan - LANGSUNG BULANAN
        switch ($level) {
            case 'Executive': // CEO, Admin level tinggi
                $tunjanganJabatan = $maxTotalTunjangan * 0.6; // 60% dari max
                $tunjanganMakanBulanan = min(1650000, $maxTotalTunjangan * 0.2); // 20% atau max 1.65jt/bulan (~75k/hari)
                $tunjanganTransportBulanan = $maxTotalTunjangan - $tunjanganJabatan - $tunjanganMakanBulanan;
                break;

            case 'Manager': // Manager level
                $tunjanganJabatan = $maxTotalTunjangan * 0.5; // 50% dari max
                $tunjanganMakanBulanan = min(1100000, $maxTotalTunjangan * 0.25); // 25% atau max 1.1jt/bulan (~50k/hari)
                $tunjanganTransportBulanan = $maxTotalTunjangan - $tunjanganJabatan - $tunjanganMakanBulanan;
                break;

            case 'Staff':
            default: // Staff dan karyawan biasa
                $tunjanganJabatan = $maxTotalTunjangan * 0.4; // 40% dari max
                $tunjanganMakanBulanan = min(770000, $maxTotalTunjangan * 0.3); // 30% atau max 770k/bulan (~35k/hari)
                $tunjanganTransportBulanan = $maxTotalTunjangan - $tunjanganJabatan - $tunjanganMakanBulanan;
                break;
        }

        // Pastikan tidak ada nilai negatif dan bulatkan
        $tunjanganJabatan = max(0, round($tunjanganJabatan, -3)); // Bulatkan ke ribuan
        $tunjanganMakanBulanan = max(0, round($tunjanganMakanBulanan, -3));
        $tunjanganTransportBulanan = max(0, round($tunjanganTransportBulanan, -3));

        // Validasi final: pastikan rule 75% terpenuhi
        $totalTunjangan = $tunjanganJabatan + $tunjanganMakanBulanan + $tunjanganTransportBulanan;
        $totalGaji = $gajiPokok + $totalTunjangan;
        $persentaseGajiPokok = ($gajiPokok / $totalGaji) * 100;

        // Jika masih belum 75%, adjust
        if ($persentaseGajiPokok < 75) {
            $factor = 0.75 / ($persentaseGajiPokok / 100);
            $adjustedMaxTunjangan = $totalTunjangan / $factor;

            $tunjanganJabatan = round(($tunjanganJabatan / $totalTunjangan) * $adjustedMaxTunjangan, -3);
            $tunjanganMakanBulanan = round(($tunjanganMakanBulanan / $totalTunjangan) * $adjustedMaxTunjangan, -3);
            $tunjanganTransportBulanan = round(($tunjanganTransportBulanan / $totalTunjangan) * $adjustedMaxTunjangan, -3);
        }

        return [
            'jabatan' => $tunjanganJabatan,
            'makan' => $tunjanganMakanBulanan,
            'transport' => $tunjanganTransportBulanan,
        ];
    }
}
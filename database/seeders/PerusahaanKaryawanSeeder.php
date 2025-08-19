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
        // 1. Buat Role Admin terlebih dahulu
        $adminRole = Role::create([
            'role_id' => 'R0001',
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

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

        // 3. Assign permissions ke Admin role menggunakan nama permission
        $adminRole->givePermissionTo([
            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role'
        ]);

        // 4. Buat Role Staff
        Role::create([
            'role_id' => 'R0002',
            'name' => 'Staff',
            'guard_name' => 'web',
        ]);

        // 5. Buat Perusahaan
        $perusahaan = Perusahaan::create([
            'perusahaan_id' => 'P0001',
            'nama_perusahaan' => 'PT. Quanta Teknik Gemilang',
            'email' => 'herein@smartcool.id',
            'nomor_telepon' => '0215550123',
            'jam_masuk' => '09:00:00',
            'jam_pulang' => '17:00:00',
        ]);

        // 6. Buat Cabang untuk Perusahaan tersebut
        $cabangUtama = Cabang::create([
            'cabang_id' => 'C0001',
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'nama_cabang' => 'Kantor Pusat Jakarta',
            'alamat' => 'Jl. Jend. Sudirman Kav. 52-53, Jakarta Selatan',
            'latitude' => -6.2245,
            'longitude' => 106.809,
            'radius_lokasi' => 100, // 100 meter
        ]);

        $cabangDepok = Cabang::create([
            'cabang_id' => 'C0002',
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'nama_cabang' => 'Kantor Cabang Depok',
            'alamat' => 'Jl. Bojongsari No. 1, Depok',
            'latitude' => -6.9218,
            'longitude' => 107.607,
            'radius_lokasi' => 50, // 50 meter
        ]);

        // 7. Ambil data master yang sudah ada
        $ptkp = GolonganPtkp::first();

        // 8. Buat 1 Karyawan Admin
        $adminKaryawan = Karyawan::create([
            'karyawan_id' => 'K0001',
            'perusahaan_id' => $perusahaan->perusahaan_id,
            'golongan_ptkp_id' => $ptkp->golongan_ptkp_id,
            'nik' => '3171010101800001',
            'nama_lengkap' => 'Budi Santoso',
            'email' => 'admin@email.com',
            'password' => 'admin',
            'tanggal_lahir' => '1980-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat' => 'Jl. Kebagusan Raya No. 10',
            'nomor_telepon' => '081234567890',
            'jabatan' => 'Manager HRD',
            'departemen' => 'Human Resources',
            'status_kepegawaian' => 'Tetap',
            'tanggal_mulai_bekerja' => '2015-05-10',
            'gaji_pokok' => 15000000,
            'nomor_rekening' => '1234567890',
            'nama_pemilik_rekening' => 'Budi Santoso',
            'role_id' => 'R0001',
        ]);

        // Assign role Admin ke karyawan menggunakan Spatie Permission
        $adminKaryawan->assignRole('Admin');

        // 9. Buat 20 Karyawan Staff secara dinamis dan assign ke cabang
        for ($i = 2; $i <= 21; $i++) {
            // Alternating antara cabang utama dan cabang depok
            $cabangId = ($i % 2 == 0) ? $cabangUtama->cabang_id : $cabangDepok->cabang_id;

            $staffKaryawan = Karyawan::factory()->create([
                'karyawan_id' => 'K' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'perusahaan_id' => $perusahaan->perusahaan_id,
                'golongan_ptkp_id' => GolonganPtkp::all()->random()->golongan_ptkp_id,
                'role_id' => 'R0002', // Assign role staff
                // Anda bisa menambahkan cabang_id jika ada kolom tersebut
                // 'cabang_id' => $cabangId,
            ]);

            // Assign role Staff ke karyawan staff
            $staffKaryawan->assignRole('Staff');
        }
    }
}
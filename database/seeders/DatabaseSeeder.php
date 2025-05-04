<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Site;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@email.com',
            'password' => Hash::make('admin'),
            'position' => 'Administrator',
            'department' => 'Operational',
            'phone' => '085155414564',
            'role' => 'admin',
        ]);

        Company::create([
            'name' => 'PT. Quanta Teknik Gemilang',
            'email' => 'herein@smartcool.id',
            'phone' => '02150919091',
            'time_in' => '09:00',
            'time_out' => '17:00',
        ]);

        Site::create([
            'company_id' => 1,
            'name' => 'Kantor Pusat',
            'address' => 'Jl. Taman Margasatwa Raya No.3, RT.1/RW.1, Ragunan, Ps. Minggu, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12540, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12550',
            'latitude' => '-6.290778026580011',
            'longitude' => '106.82471444171392',
            'radius_in_m' => '50',
        ]);

        $this->call([
            AttendanceSeeder::class,
            PermissionSeeder::class,
        ]);
    }
}

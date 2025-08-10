<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Menggunakan updateOrCreate untuk menghindari duplikasi jika seeder dijalankan lagi
        Role::updateOrCreate(
            ['role_id' => 'MGR'],
            ['role_name' => 'Manager HRD', 'guard_name' => 'web']
        );

        Role::updateOrCreate(
            ['role_id' => 'STF'],
            ['role_name' => 'Staff', 'guard_name' => 'web']
        );
        
        Role::updateOrCreate(
            ['role_id' => 'ADM'],
            ['role_name' => 'Admin', 'guard_name' => 'web']
        );
    }
}
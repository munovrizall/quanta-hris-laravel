<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Define permissions for each model
    $permissions = [
      // Cabang permissions
      'view_any_cabang',
      'view_cabang',
      'create_cabang',
      'update_cabang',
      'delete_cabang',
      'delete_any_cabang',
      'force_delete_cabang',
      'force_delete_any_cabang',
      'restore_cabang',
      'restore_any_cabang',
      'replicate_cabang',
      'reorder_cabang',

      // Karyawan permissions
      'view_any_karyawan',
      'view_karyawan',
      'create_karyawan',
      'update_karyawan',
      'delete_karyawan',
      'delete_any_karyawan',
      'force_delete_karyawan',
      'force_delete_any_karyawan',
      'restore_karyawan',
      'restore_any_karyawan',
      'replicate_karyawan',
      'reorder_karyawan',

      // Role permissions
      'view_any_role',
      'view_role',
      'create_role',
      'update_role',
      'delete_role',
      'delete_any_role',
      'force_delete_role',
      'force_delete_any_role',
      'restore_role',
      'restore_any_role',
      'replicate_role',
      'reorder_role',

      // Perusahaan permissions
      'view_any_perusahaan',
      'view_perusahaan',
      'create_perusahaan',
      'update_perusahaan',
      'delete_perusahaan',
      'delete_any_perusahaan',
      'force_delete_perusahaan',
      'force_delete_any_perusahaan',
      'restore_perusahaan',
      'restore_any_perusahaan',
      'replicate_perusahaan',
      'reorder_perusahaan',

      // Absensi permissions
      'view_any_absensi',
      'view_absensi',
      'create_absensi',
      'update_absensi',
      'delete_absensi',
      'delete_any_absensi',
      'force_delete_absensi',
      'force_delete_any_absensi',
      'restore_absensi',
      'restore_any_absensi',
      'replicate_absensi',
      'reorder_absensi',

      // Lembur permissions
      'view_any_lembur',
      'view_lembur',
      'create_lembur',
      'update_lembur',
      'delete_lembur',
      'delete_any_lembur',
      'force_delete_lembur',
      'force_delete_any_lembur',
      'restore_lembur',
      'restore_any_lembur',
      'replicate_lembur',
      'reorder_lembur',

      // Cuti permissions
      'view_any_cuti',
      'view_cuti',
      'create_cuti',
      'update_cuti',
      'delete_cuti',
      'delete_any_cuti',
      'force_delete_cuti',
      'force_delete_any_cuti',
      'restore_cuti',
      'restore_any_cuti',
      'replicate_cuti',
      'reorder_cuti',
    ];

    // Create permissions with custom ID format
    foreach ($permissions as $index => $permission) {
      $permissionId = 'P' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

      Permission::updateOrCreate(
        ['name' => $permission, 'guard_name' => 'web'],
        ['permission_id' => $permissionId]
      );
    }

    $this->command->info('Permissions created successfully!');
    $this->command->info('Total permissions created: ' . count($permissions));
  }
}
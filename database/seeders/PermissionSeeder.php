<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

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
    ];

    // Create permissions with custom ID format
    foreach ($permissions as $index => $permission) {
      $permissionId = 'P' . str_pad($index + 1, 4, '0', STR_PAD_LEFT);

      Permission::updateOrCreate(
        ['name' => $permission, 'guard_name' => 'web'],
        ['permission_id' => $permissionId]
      );
    }

    // Create or get admin role with custom ID
    $adminRole = Role::updateOrCreate(
      ['name' => 'admin', 'guard_name' => 'web'],
      ['role_id' => 'R0001']
    );

    // Clear existing role permissions
    DB::table('role_has_permissions')->where('role_id', 'R0001')->delete();

    // Get all created permissions
    $allPermissions = Permission::whereIn('name', $permissions)->get();

    // Insert role permissions manually
    foreach ($allPermissions as $permission) {
      DB::table('role_has_permissions')->updateOrInsert([
        'role_id' => 'R0001',
        'permission_id' => $permission->permission_id
      ]);
    }

    $this->command->info('Permissions created and assigned to admin role successfully!');
    $this->command->info('Total permissions created: ' . count($permissions));
  }
}
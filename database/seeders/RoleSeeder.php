<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Define roles with their custom IDs
    $roles = [
      ['role_id' => 'R01', 'name' => 'Admin'],
      ['role_id' => 'R02', 'name' => 'Staff HRD'],
      ['role_id' => 'R03', 'name' => 'Manager HRD'],
      ['role_id' => 'R04', 'name' => 'Manager Finance'],
      ['role_id' => 'R05', 'name' => 'Account Payment'],
      ['role_id' => 'R06', 'name' => 'CEO'],
      ['role_id' => 'R07', 'name' => 'Karyawan'],
    ];

    // Create roles
    foreach ($roles as $roleData) {
      Role::updateOrCreate(
        ['name' => $roleData['name'], 'guard_name' => 'web'],
        ['role_id' => $roleData['role_id']]
      );
    }

    // Assign permissions to roles
    $this->assignPermissions();

    $this->command->info('Roles created successfully!');
    $this->command->info('Total roles created: ' . count($roles));
  }

  /**
   * Assign permissions to roles
   */
  private function assignPermissions(): void
  {
    // Get all permissions
    $allPermissions = Permission::all();

    // Admin gets all permissions
    $adminRole = Role::where('name', 'Admin')->first();
    if ($adminRole) {
      DB::table('role_has_permissions')->where('role_id', $adminRole->role_id)->delete();
      foreach ($allPermissions as $permission) {
        DB::table('role_has_permissions')->updateOrInsert([
          'role_id' => $adminRole->role_id,
          'permission_id' => $permission->permission_id
        ]);
      }
    }

    // CEO gets most permissions (except some admin-specific ones)
    $ceoRole = Role::where('name', 'CEO')->first();
    if ($ceoRole) {
      $ceoPermissions = $allPermissions->whereIn('name', [
        'menu_laporan_keuangan',
        'view_any_laporan_keuangan',
        'view_laporan_keuangan',

        'menu_laporan_kinerja',
        'view_any_laporan_kinerja',
        'view_laporan_kinerja',
      ]);

      DB::table('role_has_permissions')->where('role_id', $ceoRole->role_id)->delete();
      foreach ($ceoPermissions as $permission) {
        DB::table('role_has_permissions')->updateOrInsert([
          'role_id' => $ceoRole->role_id,
          'permission_id' => $permission->permission_id
        ]);
      }
    }

    // Manager HRD permissions (termasuk lembur dan cuti)
    $managerHRDRole = Role::where('name', 'Manager HRD')->first();
    if ($managerHRDRole) {
      $managerHRDPermissions = $allPermissions->whereIn('name', [
        // Karyawan permissions
        'view_any_karyawan',
        'view_karyawan',
        'create_karyawan',
        'update_karyawan',
        'delete_karyawan',
        'restore_karyawan',
        // Role permissions
        'view_any_role',
        'view_role',
        // Absensi permissions
        'view_any_absensi',
        'view_absensi',
        'create_absensi',
        'update_absensi',
        'delete_absensi',
        'restore_absensi',
        // Lembur permissions
        'view_any_lembur',
        'view_lembur',
        'create_lembur',
        'update_lembur',
        'delete_lembur',
        'restore_lembur',
        // Cuti permissions
        'view_any_cuti',
        'view_cuti',
        'create_cuti',
        'update_cuti',
        'delete_cuti',
        'restore_cuti',
        // Izin permissions
        'view_any_izin',
        'view_izin',
        'create_izin',
        'update_izin',
        'delete_izin',
        'restore_izin',
        // Penggajian permissions
        'view_any_penggajian',
        'view_penggajian',
        'update_penggajian',
        'delete_penggajian',
        'restore_penggajian',
      ]);

      DB::table('role_has_permissions')->where('role_id', $managerHRDRole->role_id)->delete();
      foreach ($managerHRDPermissions as $permission) {
        DB::table('role_has_permissions')->updateOrInsert([
          'role_id' => $managerHRDRole->role_id,
          'permission_id' => $permission->permission_id
        ]);
      }
    }

    // Staff HRD permissions (termasuk lembur dan cuti)
    $staffHRDRole = Role::where('name', 'Staff HRD')->first();
    if ($staffHRDRole) {
      $staffHRDPermissions = $allPermissions->whereIn('name', [
        // Karyawan permissions
        'view_any_karyawan',
        'view_karyawan',
        'create_karyawan',
        'update_karyawan',
        // Absensi permissions
        'view_any_absensi',
        'view_absensi',
        'create_absensi',
        'update_absensi',
        // Lembur permissions
        'view_any_lembur',
        'view_lembur',
        'create_lembur',
        'update_lembur',
        // Cuti permissions
        'view_any_cuti',
        'view_cuti',
        'create_cuti',
        'update_cuti',
        // Izin permissions
        'view_any_izin',
        'view_izin',
        'create_izin',
        'update_izin',
        // Penggajian permissions
        'view_any_penggajian',
        'view_penggajian',
        'create_penggajian',
        'update_penggajian',
        'delete_penggajian',
        'restore_penggajian',
      ]);

      DB::table('role_has_permissions')->where('role_id', $staffHRDRole->role_id)->delete();
      foreach ($staffHRDPermissions as $permission) {
        DB::table('role_has_permissions')->updateOrInsert([
          'role_id' => $staffHRDRole->role_id,
          'permission_id' => $permission->permission_id
        ]);
      }
    }

    // Manager Finance permissions (bisa lihat lembur dan cuti untuk payroll)
    $managerFinanceRole = Role::where('name', 'Manager Finance')->first();
    if ($managerFinanceRole) {
      $managerFinancePermissions = $allPermissions->whereIn('name', [
        // Penggajian permissions
        'view_any_penggajian',
        'view_penggajian',
        'update_penggajian',
        'delete_penggajian',
        'restore_penggajian',
      ]);

      DB::table('role_has_permissions')->where('role_id', $managerFinanceRole->role_id)->delete();
      foreach ($managerFinancePermissions as $permission) {
        DB::table('role_has_permissions')->updateOrInsert([
          'role_id' => $managerFinanceRole->role_id,
          'permission_id' => $permission->permission_id
        ]);
      }
    }

    // Account Payment permissions
    $accountPaymentRole = Role::where('name', 'Account Payment')->first();
    if ($accountPaymentRole) {
      $accountPaymentPermissions = $allPermissions->whereIn('name', [
        // Penggajian permissions
        'view_any_penggajian',
        'view_penggajian',
        'update_penggajian',
        'delete_penggajian',
        'restore_penggajian',
      ]);

      DB::table('role_has_permissions')->where('role_id', $accountPaymentRole->role_id)->delete();
      foreach ($accountPaymentPermissions as $permission) {
        DB::table('role_has_permissions')->updateOrInsert([
          'role_id' => $accountPaymentRole->role_id,
          'permission_id' => $permission->permission_id
        ]);
      }
    }

    // Karyawan permissions (bisa manage absensi, lembur, dan cuti sendiri)
    $karyawanRole = Role::where('name', 'Karyawan')->first();
    if ($karyawanRole) {
      $karyawanPermissions = $allPermissions->whereIn('name', [
        'view_absensi',
        'create_absensi',
        'update_absensi',
        'view_lembur',
        'create_lembur',
        'update_lembur',
        'view_cuti',
        'create_cuti',
        'update_cuti'
      ]);

      DB::table('role_has_permissions')->where('role_id', $karyawanRole->role_id)->delete();
      foreach ($karyawanPermissions as $permission) {
        DB::table('role_has_permissions')->updateOrInsert([
          'role_id' => $karyawanRole->role_id,
          'permission_id' => $permission->permission_id
        ]);
      }
    }

    $this->command->info('Permissions assigned to roles successfully!');
  }
}
<?php

namespace App\Policies;

use App\Models\Karyawan;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the karyawan can view any models.
     */
    public function viewAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('view_any_role');
    }

    /**
     * Determine whether the karyawan can view the model.
     */
    public function view(Karyawan $karyawan, Role $role): bool
    {
        return $karyawan->can('view_role');
    }

    /**
     * Determine whether the karyawan can create models.
     */
    public function create(Karyawan $karyawan): bool
    {
        return $karyawan->can('create_role');
    }

    /**
     * Determine whether the karyawan can update the model.
     */
    public function update(Karyawan $karyawan, Role $role): bool
    {
        return $karyawan->can('update_role');
    }

    /**
     * Determine whether the karyawan can delete the model.
     */
    public function delete(Karyawan $karyawan, Role $role): bool
    {
        return $karyawan->can('delete_role');
    }

    /**
     * Determine whether the karyawan can bulk delete.
     */
    public function deleteAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('delete_any_role');
    }

    /**
     * Determine whether the karyawan can permanently delete.
     */
    public function forceDelete(Karyawan $karyawan, Role $role): bool
    {
        return $karyawan->can('{{ ForceDelete }}');
    }

    /**
     * Determine whether the karyawan can permanently bulk delete.
     */
    public function forceDeleteAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('{{ ForceDeleteAny }}');
    }

    /**
     * Determine whether the karyawan can restore.
     */
    public function restore(Karyawan $karyawan, Role $role): bool
    {
        return $karyawan->can('{{ Restore }}');
    }

    /**
     * Determine whether the karyawan can bulk restore.
     */
    public function restoreAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('{{ RestoreAny }}');
    }

    /**
     * Determine whether the karyawan can replicate.
     */
    public function replicate(Karyawan $karyawan, Role $role): bool
    {
        return $karyawan->can('{{ Replicate }}');
    }

    /**
     * Determine whether the karyawan can reorder.
     */
    public function reorder(Karyawan $karyawan): bool
    {
        return $karyawan->can('{{ Reorder }}');
    }
}

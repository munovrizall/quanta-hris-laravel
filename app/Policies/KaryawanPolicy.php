<?php

namespace App\Policies;

use App\Models\Karyawan;

use Illuminate\Auth\Access\HandlesAuthorization;

class KaryawanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the karyawan can view any models.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function viewAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('view_any_karyawan');
    }

    /**
     * Determine whether the karyawan can view the model.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function view(Karyawan $karyawan): bool
    {
        return $karyawan->can('view_karyawan');
    }

    /**
     * Determine whether the karyawan can create models.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function create(Karyawan $karyawan): bool
    {
        return $karyawan->can('create_karyawan');
    }

    /**
     * Determine whether the karyawan can update the model.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function update(Karyawan $karyawan): bool
    {
        return $karyawan->can('update_karyawan');
    }

    /**
     * Determine whether the karyawan can delete the model.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function delete(Karyawan $karyawan): bool
    {
        return $karyawan->can('delete_karyawan');
    }

    /**
     * Determine whether the karyawan can bulk delete.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function deleteAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('delete_any_karyawan');
    }

    /**
     * Determine whether the karyawan can permanently delete.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function forceDelete(Karyawan $karyawan): bool
    {
        return $karyawan->can('force_delete_karyawan');
    }

    /**
     * Determine whether the karyawan can permanently bulk delete.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function forceDeleteAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('force_delete_any_karyawan');
    }

    /**
     * Determine whether the karyawan can restore.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function restore(Karyawan $karyawan): bool
    {
        return $karyawan->can('restore_karyawan');
    }

    /**
     * Determine whether the karyawan can bulk restore.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function restoreAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('restore_any_karyawan');
    }

    /**
     * Determine whether the karyawan can bulk restore.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function replicate(Karyawan $karyawan): bool
    {
        return $karyawan->can('replicate_karyawan');
    }

    /**
     * Determine whether the karyawan can reorder.
     *
     * @param  \App\Models\Karyawan  $karyawan
     * @return bool
     */
    public function reorder(Karyawan $karyawan): bool
    {
        return $karyawan->can('reorder_karyawan');
    }
}

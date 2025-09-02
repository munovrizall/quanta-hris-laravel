<?php

namespace App\Policies;

use App\Models\Karyawan;
use App\Models\Perusahaan;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerusahaanPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the karyawan can view any models.
     */
    public function viewAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('view_any_perusahaan');
    }

    /**
     * Determine whether the karyawan can view the model.
     */
    public function view(Karyawan $karyawan, Perusahaan $perusahaan): bool
    {
        return $karyawan->can('view_perusahaan');
    }

    /**
     * Determine whether the karyawan can create models.
     */
    public function create(Karyawan $karyawan): bool
    {
        return $karyawan->can('create_perusahaan');
    }

    /**
     * Determine whether the karyawan can update the model.
     */
    public function update(Karyawan $karyawan, Perusahaan $perusahaan): bool
    {
        return $karyawan->can('update_perusahaan');
    }

    /**
     * Determine whether the karyawan can delete the model.
     */
    public function delete(Karyawan $karyawan, Perusahaan $perusahaan): bool
    {
        return $karyawan->can('delete_perusahaan');
    }

    /**
     * Determine whether the karyawan can bulk delete.
     */
    public function deleteAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('delete_any_perusahaan');
    }

    /**
     * Determine whether the karyawan can permanently delete.
     */
    public function forceDelete(Karyawan $karyawan, Perusahaan $perusahaan): bool
    {
        return $karyawan->can('force_delete_perusahaan');
    }

    /**
     * Determine whether the karyawan can permanently bulk delete.
     */
    public function forceDeleteAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('force_delete_any_perusahaan');
    }

    /**
     * Determine whether the karyawan can restore.
     */
    public function restore(Karyawan $karyawan, Perusahaan $perusahaan): bool
    {
        return $karyawan->can('restore_perusahaan');
    }

    /**
     * Determine whether the karyawan can bulk restore.
     */
    public function restoreAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('restore_any_perusahaan');
    }

    /**
     * Determine whether the karyawan can replicate.
     */
    public function replicate(Karyawan $karyawan, Perusahaan $perusahaan): bool
    {
        return $karyawan->can('replicate_perusahaan');
    }

    /**
     * Determine whether the karyawan can reorder.
     */
    public function reorder(Karyawan $karyawan): bool
    {
        return $karyawan->can('reorder_perusahaan');
    }
}
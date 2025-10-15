<?php

namespace App\Policies;

use App\Models\Karyawan;
use Illuminate\Auth\Access\HandlesAuthorization;

class SlipGajiPolicy
{
    use HandlesAuthorization;

    public function viewAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('view_any_slip_gaji');
    }

    public function view(Karyawan $karyawan): bool
    {
        return $karyawan->can('view_slip_gaji');
    }
}

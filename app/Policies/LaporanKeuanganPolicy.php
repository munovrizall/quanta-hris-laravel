<?php

namespace App\Policies;

use App\Models\Karyawan;
use Illuminate\Auth\Access\HandlesAuthorization;

class LaporanKeuanganPolicy
{
    use HandlesAuthorization;

    public function menu(Karyawan $karyawan): bool
    {
        return $karyawan->can('menu_laporan_keuangan');
    }

    public function viewAny(Karyawan $karyawan): bool
    {
        return $karyawan->can('view_any_laporan_keuangan');
    }
    
    public function view(Karyawan $karyawan): bool
    {
        return $karyawan->can('view_laporan_keuangan');
    }
}

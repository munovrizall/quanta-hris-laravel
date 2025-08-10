<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    /**
     * Konfigurasi untuk Primary Key Kustom.
     */
    protected $table = 'roles';
    protected $primaryKey = 'role_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'role_id',
        'role_name',
        'guard_name',
    ];

    /**
     * Mendefinisikan relasi bahwa satu Role dimiliki oleh banyak Karyawan.
     */
    public function karyawan(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'role_id', 'role_id');
    }
}
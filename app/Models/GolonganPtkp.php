<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GolonganPtkp extends Model
{
    use HasFactory;

    /**
     * Konfigurasi untuk Primary Key Kustom
     */
    protected $table = 'golongan_ptkp';
    protected $primaryKey = 'golongan_ptkp_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'golongan_ptkp_id',
        'nama_golongan_ptkp',
    ];

    /**
     * Mendefinisikan relasi bahwa satu Golongan PTKP dimiliki oleh banyak Karyawan.
     */
    public function karyawan(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'golongan_ptkp_id', 'golongan_ptkp_id');
    }
}
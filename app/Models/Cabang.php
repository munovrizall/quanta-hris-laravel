<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cabang extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Konfigurasi untuk Primary Key Kustom
     */
    protected $table = 'cabang';
    protected $primaryKey = 'cabang_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'cabang_id',
        'perusahaan_id',
        'nama_cabang',
        'alamat',
        'latitude',
        'longitude',
        'radius_lokasi',
    ];

    /**
     * Mengatur casting tipe data untuk atribut model.
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Mendefinisikan relasi bahwa satu Cabang milik satu Perusahaan.
     */
    public function perusahaan(): BelongsTo
    {
        return $this->belongsTo(Perusahaan::class, 'perusahaan_id', 'perusahaan_id');
    }

    /**
     * Mendefinisikan relasi bahwa satu Cabang memiliki banyak data Absensi.
     */
    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'cabang_id', 'cabang_id');
    }
}
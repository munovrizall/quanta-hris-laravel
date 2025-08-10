<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Absensi extends Model
{
    use HasFactory;

    /**
     * Konfigurasi untuk Primary Key Kustom.
     */
    protected $table = 'absensi';
    protected $primaryKey = 'absensi_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'absensi_id',
        'karyawan_id',
        'cabang_id',
        'tanggal',
        'waktu_masuk',
        'waktu_pulang',
        'status_masuk',
        'status_pulang',
        'durasi_telat',
        'durasi_pulang_cepat',
        'koordinat_masuk',
        'koordinat_pulang',
        'foto_masuk',
        'foto_pulang',
        'status_absensi',
    ];

    /**
     * Mengatur casting tipe data untuk atribut model.
     * Ini akan mengubah kolom tanggal/waktu menjadi instance Carbon.
     */
    protected $casts = [
        'tanggal' => 'date',
        'waktu_masuk' => 'datetime',
        'waktu_pulang' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi bahwa satu data Absensi milik satu Karyawan.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'karyawan_id');
    }

    /**
     * Mendefinisikan relasi bahwa satu data Absensi terjadi di satu Cabang.
     */
    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'cabang_id', 'cabang_id');
    }

    /**
     * Mendefinisikan relasi bahwa satu data Absensi bisa memiliki satu data Lembur.
     */
    public function lembur(): HasOne
    {
        return $this->hasOne(Lembur::class, 'absensi_id', 'absensi_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lembur extends Model
{
    use HasFactory;

    /**
     * Konfigurasi untuk Primary Key Kustom.
     */
    protected $table = 'lembur';
    protected $primaryKey = 'lembur_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'lembur_id',
        'karyawan_id',
        'absensi_id',
        'tanggal_lembur',
        'durasi_lembur',
        'deskripsi_pekerjaan',
        'dokumen_pendukung',
        'status_lembur',
        'alasan_penolakan',
        'approved_by',
        'approved_at',
    ];

    /**
     * Mengatur casting tipe data untuk atribut model.
     */
    protected $casts = [
        'tanggal_lembur' => 'date',
        'approved_at' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi bahwa satu data Lembur diajukan oleh satu Karyawan.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'karyawan_id');
    }

    /**
     * Mendefinisikan relasi bahwa satu data Lembur terkait dengan satu data Absensi.
     */
    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class, 'absensi_id', 'absensi_id');
    }

    /**
     * Mendefinisikan relasi bahwa satu data Lembur disetujui oleh satu Karyawan (Approver).
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'approved_by', 'karyawan_id');
    }
}
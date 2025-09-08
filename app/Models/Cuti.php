<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cuti extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Konfigurasi untuk Primary Key Kustom.
     */
    protected $table = 'cuti';
    protected $primaryKey = 'cuti_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'cuti_id',
        'karyawan_id',
        'jenis_cuti',
        'tanggal_mulai',
        'tanggal_selesai',
        'keterangan',
        'dokumen_pendukung',
        'status_cuti',
        'alasan_penolakan',
        'approver_id',
        'processed_at', // Changed from approved_at
    ];

    /**
     * Mengatur casting tipe data untuk atribut model.
     */
    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'processed_at' => 'datetime',
    ];

    /**
     * Mendefinisikan relasi bahwa satu data Cuti diajukan oleh satu Karyawan.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'karyawan_id');
    }

    /**
     * Mendefinisikan relasi bahwa satu data Cuti disetujui oleh satu Karyawan (Approver).
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'approver_id', 'karyawan_id');
    }

    /**
     * Accessor untuk menghitung durasi cuti
     */
    public function getDurasiCutiAttribute(): int
    {
        if ($this->tanggal_mulai && $this->tanggal_selesai) {
            $start = Carbon::parse($this->tanggal_mulai);
            $end = Carbon::parse($this->tanggal_selesai);
            return $start->diffInDays($end) + 1;
        }
        return 0;
    }
}
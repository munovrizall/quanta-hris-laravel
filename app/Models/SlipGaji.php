<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlipGaji extends Model
{
    use HasFactory;

    /**
     * Konfigurasi untuk Primary Key Kustom
     */
    protected $table = 'slip_gaji';
    protected $primaryKey = 'slip_gaji_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Kolom yang dapat diisi secara massal.
     */
    protected $fillable = [
        'slip_gaji_id',
        'penggajian_id',
        'karyawan_id',
        'gaji_pokok',
        'total_tunjangan',
        'total_insentif_lembur',
        'total_potongan_pph21',
        'total_potongan_bpjs',
        'total_potongan_penalty',
        'pendapatan_bersih',
    ];

    /**
     * Mengatur casting tipe data untuk atribut model.
     */
    protected $casts = [
        'gaji_pokok' => 'decimal:2',
        'total_tunjangan' => 'decimal:2',
        'total_insentif_lembur' => 'decimal:2',
        'total_potongan_pph21' => 'decimal:2',
        'total_potongan_bpjs' => 'decimal:2',
        'total_potongan_penalty' => 'decimal:2',
        'pendapatan_bersih' => 'decimal:2',
    ];

    /**
     * Mendefinisikan relasi bahwa satu Slip Gaji milik satu Penggajian.
     */
    public function penggajian(): BelongsTo
    {
        return $this->belongsTo(Penggajian::class, 'penggajian_id', 'penggajian_id');
    }

    /**
     * Mendefinisikan relasi bahwa satu Slip Gaji milik satu Karyawan.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'karyawan_id');
    }
}
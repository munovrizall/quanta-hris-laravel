<?php

namespace App\Models;

use App\Services\LemburService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Lembur extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lembur';
    protected $primaryKey = 'lembur_id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Hapus 'durasi_lembur' dari fillable karena tidak lagi digunakan sebagai input.
     * Ganti 'total_insentif' dengan nama yang lebih sesuai: 'total_upah_lembur'.
     */
    protected $fillable = [
        'lembur_id',
        'karyawan_id',
        'absensi_id',
        'tanggal_lembur',
        'durasi_lembur',
        'total_insentif',
        'deskripsi_pekerjaan',
        'dokumen_pendukung',
        'status_lembur',
        'alasan_penolakan',
        'approver_id',
        'processed_at',
    ];

    protected $casts = [
        'tanggal_lembur' => 'date',
        'processed_at' => 'datetime',
        'total_insentif' => 'double', // Tipe data disesuaikan
    ];

    /**
     * Relasi ke model Karyawan.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id', 'karyawan_id');
    }

    /**
     * Relasi ke model Absensi (ini adalah sumber data waktu).
     */
    public function absensi(): BelongsTo
    {
        return $this->belongsTo(Absensi::class, 'absensi_id', 'absensi_id');
    }

    /**
     * Relasi ke model Karyawan (Approver).
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'approver_id', 'karyawan_id');
    }

    /**
     * Method untuk menghitung insentif menggunakan LemburService - CENTRALIZED LOGIC
     *
     * @return float
     */
    public function calculateInsentif(): float
    {
        $lemburService = new LemburService();
        return $lemburService->calculateInsentifFromLembur($this);
    }
}
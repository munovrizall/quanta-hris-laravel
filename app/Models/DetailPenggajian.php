<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailPenggajian extends Model
{
  use HasFactory, SoftDeletes;

  /**
   * Nama tabel yang terhubung dengan model ini.
   */
  protected $table = 'detail_penggajian';

  /**
   * Kolom yang dapat diisi secara massal (mass assignable).
   */
  protected $fillable = [
    'penggajian_id',
    'karyawan_id',
    'gaji_pokok',
    'total_tunjangan',
    'total_lembur',
    'penghasilan_bruto',
    'potongan_alfa',
    'potongan_terlambat',
    'potongan_bpjs',
    'potongan_pph21',
    'penyesuaian',
    'catatan_penyesuaian',
    'total_potongan',
    'gaji_bersih',
  ];

  /**
   * Casting tipe data untuk atribut.
   * Memastikan semua kolom uang diperlakukan sebagai angka desimal.
   */
  protected $casts = [
    'gaji_pokok' => 'decimal:2',
    'total_tunjangan' => 'decimal:2',
    'total_lembur' => 'decimal:2',
    'penghasilan_bruto' => 'decimal:2',
    'potongan_alfa' => 'decimal:2',
    'potongan_terlambat' => 'decimal:2',
    'potongan_bpjs' => 'decimal:2',
    'potongan_pph21' => 'decimal:2',
    'penyesuaian' => 'decimal:2',
    'total_potongan' => 'decimal:2',
    'gaji_bersih' => 'decimal:2',
  ];

  /**
   * Mendefinisikan relasi "belongsTo" ke model Penggajian.
   * Satu detail penggajian dimiliki oleh satu penggajian utama.
   */
  public function penggajian(): BelongsTo
  {
    return $this->belongsTo(Penggajian::class, 'penggajian_id', 'penggajian_id');
  }

  /**
   * Mendefinisikan relasi "belongsTo" ke model Karyawan.
   * Satu detail penggajian dimiliki oleh satu karyawan.
   */
  public function karyawan(): BelongsTo
  {
    return $this->belongsTo(Karyawan::class, 'karyawan_id', 'karyawan_id');
  }
}
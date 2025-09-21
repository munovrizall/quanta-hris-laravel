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
    'gaji_pokok' => 'float',
    'total_tunjangan' => 'float',
    'total_lembur' => 'float',
    'penghasilan_bruto' => 'float',
    'potongan_alfa' => 'float',
    'potongan_terlambat' => 'float',
    'potongan_bpjs' => 'float',
    'potongan_pph21' => 'float',
    'penyesuaian' => 'float',
    'total_potongan' => 'float',
    'gaji_bersih' => 'float',
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
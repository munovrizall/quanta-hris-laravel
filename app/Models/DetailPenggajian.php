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
   * Primary key adalah string, bukan auto-increment
   */
  protected $primaryKey = 'detail_penggajian_id';
  public $incrementing = false;
  protected $keyType = 'string';

  /**
   * Kolom yang dapat diisi secara massal (mass assignable).
   */
  protected $fillable = [
    'detail_penggajian_id',
    'penggajian_id',
    'karyawan_id',
    'sudah_diproses',
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

  /**
   * Generate next Detail Penggajian ID
   */
  public static function generateNextId(): string
  {
    // Ambil semua ID yang ada (termasuk yang soft deleted)
    $allIds = static::withTrashed()
      ->pluck('detail_penggajian_id')
      ->map(function ($id) {
        // Ambil angka dari DP0001 -> 1
        return intval(substr($id, 2));
      })
      ->max();

    $nextNumber = ($allIds ?? 0) + 1;
    return 'DP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
  }

  /**
   * Boot method untuk auto-generate ID
   */
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      if (empty($model->detail_penggajian_id)) {
        $model->detail_penggajian_id = static::generateNextId();
      }
    });
  }
}
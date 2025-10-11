<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Penggajian extends Model
{
  use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

  protected $table = 'penggajian';
  protected $primaryKey = 'penggajian_id';
  public $incrementing = false;
  protected $keyType = 'string';

  protected $fillable = [
    'penggajian_id',
    'periode_bulan',
    'periode_tahun',
    'status_penggajian',
    'catatan_penolakan_draf',
    'karyawan_id',
    'sudah_ditransfer',
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

  protected $casts = [
    'periode_bulan' => 'integer',
    'periode_tahun' => 'integer',
    'sudah_ditransfer' => 'boolean',
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

  public function scopeForPeriode($query, int $bulan, int $tahun)
  {
    return $query->where('periode_bulan', $bulan)->where('periode_tahun', $tahun);
  }

  public function karyawan(): BelongsTo
  {
    return $this->belongsTo(Karyawan::class, 'karyawan_id', 'karyawan_id');
  }

  public function getPeriodeAttribute(): string
  {
    $namaBulan = [
      1 => 'Januari',
      2 => 'Februari',
      3 => 'Maret',
      4 => 'April',
      5 => 'Mei',
      6 => 'Juni',
      7 => 'Juli',
      8 => 'Agustus',
      9 => 'September',
      10 => 'Oktober',
      11 => 'November',
      12 => 'Desember',
    ];

    return $namaBulan[$this->periode_bulan] . ' ' . $this->periode_tahun;
  }

  public static function booted(): void
  {
    static::saved(function (self $model) {
      static::query()
        ->forPeriode($model->periode_bulan, $model->periode_tahun)
        ->where('penggajian_id', '!=', $model->penggajian_id)
        ->update([
          'status_penggajian' => $model->status_penggajian,
          'catatan_penolakan_draf' => $model->catatan_penolakan_draf,
          'updated_at' => now(),
        ]);
    });

  }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    'verified_by',
    'approved_by',
    'processed_by',
    'catatan_penolakan_draf'
  ];

  protected $casts = [
    'periode_bulan' => 'integer',
    'periode_tahun' => 'integer',
  ];

  public function verifier()
  {
    return $this->belongsTo(Karyawan::class, 'verified_by', 'karyawan_id');
  }

  public function approver()
  {
    return $this->belongsTo(Karyawan::class, 'approved_by', 'karyawan_id');
  }

  public function processor()
  {
    return $this->belongsTo(Karyawan::class, 'processed_by', 'karyawan_id');
  }

  // Comment dulu relasi SlipGaji sampai tabel dibuat
  // public function slipGaji()
  // {
  //     return $this->hasMany(SlipGaji::class, 'penggajian_id', 'penggajian_id');
  // }

  // Helper methods
  public function getPeriodeAttribute()
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
      12 => 'Desember'
    ];

    return $namaBulan[$this->periode_bulan] . ' ' . $this->periode_tahun;
  }

  // Comment dulu methods yang bergantung pada SlipGaji
  // public function getTotalKaryawanAttribute()
  // {
  //     return $this->slipGaji()->count();
  // }

  // public function getTotalGajiAttribute()
  // {
  //     return $this->slipGaji()->sum('total_gaji');
  // }
}
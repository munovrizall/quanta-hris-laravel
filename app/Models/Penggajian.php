<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Penggajian extends Model
{
  use HasFactory, Notifiable, HasApiTokens;

  protected $table = 'penggajian';
  protected $primaryKey = 'penggajian_id';
  public $incrementing = false;
  protected $keyType = 'string';

  protected $fillable = [
    'penggajian_id',
    'periode_bulan',
    'periode_tahun',
    'status_gaji',
    'verified_by',
    'approved_by',
    'processed_by',
    'catatan_penolakan_draf'
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

  public function slipGaji()
  {
    return $this->hasMany(SlipGaji::class, 'penggajian_id', 'penggajian_id');
  }
}
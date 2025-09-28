<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Perusahaan extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'perusahaan';
  protected $primaryKey = 'perusahaan_id';
  public $incrementing = false;
  protected $keyType = 'string';

  protected $fillable = [
    'perusahaan_id',
    'nama_perusahaan',
    'email',
    'nomor_telepon',
    'jam_masuk',
    'jam_pulang',
    'potongan_keterlambatan',
    'persen_bpjs_kesehatan',
    'persen_bpjs_jht',
    'persen_bpjs_jp',
    'batas_gaji_bpjs_kesehatan',
    'batas_gaji_bpjs_pensiun',
  ];

  public function karyawan()
  {
    return $this->hasMany(Karyawan::class, 'perusahaan_id', 'perusahaan_id');
  }

  public function cabang()
  {
    return $this->hasMany(Cabang::class, 'perusahaan_id', 'perusahaan_id');
  }
}
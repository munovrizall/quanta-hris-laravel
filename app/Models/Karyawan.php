<?php

namespace App\Models;

use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticable;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class Karyawan extends Authenticable implements FilamentUser, HasName
{
  use HasFactory, SoftDeletes, HasRoles;

  protected $table = 'karyawan';
  protected $primaryKey = 'karyawan_id';
  public $incrementing = false;
  protected $keyType = 'string';

  protected $fillable = [
    'karyawan_id',
    'role_id',
    'perusahaan_id',
    'golongan_ptkp_id',
    'nik',
    'nama_lengkap',
    'email',
    'password',
    'tanggal_lahir',
    'jenis_kelamin',
    'alamat',
    'nomor_telepon',
    'jabatan',
    'departemen',
    'status_kepegawaian',
    'tanggal_mulai_bekerja',
    'gaji_pokok',
    'nomor_rekening',
    'nama_pemilik_rekening',
    'nomor_bpjs_kesehatan',
    'face_embedding'
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'tanggal_lahir' => 'date',
    'tanggal_mulai_bekerja' => 'date',
    'gaji_pokok' => 'decimal:2',
    'face_embedding' => 'array',
  ];

  public function canAccessPanel(Panel $panel): bool
  {
    // Gunakan role_id langsung untuk pengecekan
    return $this->role_id === 'R01' || $this->role?->name === 'Admin';
  }

  public function isSuperAdmin(): bool
  {
    return $this->role_id === 'R0001' || $this->hasRole('Admin');
  }

  public function getFilamentName(): string
  {
    return $this->nama_lengkap;
  }

  // Relasi BelongsTo
  public function role()
  {
    return $this->belongsTo(Role::class, 'role_id', 'role_id');
  }

  public function perusahaan()
  {
    return $this->belongsTo(Perusahaan::class, 'perusahaan_id', 'perusahaan_id');
  }

  public function golonganPtkp()
  {
    return $this->belongsTo(GolonganPtkp::class, 'golongan_ptkp_id', 'golongan_ptkp_id');
  }

  // Relasi HasMany
  public function absensi()
  {
    return $this->hasMany(Absensi::class, 'karyawan_id', 'karyawan_id');
  }
  //... (relasi hasMany lainnya seperti cuti, izin, lembur, slipGaji)
}
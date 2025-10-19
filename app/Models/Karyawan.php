<?php

namespace App\Models;

use App\Services\Pph21Service;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticable;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Karyawan extends Authenticable implements FilamentUser, HasName
{
  use HasApiTokens, HasFactory, SoftDeletes, HasRoles;

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
    'status_pernikahan', // Tambahkan ini jika belum ada
    'tanggal_mulai_bekerja',
    'gaji_pokok',
    'tunjangan_jabatan',
    'tunjangan_makan_bulanan',
    'tunjangan_transport_bulanan',
    'kuota_cuti_tahunan',
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
    'tunjangan_jabatan',
    'tunjangan_makan_bulanan',
    'tunjangan_transport_bulanan',
    'kuota_cuti_tahunan',
    'nomor_rekening',
    'nama_pemilik_rekening',
    'nomor_bpjs_kesehatan',
    'face_embedding'
  ];

  public function canAccessPanel(Panel $panel): bool
  {
    // Gunakan role_id langsung untuk pengecekan
    return true;
  }

  public function isSuperAdmin(): bool
  {
    return $this->role_id === 'R01' || $this->hasRole('Admin');
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

  public function lembur()
  {
    return $this->hasMany(Lembur::class, 'karyawan_id', 'karyawan_id');
  }

  public function cuti()
  {
    return $this->hasMany(Cuti::class, 'karyawan_id', 'karyawan_id');
  }

  public function izin()
  {
    return $this->hasMany(Izin::class, 'karyawan_id', 'karyawan_id');
  }

  public function calculatePph21Deduction(): float
  {
    $pph21Service = new Pph21Service();
    return $pph21Service->calculateMonthlyPph21Deduction($this);
  }


  public static function getEloquentQuery(): Builder
  {
    return parent::getEloquentQuery()->with('role');
  }

  public function getRoleColorAttribute(): string
  {
    if (!$this->role)
      return 'secondary';

    return match (strtolower($this->role->name)) {
      'admin' => 'admin',
      'staff hrd' => 'hr',
      'manager hrd' => 'manager',
      'manager finance' => 'finance',
      'account payment' => 'finance',
      'ceo' => 'ceo',
      'karyawan' => 'karyawan',
      default => 'secondary',
    };
  }
}

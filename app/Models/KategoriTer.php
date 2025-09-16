<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriTer extends Model
{
  use HasFactory;

  // Penyesuaian
  protected $table = 'kategori_ter';
  protected $primaryKey = 'kategori_ter_id';
  public $incrementing = false;
  protected $keyType = 'string';

  protected $fillable = ['kategori_ter_id', 'nama_kategori', 'deskripsi'];

  // Relasi tetap, namun lebih baik definisikan key secara eksplisit
  public function golonganPtkp(): HasMany
  {
    return $this->hasMany(GolonganPtkp::class, 'kategori_ter_id', 'kategori_ter_id');
  }

  public function tarifTer(): HasMany
  {
    return $this->hasMany(TarifTer::class, 'kategori_ter_id', 'kategori_ter_id');
  }
}
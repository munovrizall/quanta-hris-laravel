<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarifTer extends Model
{
    use HasFactory;
    
    // Penyesuaian
    protected $table = 'tarif_ter';
    protected $primaryKey = 'tarif_ter_id';
    public $incrementing = false;
    protected $keyType = 'string';


    protected $fillable = [
        'tarif_ter_id',
        'kategori_ter_id',
        'batas_bawah',
        'batas_atas',
        'tarif',
    ];

    public function kategoriTer(): BelongsTo
    {
        return $this->belongsTo(KategoriTer::class, 'kategori_ter_id', 'kategori_ter_id');
    }
}
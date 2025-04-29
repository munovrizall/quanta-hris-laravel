<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "address",
        "latitude",
        "longitude",
        "radius_in_m"
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

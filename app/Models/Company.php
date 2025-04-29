<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "email",
        "phone",
        "time_in",
        "time_out"
    ];

    public function sites()
    {
        return $this->hasMany(Site::class);
    }
}

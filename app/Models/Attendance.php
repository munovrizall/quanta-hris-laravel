<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    /** @use HasFactory<\Database\Factories\AttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        "user_id",
        "date",
        "time_in",
        "time_out",
        "latlon_in",
        "latlon_out",
        "hours_worked",
        "is_late",
        "is_overtime"
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

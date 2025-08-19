<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends SpatieRole
{
    protected $table = 'roles';
    protected $primaryKey = 'role_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'role_id',
        'name',
        'guard_name',
    ];

    public function getKeyName()
    {
        return 'role_id';
    }

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Override method untuk relasi dengan permissions
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.role_has_permissions'),
            config('permission.column_names.role_pivot_key'),
            config('permission.column_names.permission_pivot_key')
        );
    }

    /**
     * Override method untuk relasi dengan users
     */
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            getModelForGuard($this->attributes['guard_name'] ?? config('auth.defaults.guard')),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.role_pivot_key'),
            config('permission.column_names.model_morph_key')
        );
    }

    /**
     * Relasi ke Karyawan
     */
    public function karyawan(): HasMany
    {
        return $this->hasMany(Karyawan::class, 'role_id', 'role_id');
    }
}
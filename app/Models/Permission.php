<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends SpatiePermission
{
    protected $table = 'permissions';
    protected $primaryKey = 'permission_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'permission_id',
        'name',
        'guard_name',
    ];

    public function getKeyName()
    {
        return 'permission_id';
    }

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Override method untuk relasi dengan roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('permission.table_names.role_has_permissions'),
            config('permission.column_names.permission_pivot_key'),
            config('permission.column_names.role_pivot_key')
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
            config('permission.table_names.model_has_permissions'),
            config('permission.column_names.permission_pivot_key'),
            config('permission.column_names.model_morph_key')
        );
    }
}
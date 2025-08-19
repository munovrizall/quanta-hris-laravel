<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermission extends CreateRecord
{
    protected static string $resource = PermissionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto generate permission_id jika tidak diisi
        if (empty($data['permission_id'])) {
            $lastPermission = \App\Models\Permission::orderBy('permission_id', 'desc')->first();
            $nextNumber = $lastPermission ? intval(substr($lastPermission->permission_id, 1)) + 1 : 1;
            $data['permission_id'] = 'P' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }

        $data['guard_name'] = 'web';
        
        return $data;
    }
}
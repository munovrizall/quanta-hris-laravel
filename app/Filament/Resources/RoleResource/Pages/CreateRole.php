<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto generate role_id jika tidak diisi
        if (empty($data['role_id'])) {
            $lastRole = \App\Models\Role::orderBy('role_id', 'desc')->first();
            $nextNumber = $lastRole ? intval(substr($lastRole->role_id, 1)) + 1 : 1;
            $data['role_id'] = 'R' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }

        $data['guard_name'] = 'web';
        
        return $data;
    }
}
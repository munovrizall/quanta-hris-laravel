<?php

namespace App\Filament\Pages\Auth;

use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Http\RedirectResponse;

class Login extends BaseLogin
{
    protected function authenticate(): ?RedirectResponse
    {
        $response = parent::authenticate();

        Notification::make()
            ->title('Berhasil login')
            ->success()
            ->send();

        return $response;
    }
}

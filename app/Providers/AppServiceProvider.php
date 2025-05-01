<?php

namespace App\Providers;

use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        DateTimePicker::configureUsing(fn(DateTimePicker $component) => $component->native(false));
    }
}

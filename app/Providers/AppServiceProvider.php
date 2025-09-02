<?php

namespace App\Providers;

use Filament\Forms\Components\DateTimePicker;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
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

        FilamentColor::register([
            // Default colors
            'danger' => Color::Red,
            'gray' => Color::Zinc,
            'info' => Color::Blue,
            'primary' => Color::Cyan,
            'success' => Color::Green,
            'warning' => Color::Amber,

            // Extended color palette
            'purple' => Color::Purple,
            'pink' => Color::Pink,
            'teal' => Color::Teal,
            'orange' => Color::Orange,
            'lime' => Color::Lime,
            'indigo' => Color::Indigo,
            'yellow' => Color::Yellow,
            'emerald' => Color::Emerald,
            'sky' => Color::Sky,
            'red' => Color::Red,
            'blue' => Color::Blue,
            'green' => Color::Green,
            'amber' => Color::Amber,
            'zinc' => Color::Zinc,
            'cyan' => Color::Cyan,

            // Additional colors
            'slate' => Color::Slate,
            'stone' => Color::Stone,
            'neutral' => Color::Neutral,
            'rose' => Color::Rose,
            'fuchsia' => Color::Fuchsia,
            'violet' => Color::Violet,
            'lightBlue' => Color::Sky,
            'coolGray' => Color::Gray,
            'warmGray' => Color::Stone,
            'trueGray' => Color::Neutral,
            'blueGray' => Color::Slate,

            // Custom semantic colors
            'brand' => Color::Cyan,
            'secondary' => Color::Slate,
            'accent' => Color::Purple,
            'muted' => Color::Gray,
            'destructive' => Color::Red,
            'constructive' => Color::Green,
            'informative' => Color::Blue,
            'cautionary' => Color::Amber,

            // Status colors
            'active' => Color::Green,
            'inactive' => Color::Gray,
            'pending' => Color::Yellow,
            'approved' => Color::Emerald,
            'rejected' => Color::Red,
            'draft' => Color::Slate,
            'published' => Color::Blue,
            'archived' => Color::Zinc,

            // Role-based colors (for HR system)
            'admin' => Color::Purple,
            'manager' => Color::Indigo,
            'hr' => Color::Teal,
            'finance' => Color::Emerald,
            'ceo' => Color::Amber,
            'karyawan' => Color::Blue,

            // Priority colors
            'low' => Color::Green,
            'medium' => Color::Yellow,
            'high' => Color::Orange,
            'urgent' => Color::Red,
            'critical' => Color::Rose,
        ]);
    }
}
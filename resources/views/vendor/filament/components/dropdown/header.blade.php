@php
    use Filament\Support\Enums\IconSize;
    use Illuminate\Support\Str;

    $user = auth()->user();
    $recentNotifications = collect();
    $totalUnreadNotifications = 0;

    if ($user) {
        $recentNotifications = $user->notifications()
            ->latest()
            ->limit(5)
            ->get();

        $totalUnreadNotifications = $user->unreadNotifications()->count();
    }
@endphp

@props([
    'color' => 'gray',
    'icon' => null,
    'iconSize' => IconSize::Medium,
    'tag' => 'div',
])

<{{ $tag }}
    {{
        $attributes
            ->class([
                'fi-dropdown-header flex w-full gap-2 p-3 text-sm',
                match ($color) {
                    'gray' => null,
                    default => 'fi-color-custom',
                },
                // @deprecated `fi-dropdown-header-color-*` has been replaced by `fi-color-*` and `fi-color-custom`.
                is_string($color) ? "fi-dropdown-header-color-{$color}" : null,
                is_string($color) ? "fi-color-{$color}" : null,
            ])
    }}
>
    @if (filled($icon))
        <x-filament::icon
            :icon="$icon"
            @class([
                'fi-dropdown-header-icon',
                match ($iconSize) {
                    IconSize::Small, 'sm' => 'h-4 w-4',
                    IconSize::Medium, 'md' => 'h-5 w-5',
                    IconSize::Large, 'lg' => 'h-6 w-6',
                    default => $iconSize,
                },
                match ($color) {
                    'gray' => 'text-gray-400 dark:text-gray-500',
                    default => 'text-custom-500 dark:text-custom-400',
                },
            ])
            @style([
                \Filament\Support\get_color_css_variables(
                    $color,
                    shades: [400, 500],
                    alias: 'dropdown.header.icon',
                ) => $color !== 'gray',
            ])
        />
    @endif

    <span
        @class([
            'fi-dropdown-header-label flex-1 truncate text-start',
            match ($color) {
                'gray' => 'text-gray-700 dark:text-gray-200',
                default => 'text-custom-600 dark:text-custom-400',
            },
        ])
        @style([
            \Filament\Support\get_color_css_variables(
                $color,
                shades: [400, 600],
                alias: 'dropdown.header.label',
            ) => $color !== 'gray',
        ])
    >
        @if($user)
            {{ $slot }}
            <br />
            {{ $user->nama_lengkap ?? $user->name }} ({{ $user->getRoleNames()->join(', ') }})
        @else
            {{ $slot }}
        @endif
    </span>

    @if($user)
        <div class="mt-2 w-full text-xs text-gray-600 dark:text-gray-300">
            <div class="flex items-center justify-between">
                <span class="font-semibold">Notifikasi</span>
                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                    {{ $totalUnreadNotifications }} belum dibaca
                </span>
            </div>

            <ul class="mt-1 space-y-1">
                @forelse($recentNotifications as $notification)
                    <li class="flex items-start gap-2 rounded bg-gray-100 p-2 text-[11px] text-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
                        <span class="mt-1 h-1.5 w-1.5 flex-none rounded-full {{ $notification->read_at ? 'bg-gray-400' : 'bg-primary-500 dark:bg-primary-400' }}"></span>
                        <div class="space-y-0.5">
                            <p class="font-medium">
                                {{ data_get($notification->data, 'title', Str::headline(class_basename($notification->type))) }}
                            </p>

                            @if(data_get($notification->data, 'message'))
                                <p class="text-gray-600 dark:text-gray-400">
                                    {{ Str::limit(data_get($notification->data, 'message'), 80) }}
                                </p>
                            @endif

                            <p class="text-[10px] text-gray-500 dark:text-gray-500">
                                {{ optional($notification->created_at)->diffForHumans() }}
                            </p>
                        </div>
                    </li>
                @empty
                    <li class="rounded bg-gray-50 p-2 text-[11px] text-gray-500 dark:bg-gray-800/40 dark:text-gray-400">
                        Belum ada notifikasi.
                    </li>
                @endforelse
            </ul>
        </div>
    @endif
</{{ $tag }}>

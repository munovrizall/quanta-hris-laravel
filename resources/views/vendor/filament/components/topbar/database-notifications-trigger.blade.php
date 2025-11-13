@once
    <style>
        .fi-topbar-database-notifications-btn .fi-icon-btn-badge-ctn .fi-badge {
            background-color: #dc2626 !important;
            color: #fff !important;
            box-shadow: 0 0 0 1px rgba(220, 38, 38, 0.4);
        }

        .dark .fi-topbar-database-notifications-btn .fi-icon-btn-badge-ctn .fi-badge {
            background-color: #b91c1c !important;
            color: #fff !important;
            box-shadow: 0 0 0 1px rgba(248, 113, 113, 0.35);
        }
    </style>
@endonce

<x-filament::icon-button
    :badge="$unreadNotificationsCount ?: null"
    badge-color="danger"
    color="gray"
    icon="heroicon-o-bell"
    icon-alias="panels::topbar.open-database-notifications-button"
    icon-size="lg"
    :label="__('filament-panels::layout.actions.open_database_notifications.label')"
    class="fi-topbar-database-notifications-btn"
/>

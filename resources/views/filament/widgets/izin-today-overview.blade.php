<x-filament-widgets::widget class="fi-izin-overview-widget">
    <style>
        .fi-izin-overview-widget .widget-container {
            border-radius: 0.75rem;
            background-color: #fff;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            border: 1px solid rgb(var(--gray-950) / 0.05);
            padding: 1.5rem;
        }

        .dark .fi-izin-overview-widget .widget-container {
            background-color: rgb(var(--gray-900));
            border-color: rgb(var(--white) / 0.1);
        }

        .fi-izin-overview-widget .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .fi-izin-overview-widget .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .fi-izin-overview-widget .widget-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: rgb(var(--gray-950));
        }

        .dark .fi-izin-overview-widget .widget-title {
            color: #fff;
        }

        .fi-izin-overview-widget .attendance-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            background-color: rgb(var(--success-100));
            color: rgb(var(--success-800));
        }

        .dark .fi-izin-overview-widget .attendance-badge {
            background-color: rgb(var(--success-500) / 0.35);
            color: rgb(var(--success-200));
        }

        .fi-izin-overview-widget .date-info {
            font-size: 0.875rem;
            color: rgb(var(--gray-500));
        }

        .dark .fi-izin-overview-widget .date-info {
            color: rgb(var(--gray-400));
        }

        .fi-izin-overview-widget .employees-scroll-container {
            width: 100%;
            overflow-x: auto;
        }

        .fi-izin-overview-widget .employees-list {
            display: flex;
            gap: 1.5rem;
            padding-bottom: 1rem;
            width: max-content;
        }

        .fi-izin-overview-widget .employee-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .fi-izin-overview-widget .avatar-container {
            position: relative;
        }

        .fi-izin-overview-widget .avatar {
            width: 4rem;
            height: 4rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 1.125rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        .fi-izin-overview-widget .avatar-color-primary {
            background-color: rgb(var(--primary-500));
        }

        .fi-izin-overview-widget .avatar-color-success {
            background-color: rgb(var(--success-500));
        }

        .fi-izin-overview-widget .avatar-color-danger {
            background-color: rgb(var(--danger-500));
        }

        .fi-izin-overview-widget .avatar-color-warning {
            background-color: rgb(var(--warning-500));
        }

        .fi-izin-overview-widget .avatar-color-purple {
            background-color: #a855f7;
        }

        .fi-izin-overview-widget .avatar-color-pink {
            background-color: #ec4899;
        }

        .fi-izin-overview-widget .avatar-color-indigo {
            background-color: #6366f1;
        }

        .fi-izin-overview-widget .avatar-color-teal {
            background-color: #14b8a6;
        }

        .fi-izin-overview-widget .avatar-color-orange {
            background-color: #f97316;
        }

        .fi-izin-overview-widget .avatar-color-cyan {
            background-color: #06b6d4;
        }

        .fi-izin-overview-widget .status-indicator {
            position: absolute;
            bottom: -0.25rem;
            right: -0.25rem;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 9999px;
            border: 3px solid #fff;
        }

        .dark .fi-izin-overview-widget .status-indicator {
            border-color: rgb(var(--gray-900));
        }

        .fi-izin-overview-widget .status-indicator.status-izin {
            background-color: rgb(var(--success-500));
        }

        .fi-izin-overview-widget .employee-info {
            text-align: center;
        }

        .fi-izin-overview-widget .employee-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgb(var(--gray-950));
            max-width: 10rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .fi-izin-overview-widget .employee-name {
            color: #fff;
        }

        .fi-izin-overview-widget .employee-time {
            font-size: 0.75rem;
            color: rgb(var(--gray-500));
            margin-top: 0.25rem;
            max-width: 8rem;
        }

        .dark .fi-izin-overview-widget .employee-time {
            color: rgb(var(--gray-400));
        }

        .fi-izin-overview-widget .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
            background-color: rgb(var(--success-100));
            color: rgb(var(--success-800));
        }

        .dark .fi-izin-overview-widget .status-badge {
            background-color: rgb(var(--success-500) / 0.35);
            color: rgb(var(--success-200));
        }

        .fi-izin-overview-widget .empty-state {
            text-align: center;
            padding: 3rem 0;
        }

        .fi-izin-overview-widget .empty-icon {
            margin: 0 auto 1rem;
            height: 4rem;
            width: 4rem;
            color: rgb(var(--gray-400));
        }

        .fi-izin-overview-widget .empty-title {
            font-size: 1.125rem;
            font-weight: 500;
            color: rgb(var(--gray-950));
            margin-bottom: 0.5rem;
        }

        .dark .fi-izin-overview-widget .empty-title {
            color: #fff;
        }

        .fi-izin-overview-widget .empty-description {
            font-size: 0.875rem;
            color: rgb(var(--gray-500));
        }

        .dark .fi-izin-overview-widget .empty-description {
            color: rgb(var(--gray-400));
        }

        .fi-izin-overview-widget .legend-container {
            border-top: 1px solid rgb(var(--gray-200));
            padding-top: 1rem;
            margin-top: 1.5rem;
        }

        .dark .fi-izin-overview-widget .legend-container {
            border-color: rgb(var(--gray-700));
        }

        .fi-izin-overview-widget .legend-items {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            font-size: 0.875rem;
            flex-wrap: wrap;
        }

        .fi-izin-overview-widget .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .fi-izin-overview-widget .legend-dot {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 9999px;
        }

        .fi-izin-overview-widget .legend-dot.status-izin {
            background-color: rgb(var(--success-500));
        }

        .fi-izin-overview-widget .legend-text {
            color: rgb(var(--gray-600));
        }

        .dark .fi-izin-overview-widget .legend-text {
            color: rgb(var(--gray-400));
        }
    </style>

    @php($izinHariIni = $this->getIzinHariIni())

    <div class="widget-container">
        <div class="widget-header">
            <div class="header-left">
                <h3 class="widget-title">
                    {{ $this->getHeading() }}
                </h3>
            </div>
        </div>

        @if($izinHariIni->count() > 0)
            <div class="employees-scroll-container">
                <div class="employees-list">
                    @foreach($izinHariIni as $izin)
                        <div class="employee-card">
                            <div class="avatar-container">
                                <div class="avatar {{ $izin['avatar_color_class'] }}">
                                    {{ $izin['initial'] }}
                                </div>
                                <div class="status-indicator status-izin"></div>
                            </div>

                            <div class="employee-info">
                                <p class="employee-name" title="{{ $izin['nama_lengkap'] }}">
                                    {{ Str::limit($izin['nama_lengkap'], 18) }}
                                </p>
                                <p class="employee-time">
                                    {{ $izin['periode'] }}
                                </p>
                                @if(!empty($izin['jenis']))
                                    <span class="status-badge">
                                        {{ $izin['jenis'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="empty-state">
                <div class="empty-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-9A2.25 2.25 0 002.25 5.25v9a2.25 2.25 0 002.25 2.25H9" />
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9 21 14.25m0 0v-3.75M21 14.25h-3.75" />
                    </svg>
                </div>
                <h3 class="empty-title">Belum Ada Izin</h3>
                <p class="empty-description">
                    Tidak ada karyawan yang mengambil izin hari ini.
                </p>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>

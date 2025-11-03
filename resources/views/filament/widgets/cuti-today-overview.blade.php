<x-filament-widgets::widget class="fi-cuti-overview-widget">
    <style>
        .fi-cuti-overview-widget .widget-container {
            border-radius: 0.75rem;
            background-color: #fff;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            border: 1px solid rgb(var(--gray-950) / 0.05);
            padding: 1.5rem;
        }

        .dark .fi-cuti-overview-widget .widget-container {
            background-color: rgb(var(--gray-900));
            border-color: rgb(var(--white) / 0.1);
        }

        .fi-cuti-overview-widget .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .fi-cuti-overview-widget .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .fi-cuti-overview-widget .widget-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: rgb(var(--gray-950));
        }

        .dark .fi-cuti-overview-widget .widget-title {
            color: #fff;
        }

        .fi-cuti-overview-widget .attendance-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            background-color: rgb(var(--primary-100));
            color: rgb(var(--primary-800));
        }

        .dark .fi-cuti-overview-widget .attendance-badge {
            background-color: rgb(var(--primary-500) / 0.5);
            color: rgb(var(--primary-300));
        }

        .fi-cuti-overview-widget .date-info {
            font-size: 0.875rem;
            color: rgb(var(--gray-500));
        }

        .dark .fi-cuti-overview-widget .date-info {
            color: rgb(var(--gray-400));
        }

        .fi-cuti-overview-widget .employees-scroll-container {
            width: 100%;
            overflow-x: auto;
        }

        .fi-cuti-overview-widget .employees-list {
            display: flex;
            gap: 1.5rem;
            padding-bottom: 1rem;
            width: max-content;
        }

        .fi-cuti-overview-widget .employee-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }

        .fi-cuti-overview-widget .avatar-container {
            position: relative;
        }

        .fi-cuti-overview-widget .avatar {
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

        .fi-cuti-overview-widget .avatar-color-primary {
            background-color: rgb(var(--primary-500));
        }

        .fi-cuti-overview-widget .avatar-color-success {
            background-color: rgb(var(--success-500));
        }

        .fi-cuti-overview-widget .avatar-color-danger {
            background-color: rgb(var(--danger-500));
        }

        .fi-cuti-overview-widget .avatar-color-warning {
            background-color: rgb(var(--warning-500));
        }

        .fi-cuti-overview-widget .avatar-color-purple {
            background-color: #a855f7;
        }

        .fi-cuti-overview-widget .avatar-color-pink {
            background-color: #ec4899;
        }

        .fi-cuti-overview-widget .avatar-color-indigo {
            background-color: #6366f1;
        }

        .fi-cuti-overview-widget .avatar-color-teal {
            background-color: #14b8a6;
        }

        .fi-cuti-overview-widget .avatar-color-orange {
            background-color: #f97316;
        }

        .fi-cuti-overview-widget .avatar-color-cyan {
            background-color: #06b6d4;
        }

        .fi-cuti-overview-widget .status-indicator {
            position: absolute;
            bottom: -0.25rem;
            right: -0.25rem;
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 9999px;
            border: 3px solid #fff;
        }

        .dark .fi-cuti-overview-widget .status-indicator {
            border-color: rgb(var(--gray-900));
        }

        .fi-cuti-overview-widget .status-indicator.status-cuti {
            background-color: rgb(var(--primary-500));
        }

        .fi-cuti-overview-widget .employee-info {
            text-align: center;
        }

        .fi-cuti-overview-widget .employee-name {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgb(var(--gray-950));
            max-width: 6rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .fi-cuti-overview-widget .employee-name {
            color: #fff;
        }

        .fi-cuti-overview-widget .employee-time {
            font-size: 0.75rem;
            color: rgb(var(--gray-500));
            margin-top: 0.25rem;
            max-width: 8rem;
        }

        .dark .fi-cuti-overview-widget .employee-time {
            color: rgb(var(--gray-400));
        }

        .fi-cuti-overview-widget .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            margin-top: 0.25rem;
            background-color: rgb(var(--primary-100));
            color: rgb(var(--primary-800));
        }

        .dark .fi-cuti-overview-widget .status-badge {
            background-color: rgb(var(--primary-500) / 0.5);
            color: rgb(var(--primary-200));
        }

        .fi-cuti-overview-widget .empty-state {
            text-align: center;
            padding: 3rem 0;
        }

        .fi-cuti-overview-widget .empty-icon {
            margin: 0 auto 1rem;
            height: 4rem;
            width: 4rem;
            color: rgb(var(--gray-400));
        }

        .fi-cuti-overview-widget .empty-title {
            font-size: 1.125rem;
            font-weight: 500;
            color: rgb(var(--gray-950));
            margin-bottom: 0.5rem;
        }

        .dark .fi-cuti-overview-widget .empty-title {
            color: #fff;
        }

        .fi-cuti-overview-widget .empty-description {
            font-size: 0.875rem;
            color: rgb(var(--gray-500));
        }

        .dark .fi-cuti-overview-widget .empty-description {
            color: rgb(var(--gray-400));
        }

        .fi-cuti-overview-widget .legend-container {
            border-top: 1px solid rgb(var(--gray-200));
            padding-top: 1rem;
            margin-top: 1.5rem;
        }

        .dark .fi-cuti-overview-widget .legend-container {
            border-color: rgb(var(--gray-700));
        }

        .fi-cuti-overview-widget .legend-items {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            font-size: 0.875rem;
            flex-wrap: wrap;
        }

        .fi-cuti-overview-widget .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .fi-cuti-overview-widget .legend-dot {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 9999px;
        }

        .fi-cuti-overview-widget .legend-dot.status-cuti {
            background-color: rgb(var(--primary-500));
        }

        .fi-cuti-overview-widget .legend-text {
            color: rgb(var(--gray-600));
        }

        .dark .fi-cuti-overview-widget .legend-text {
            color: rgb(var(--gray-400));
        }
    </style>

    @php($cutiHariIni = $this->getCutiHariIni())

    <div class="widget-container">
        <div class="widget-header">
            <div class="header-left">
                <h3 class="widget-title">
                    {{ $this->getHeading() }}
                </h3>
            </div>
        </div>

        @if($cutiHariIni->count() > 0)
            <div class="employees-scroll-container">
                <div class="employees-list">
                    @foreach($cutiHariIni as $cuti)
                        <div class="employee-card">
                            <div class="avatar-container">
                                <div class="avatar {{ $cuti['avatar_color_class'] }}">
                                    {{ $cuti['initial'] }}
                                </div>
                                <div class="status-indicator status-cuti"></div>
                            </div>

                            <div class="employee-info">
                                <p class="employee-name" title="{{ $cuti['nama_lengkap'] }}">
                                    {{ Str::limit($cuti['nama_lengkap'], 18) }}
                                </p>
                                <p class="employee-time">
                                    {{ $cuti['periode'] }}
                                </p>
                                @if(!empty($cuti['jenis']))
                                    <span class="status-badge">
                                        {{ $cuti['jenis'] }}
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
                            d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 class="empty-title">Belum Ada Cuti</h3>
                <p class="empty-description">
                    Tidak ada karyawan yang sedang cuti hari ini.
                </p>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>

<x-filament-widgets::widget class="fi-activity-overview-widget">
    <style>
        /* General Widget Styling */
        .fi-activity-overview-widget .widget-container {
            border-radius: 0.75rem;
            /* rounded-xl */
            background-color: #fff;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            /* shadow-sm */
            border: 1px solid rgb(var(--gray-950) / 0.05);
            padding: 1.5rem;
            /* p-6 */
        }

        /* Dark Mode Styling */
        .dark .fi-activity-overview-widget .widget-container {
            background-color: rgb(var(--gray-900));
            border-color: rgb(var(--white) / 0.1);
        }

        /* Header Section */
        .widget-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            /* mb-6 */
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            /* space-x-3 */
        }

        .widget-title {
            font-size: 1.125rem;
            /* text-lg */
            font-weight: 600;
            /* font-semibold */
            color: rgb(var(--gray-950));
        }

        .dark .widget-title {
            color: #fff;
        }

        .attendance-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            /* text-sm */
            font-weight: 500;
            background-color: rgb(var(--primary-100));
            color: rgb(var(--primary-800));
        }

        .dark .attendance-badge {
            background-color: rgb(var(--primary-500) / 0.5);
            color: rgb(var(--primary-300));
        }

        .date-info {
            font-size: 0.875rem;
            /* text-sm */
            color: rgb(var(--gray-500));
        }

        .dark .date-info {
            color: rgb(var(--gray-400));
        }

        /* Employee List Section */
        .employees-scroll-container {
            width: 100%;
            overflow-x: auto;
        }

        .employees-list {
            display: flex;
            gap: 1.5rem;
            /* space-x-6 */
            padding-bottom: 1rem;
            width: max-content;
        }

        .employee-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            /* space-y-3 */
            flex-shrink: 0;
        }

        /* Avatar Styling */
        .avatar-container {
            position: relative;
        }

        .avatar {
            width: 4rem;
            height: 4rem;
            /* w-16 h-16 */
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 1.125rem;
            /* text-lg */
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            /* shadow-lg */
        }

        /* Color classes generated from PHP */
        .avatar-color-primary {
            background-color: rgb(var(--primary-500));
        }

        .avatar-color-success {
            background-color: rgb(var(--success-500));
        }

        .avatar-color-danger {
            background-color: rgb(var(--danger-500));
        }

        .avatar-color-warning {
            background-color: rgb(var(--warning-500));
        }

        .avatar-color-purple {
            background-color: #a855f7;
        }

        .avatar-color-pink {
            background-color: #ec4899;
        }

        .avatar-color-indigo {
            background-color: #6366f1;
        }

        .avatar-color-teal {
            background-color: #14b8a6;
        }

        .avatar-color-orange {
            background-color: #f97316;
        }

        .avatar-color-cyan {
            background-color: #06b6d4;
        }

        /* Status Indicator on Avatar */
        .status-indicator {
            position: absolute;
            bottom: -0.25rem;
            right: -0.25rem;
            width: 1.25rem;
            height: 1.25rem;
            /* w-5 h-5 */
            border-radius: 9999px;
            border: 3px solid #fff;
        }

        .dark .status-indicator {
            border-color: rgb(var(--gray-900));
        }

        .status-hadir {
            background-color: rgb(var(--success-500));
        }

        .status-terlambat {
            background-color: rgb(var(--warning-500));
        }

        .status-default {
            background-color: rgb(var(--gray-500));
        }

        /* Employee Info Text */
        .employee-info {
            text-align: center;
        }

        .employee-name {
            font-size: 0.875rem;
            /* text-sm */
            font-weight: 500;
            color: rgb(var(--gray-950));
            max-width: 6rem;
            /* max-w-24 */
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .employee-name {
            color: #fff;
        }

        .employee-time {
            font-size: 0.75rem;
            /* text-xs */
            color: rgb(var(--gray-500));
            margin-top: 0.25rem;
        }

        .dark .employee-time {
            color: rgb(var(--gray-400));
        }

        /* Status Badge Below Name */
        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            /* text-xs */
            font-weight: 500;
            margin-top: 0.25rem;
        }

        .status-badge.status-hadir {
            background-color: rgb(var(--success-100));
            color: rgb(var(--success-800));
        }

        .dark .status-badge.status-hadir {
            background-color: rgb(var(--success-500) / 0.5);
            color: rgb(var(--success-300));
        }

        .status-badge.status-terlambat {
            background-color: rgb(var(--warning-100));
            color: rgb(var(--warning-800));
        }

        .dark .status-badge.status-terlambat {
            background-color: rgb(var(--warning-500) / 0.5);
            color: rgb(var(--warning-300));
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 0;
        }

        .empty-icon {
            margin: 0 auto 1rem;
            height: 4rem;
            width: 4rem;
            /* h-16 w-16 */
            color: rgb(var(--gray-400));
        }

        .empty-title {
            font-size: 1.125rem;
            /* text-lg */
            font-weight: 500;
            color: rgb(var(--gray-950));
            margin-bottom: 0.5rem;
        }

        .dark .empty-title {
            color: #fff;
        }

        .empty-description {
            font-size: 0.875rem;
            /* text-sm */
            color: rgb(var(--gray-500));
        }

        .dark .empty-description {
            color: rgb(var(--gray-400));
        }

        /* Legend */
        .legend-container {
            border-top: 1px solid rgb(var(--gray-200));
            padding-top: 1rem;
            margin-top: 1.5rem;
        }

        .dark .legend-container {
            border-color: rgb(var(--gray-700));
        }

        .legend-items {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            /* gap-6 */
            font-size: 0.875rem;
            /* text-sm */
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            /* space-x-2 */
        }

        .legend-dot {
            width: 0.75rem;
            height: 0.75rem;
            /* w-3 h-3 */
            border-radius: 9999px;
        }

        .legend-text {
            color: rgb(var(--gray-600));
        }

        .dark .legend-text {
            color: rgb(var(--gray-400));
        }

        /* Company Hours Info */
        .company-hours-info {
            background-color: rgb(var(--primary-50));
            border: 1px solid rgb(var(--primary-200));
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .dark .company-hours-info {
            background-color: rgb(var(--primary-500) / 0.1);
            border-color: rgb(var(--primary-500) / 0.3);
        }

        .company-hours-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .company-hours-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgb(var(--primary-700));
        }

        .dark .company-hours-item {
            color: rgb(var(--primary-300));
        }

        .hours-icon {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
        }

        /* Status Badge Enhancement */
        .status-badge.status-late-warning {
            background-color: rgb(var(--danger-100));
            color: rgb(var(--danger-800));
        }

        .dark .status-badge.status-late-warning {
            background-color: rgb(var(--danger-500) / 0.5);
            color: rgb(var(--danger-300));
        }

        /* Late Time Indicator */
        .late-time-info {
            font-size: 0.625rem;
            color: rgb(var(--danger-600));
            margin-top: 0.125rem;
            font-weight: 500;
        }

        .dark .late-time-info {
            color: rgb(var(--danger-400));
        }

        /* Enhanced Legend */
        .legend-items {
            display: flex;
            justify-content: center;
            gap: 1rem;
            font-size: 0.75rem;
            flex-wrap: wrap;
        }

        .legend-detail {
            color: rgb(var(--gray-500));
            font-size: 0.625rem;
            margin-left: 0.125rem;
        }

        .dark .legend-detail {
            color: rgb(var(--gray-500));
        }
    </style>

    <div class="widget-container">
        {{-- Header dengan statistik --}}
        <div class="widget-header">
            <div class="header-left">
                <h3 class="widget-title">
                    {{ $this->getHeading() }}
                </h3>
                <span class="attendance-badge">
                    {{ $this->getTotalAbsensiHariIni() }}/{{ $this->getTotalKaryawan() }}
                </span>
            </div>
        </div>

        {{-- Company Operational Hours Info --}}
        @if($this->getCompanyOperationalHours()->count() > 0)
            <div class="company-hours-info">
                <div class="company-hours-list">
                    @foreach($this->getCompanyOperationalHours() as $company)
                        <div class="company-hours-item">
                            <svg class="hours-icon" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <span>
                                <strong>{{ $company['nama'] }}:</strong>
                                {{ $company['jam_masuk'] }} - {{ $company['jam_pulang'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($this->getKaryawanAbsensiHariIni()->count() > 0)
            <div class="employees-scroll-container">
                <div class="employees-list">
                    @foreach($this->getKaryawanAbsensiHariIni() as $karyawan)
                        <div class="employee-card">
                            {{-- Avatar --}}
                            <div class="avatar-container">
                                <div class="avatar {{ $karyawan['avatar_color_class'] }}">
                                    {{ $karyawan['initial'] }}
                                </div>
                                <div @class([
                                    'status-indicator',
                                    'status-hadir' => $karyawan['status'] === 'Hadir',
                                    'status-terlambat' => $karyawan['status'] === 'Terlambat',
                                    'status-default' => !in_array($karyawan['status'], ['Hadir', 'Terlambat']),
                                ])>
                                </div>
                            </div>

                            {{-- Info Karyawan --}}
                            <div class="employee-info">
                                <p class="employee-name" title="{{ $karyawan['nama_lengkap'] }}">
                                    {{ Str::limit($karyawan['nama_lengkap'], 12) }}
                                </p>
                                <p class="employee-time">
                                    {{ $karyawan['waktu_masuk'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            {{-- Empty state --}}
            <div class="empty-state">
                <div class="empty-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.239" />
                    </svg>
                </div>
                <h3 class="empty-title">Belum Ada Absensi</h3>
                <p class="empty-description">
                    Belum ada karyawan yang melakukan absensi hari ini.
                </p>
            </div>
        @endif

        {{-- Enhanced Legend --}}
        <div class="legend-container">
            <div class="legend-items">
                <div class="legend-item">
                    <div class="legend-dot status-hadir"></div>
                    <span class="legend-text">
                        Hadir
                    </span>
                </div>
                <div class="legend-item">
                    <div class="legend-dot status-terlambat"></div>
                    <span class="legend-text">
                        Terlambat
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
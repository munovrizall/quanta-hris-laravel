<x-filament-widgets::widget class="fi-karyawan-gaji-widget">
    <style>
        /* General Widget Styling */
        .fi-karyawan-gaji-widget .karyawan-container {
            border-radius: 0.75rem;
            background-color: #fff;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            border: 1px solid rgb(156 163 175 / 0.2);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Dark Mode Styling */
        .dark .fi-karyawan-gaji-widget .karyawan-container {
            background-color: rgb(31 41 55);
            border-color: rgb(255 255 255 / 0.1);
        }

        /* Main Grid Layout */
        .karyawan-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .karyawan-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        /* Left Column - Employee Info */
        .employee-info-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .employee-header {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .employee-id {
            display: inline-flex;
            background-color: rgb(243 244 246);
            color: rgb(75 85 99);
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 0.375rem;
            font-weight: 500;
            width: fit-content;
        }

        .dark .employee-id {
            background-color: rgb(75 85 99);
            color: rgb(209 213 219);
        }

        .employee-name {
            font-size: 1.125rem;
            font-weight: 600;
            color: rgb(17 24 39);
            margin: 0;
        }

        .dark .employee-name {
            color: #fff;
        }

        .employee-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-weight: 500;
        }

        .badge-blue {
            background-color: rgb(219 234 254);
            color: rgb(30 64 175);
        }

        .dark .badge-blue {
            background-color: rgb(30 64 175 / 0.5);
            color: rgb(147 197 253);
        }

        .badge-purple {
            background-color: rgb(233 213 255);
            color: rgb(107 33 168);
        }

        .dark .badge-purple {
            background-color: rgb(107 33 168 / 0.5);
            color: rgb(196 181 253);
        }

        /* Attendance Stats */
        .attendance-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .attendance-item {
            text-align: center;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(229 231 235);
        }

        .dark .attendance-item {
            border-color: rgb(75 85 99);
        }

        .attendance-value {
            display: block;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .attendance-label {
            font-size: 0.75rem;
            color: rgb(107 114 128);
        }

        .dark .attendance-label {
            color: rgb(156 163 175);
        }

        .attendance-hadir {
            background-color: rgb(220 252 231);
            color: rgb(22 101 52);
        }

        .dark .attendance-hadir {
            background-color: rgb(22 101 52 / 0.5);
            color: rgb(134 239 172);
        }

        .attendance-alfa {
            background-color: rgb(254 226 226);
            color: rgb(153 27 27);
        }

        .dark .attendance-alfa {
            background-color: rgb(153 27 27 / 0.5);
            color: rgb(252 165 165);
        }

        .attendance-tidak-tepat {
            background-color: rgb(254 240 138);
            color: rgb(133 77 14);
        }

        .dark .attendance-tidak-tepat {
            background-color: rgb(133 77 14 / 0.5);
            color: rgb(253 230 138);
        }

        .attendance-lembur {
            background-color: rgb(219 234 254);
            color: rgb(30 64 175);
        }

        .dark .attendance-lembur {
            background-color: rgb(30 64 175 / 0.5);
            color: rgb(147 197 253);
        }

        /* Right Column - Salary Details */
        .salary-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .salary-group {
            background-color: rgb(249 250 251);
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid rgb(229 231 235);
        }

        .dark .salary-group {
            background-color: rgb(17 24 39);
            border-color: rgb(75 85 99);
        }

        .salary-group-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: rgb(75 85 99);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dark .salary-group-title {
            color: rgb(209 213 219);
        }

        .salary-items {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .salary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
        }

        .salary-label {
            color: rgb(107 114 128);
            font-weight: 500;
        }

        .dark .salary-label {
            color: rgb(156 163 175);
        }

        .salary-value {
            font-weight: 600;
            color: rgb(17 24 39);
        }

        .dark .salary-value {
            color: #fff;
        }

        .salary-positive {
            color: rgb(22 101 52);
        }

        .dark .salary-positive {
            color: rgb(134 239 172);
        }

        .salary-negative {
            color: rgb(153 27 27);
        }

        .dark .salary-negative {
            color: rgb(252 165 165);
        }

        /* Detail breakdown untuk potongan */
        .breakdown-detail {
            font-size: 0.75rem;
            color: rgb(107 114 128);
            margin-left: 0.5rem;
            font-style: italic;
        }

        .dark .breakdown-detail {
            color: rgb(156 163 175);
        }

        /* Total Salary */
        .total-salary {
            border-top: 2px solid rgb(229 231 235);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .dark .total-salary {
            border-color: rgb(75 85 99);
        }

        .total-salary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-salary-label {
            font-size: 1.125rem;
            font-weight: 700;
            color: rgb(17 24 39);
        }

        .dark .total-salary-label {
            color: #fff;
        }

        .total-salary-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: rgb(22 101 52);
        }

        .dark .total-salary-value {
            color: rgb(134 239 172);
        }

        /* Custom Pagination Styles with Cyan Primary Color */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 2rem;
            padding: 1rem;
            flex-direction: column;
            gap: 1rem;
        }

        @media (min-width: 640px) {
            .pagination-container {
                flex-direction: row;
                gap: 0;
            }
        }

        .pagination {
            display: flex;
            gap: 0.25rem;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination a,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.75rem;
            min-width: 2.5rem;
            height: 2.5rem;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.5rem;
            text-decoration: none;
            color: rgb(75 85 99);
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            background-color: white;
        }

        /* Dark Mode Base Styles */
        .dark .pagination a,
        .dark .pagination span {
            border-color: rgb(75 85 99);
            color: rgb(209 213 219);
            background-color: rgb(31 41 55);
        }

        /* Hover State with Cyan */
        .pagination a:hover {
            background-color: rgb(236 254 255);
            /* cyan-50 */
            border-color: rgb(6 182 212);
            /* cyan-500 */
            color: rgb(14 116 144);
            /* cyan-700 */
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }

        .dark .pagination a:hover {
            background-color: rgb(6 182 212 / 0.1);
            border-color: rgb(6 182 212);
            color: rgb(103 232 249);
            /* cyan-300 */
        }

        /* Current/Active Page with Cyan */
        .pagination .current {
            background: linear-gradient(135deg, rgb(6 182 212), rgb(8 145 178));
            /* cyan-500 to cyan-600 */
            color: white;
            border-color: rgb(6 182 212);
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgb(6 182 212 / 0.3), 0 2px 4px -2px rgb(6 182 212 / 0.1);
        }

        .dark .pagination .current {
            background: linear-gradient(135deg, rgb(6 182 212), rgb(8 145 178));
            box-shadow: 0 4px 6px -1px rgb(6 182 212 / 0.4), 0 2px 4px -2px rgb(6 182 212 / 0.2);
        }

        /* Disabled State */
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
            background-color: rgb(249 250 251);
            color: rgb(156 163 175);
        }

        .dark .pagination .disabled {
            background-color: rgb(17 24 39);
            color: rgb(107 114 128);
        }

        /* Pagination Info */
        .pagination-info {
            font-size: 0.875rem;
            color: rgb(107 114 128);
            text-align: center;
            margin: 0;
        }

        @media (min-width: 640px) {
            .pagination-info {
                margin-left: 1rem;
                text-align: left;
            }
        }

        .dark .pagination-info {
            color: rgb(156 163 175);
        }

        /* Enhanced styling for better UX */
        .pagination a:focus {
            outline: 2px solid rgb(6 182 212);
            outline-offset: 2px;
        }

        .pagination a:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        /* Responsive adjustments */
        @media (max-width: 480px) {

            .pagination a,
            .pagination span {
                padding: 0.375rem 0.5rem;
                min-width: 2rem;
                height: 2rem;
                font-size: 0.8rem;
            }

            .pagination {
                gap: 0.125rem;
            }
        }

        /* Loading state animation (optional) */
        .pagination a.loading {
            pointer-events: none;
            opacity: 0.7;
            position: relative;
        }

        .pagination a.loading::after {
            content: '';
            position: absolute;
            width: 1rem;
            height: 1rem;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Icons */
        .icon {
            width: 1rem;
            height: 1rem;
            fill: currentColor;
        }
    </style>

    <div class="space-y-4">
        @foreach($karyawanData as $karyawan)
            <div class="karyawan-container">
                <div class="karyawan-grid">
                    <!-- Left Column - Employee Info -->
                    <div class="employee-info-section">
                        <!-- Employee Header -->
                        <div class="employee-header">
                            <span class="employee-id">
                                {{ $karyawan['karyawan_id'] }}
                            </span>
                            <h3 class="employee-name">
                                {{ $karyawan['nama_lengkap'] }}
                            </h3>
                            <div class="employee-badges">
                                <span class="badge badge-blue">
                                    {{ $karyawan['jabatan'] }}
                                </span>
                                <span class="badge badge-purple">
                                    {{ $karyawan['departemen'] }}
                                </span>
                            </div>
                        </div>

                        <!-- Attendance Stats -->
                        <div class="attendance-stats">
                            <div class="attendance-item">
                                <span class="attendance-value attendance-hadir">
                                    {{ $karyawan['total_hadir'] }}
                                </span>
                                <span class="attendance-label">Hadir</span>
                            </div>
                            <div class="attendance-item">
                                <span class="attendance-value attendance-alfa">
                                    {{ $karyawan['total_alfa'] }}
                                </span>
                                <span class="attendance-label">Alfa</span>
                            </div>
                            <div class="attendance-item">
                                <span class="attendance-value attendance-tidak-tepat">
                                    {{ $karyawan['total_tidak_tepat'] }}
                                </span>
                                <span class="attendance-label">Tidak Tepat</span>
                            </div>
                            <div class="attendance-item">
                                <span class="attendance-value attendance-lembur">
                                    {{ $karyawan['total_lembur'] }}
                                </span>
                                <span class="attendance-label">Jam Lembur</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Salary Details -->
                    <div class="salary-section">
                        <!-- Income Section -->
                        <div class="salary-group">
                            <div class="salary-group-title">
                                <svg class="icon" viewBox="0 0 20 20">
                                    <path
                                        d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" />
                                </svg>
                                Pendapatan
                            </div>
                            <div class="salary-items">
                                <div class="salary-item">
                                    <span class="salary-label">Gaji Pokok:</span>
                                    <span class="salary-value">Rp
                                        {{ number_format($karyawan['gaji_pokok'], 0, ',', '.') }}</span>
                                </div>
                                <div class="salary-item">
                                    <span class="salary-label">Tunjangan:</span>
                                    <span class="salary-value">Rp
                                        {{ number_format($karyawan['tunjangan_total'], 0, ',', '.') }}</span>
                                </div>
                                @if($karyawan['total_lembur'] > 0)
                                    <div class="salary-item">
                                        <span class="salary-label">Upah Lembur:</span>
                                        <span class="salary-value salary-positive">Rp
                                            {{ number_format($karyawan['lembur_pay'], 0, ',', '.') }}</span>
                                    </div>
                                    <div class="breakdown-detail">
                                        {{ $karyawan['total_lembur'] }} jam × Rp
                                        {{ number_format(($karyawan['gaji_pokok'] / (22 * 8)) * 1.5, 0, ',', '.') }}/jam
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Deduction Section -->
                        <div class="salary-group">
                            <div class="salary-group-title">
                                <svg class="icon" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" />
                                </svg>
                                Potongan
                            </div>
                            <div class="salary-items">
                                @if($karyawan['total_alfa'] > 0)
                                    <div class="salary-item">
                                        <span class="salary-label">Potongan Alfa:</span>
                                        <span class="salary-value salary-negative">Rp
                                            {{ number_format($karyawan['total_alfa'] * (($karyawan['gaji_pokok'] / (22 * 8)) * 8), 0, ',', '.') }}</span>
                                    </div>
                                    <div class="breakdown-detail">
                                        {{ $karyawan['total_alfa'] }} hari × Rp
                                        {{ number_format(($karyawan['gaji_pokok'] / (22 * 8)) * 8, 0, ',', '.') }}/hari
                                    </div>
                                @endif

                                @if($karyawan['total_tidak_tepat'] > 0)
                                    <div class="salary-item">
                                        <span class="salary-label">Potongan Terlambat:</span>
                                        <span class="salary-value salary-negative">Rp
                                            {{ number_format($karyawan['total_tidak_tepat'] * (($karyawan['gaji_pokok'] / (22 * 8)) * 4), 0, ',', '.') }}</span>
                                    </div>
                                    <div class="breakdown-detail">
                                        {{ $karyawan['total_tidak_tepat'] }} hari × Rp
                                        {{ number_format(($karyawan['gaji_pokok'] / (22 * 8)) * 4, 0, ',', '.') }}/hari (50%
                                        gaji harian)
                                    </div>
                                @endif

                                <div class="salary-item">
                                    <span class="salary-label">BPJS (4%):</span>
                                    <span class="salary-value salary-negative">Rp
                                        {{ number_format($karyawan['gaji_pokok'] * 0.04, 0, ',', '.') }}</span>
                                </div>

                                <div class="salary-item">
                                    <span class="salary-label">Pajak PPh21:</span>
                                    <span class="salary-value salary-negative">Rp
                                        {{ number_format($karyawan['pph21_detail']['jumlah'], 0, ',', '.') }}</span>
                                </div>
                                <div class="breakdown-detail">
                                    {{ $karyawan['pph21_detail']['tarif_persen'] }}% dari penghasilan bruto Rp
                                    {{ number_format($karyawan['pph21_detail']['penghasilan_bruto'], 0, ',', '.') }}
                                </div>
                                <div class="breakdown-detail">
                                    PTKP: {{ $karyawan['pph21_detail']['golongan_ptkp'] }}
                                    ({{ $karyawan['pph21_detail']['kategori_ter'] }})
                                </div>

                                <div class="salary-item"
                                    style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgb(229 231 235);">
                                    <span class="salary-label" style="font-weight: 600;">Total Potongan:</span>
                                    <span class="salary-value salary-negative">Rp
                                        {{ number_format($karyawan['potongan_total'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Salary -->
                        <div class="total-salary">
                            <div class="total-salary-item">
                                <span class="total-salary-label">GAJI BERSIH:</span>
                                <span class="total-salary-value">
                                    Rp {{ number_format($karyawan['total_gaji'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Pagination -->
        @if(isset($pagination))
            <div class="pagination-container">
                <div class="pagination">
                    {{-- Previous Page Link --}}
                    @if ($pagination->onFirstPage())
                        <span class="disabled">« Sebelumnya</span>
                    @else
                        <a href="{{ $pagination->previousPageUrl() }}">« Sebelumnya</a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($pagination->getUrlRange(1, $pagination->lastPage()) as $page => $url)
                        @if ($page == $pagination->currentPage())
                            <span class="current">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($pagination->hasMorePages())
                        <a href="{{ $pagination->nextPageUrl() }}">Selanjutnya »</a>
                    @else
                        <span class="disabled">Selanjutnya »</span>
                    @endif
                </div>

                <div class="pagination-info">
                    Menampilkan {{ $pagination->firstItem() }} - {{ $pagination->lastItem() }} dari
                    {{ $pagination->total() }}
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
{{-- Include external CSS --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/karyawan-gaji-detail.css') }}">
@endpush

<x-filament-widgets::widget class="fi-karyawan-gaji-widget">
    <div x-data="{
        editModal: null
    }" x-init="
        window.addEventListener('open-modal', (event) => {
            if (event.detail.action === 'editKaryawanGaji') {
                // Use Livewire to call the method
                @this.call('openEditModal', event.detail.detailId);
            }
        });
    ">

        <div class="space-y-4">
            @foreach($karyawanData as $karyawan)
                <div class="karyawan-container">
                    @if(
                            isset($karyawan['status_penggajian'], $karyawan['sudah_diproses']) &&
                            $karyawan['status_penggajian'] === 'Disetujui' &&
                            !$karyawan['sudah_diproses'] &&
                            (auth()->user() && auth()->user()->role_id === 'R05')
                        )
                        <button class="edit-button" wire:click="markKaryawanTransfer('{{ $karyawan['detail_id'] }}')"
                            type="button" style="background-color: #22c55e; color: white;">
                            Sudah Transfer
                        </button>
                    @endif

                    {{-- Edit Button - Updated to use Alpine.js --}}
                    @if(isset($canEdit) && $canEdit)
                        <button class="edit-button" x-on:click="window.dispatchEvent(new CustomEvent('open-modal', {
                                        detail: { 
                                            action: 'editKaryawanGaji',
                                            detailId: @js($karyawan['detail_id'])
                                        }
                                    }))" type="button">
                            <svg class="edit-icon" viewBox="0 0 20 20">
                                <path
                                    d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                <path
                                    d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                            </svg>
                            Edit Gaji
                        </button>
                    @endif

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

                            <!-- Attendance Stats - Now 3x2 Grid -->
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
                                    <span class="attendance-value attendance-cuti">
                                        {{ $karyawan['total_cuti'] }}
                                    </span>
                                    <span class="attendance-label">Cuti</span>
                                </div>
                                <div class="attendance-item">
                                    <span class="attendance-value attendance-izin">
                                        {{ $karyawan['total_izin'] }}
                                    </span>
                                    <span class="attendance-label">Izin</span>
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

                                    <!-- BREAKDOWN TUNJANGAN - SETIAP ITEM TERPISAH -->
                                    @if(isset($karyawan['tunjangan_breakdown']) && !empty($karyawan['tunjangan_breakdown']['breakdown']))
                                        @foreach($karyawan['tunjangan_breakdown']['breakdown'] as $item)
                                            <div class="salary-item">
                                                <span class="salary-label">{{ $item['label'] }}:</span>
                                                <span class="salary-value salary-positive">Rp
                                                    {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    @endif

                                    @if($karyawan['total_lembur'] > 0)
                                        <div class="salary-item">
                                            <span class="salary-label">Upah Lembur:</span>
                                            <span class="salary-value salary-positive">Rp
                                                {{ number_format($karyawan['lembur_pay'], 0, ',', '.') }}</span>
                                        </div>
                                        <div class="breakdown-detail">
                                            {{ $karyawan['total_lembur'] }} jam ({{ $karyawan['total_lembur_sessions'] }} sesi)
                                        </div>
                                    @endif

                                    <div class="salary-item"
                                        style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgb(229 231 235);">
                                        <span class="salary-label" style="font-weight: 600;">Total Pendapatan:</span>
                                        <span class="salary-value salary-positive">Rp
                                            {{ number_format($karyawan['pph21_detail']['penghasilan_bruto'], 0, ',', '.') }}
                                        </span>
                                    </div>
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
                                                {{ number_format($karyawan['potongan_detail']['alfa']['total_potongan'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="breakdown-detail">
                                            {{ $karyawan['total_alfa'] }} hari × Rp
                                            {{ number_format($karyawan['potongan_detail']['alfa']['potongan_per_hari'], 0, ',', '.') }}/hari
                                        </div>
                                    @endif

                                    @if($karyawan['total_tidak_tepat'] > 0)
                                        <div class="salary-item">
                                            <span class="salary-label">Potongan Terlambat:</span>
                                            <span class="salary-value salary-negative">Rp
                                                {{ number_format($karyawan['potongan_detail']['keterlambatan']['total_potongan'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="breakdown-detail">
                                            {{ $karyawan['total_tidak_tepat'] }} hari × Rp
                                            {{ number_format($karyawan['potongan_detail']['keterlambatan']['potongan_per_hari'], 0, ',', '.') }}/hari
                                        </div>
                                    @endif

                                    {{-- BPJS Components --}}
                                    @if(isset($karyawan['bpjs_breakdown']) && !empty($karyawan['bpjs_breakdown']['breakdown']))
                                        @foreach($karyawan['bpjs_breakdown']['breakdown'] as $item)
                                            <div class="salary-item">
                                                <span class="salary-label">{{ $item['label'] }}:</span>
                                                <span class="salary-value salary-negative">Rp
                                                    {{ number_format($item['amount'], 0, ',', '.') }}</span>
                                            </div>
                                            <div class="breakdown-detail">
                                                {{ $item['description'] }}
                                            </div>
                                        @endforeach
                                    @endif

                                    {{-- PPh21 --}}
                                    <div class="salary-item">
                                        <span class="salary-label">Pajak PPh21:</span>
                                        <span class="salary-value salary-negative">Rp
                                            {{ number_format($karyawan['pph21_detail']['jumlah'], 0, ',', '.') }}</span>
                                    </div>
                                    <div class="breakdown-detail">
                                        {{ $karyawan['pph21_detail']['tarif_persen'] }} dari penghasilan bruto Rp
                                        {{ number_format($karyawan['pph21_detail']['penghasilan_bruto'], 0, ',', '.') }}
                                    </div>
                                    <div class="breakdown-detail">
                                        PTKP: {{ $karyawan['pph21_detail']['golongan_ptkp'] }}
                                        ({{ $karyawan['pph21_detail']['kategori_ter'] }})
                                    </div>

                                    {{-- Total Potongan --}}
                                    <div class="salary-item"
                                        style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgb(229 231 235);">
                                        <span class="salary-label" style="font-weight: 600;">Total Potongan:</span>
                                        <span class="salary-value salary-negative">Rp
                                            {{ number_format($karyawan['potongan_total'], 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Penyesuaian Section - Only show if there's an adjustment --}}
                            @if(isset($karyawan['penyesuaian']) && ($karyawan['penyesuaian'] != 0 || !empty($karyawan['catatan_penyesuaian'])))
                                <div class="penyesuaian-section">
                                    <div class="penyesuaian-title">
                                        <svg class="icon" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" />
                                        </svg>
                                        Penyesuaian
                                    </div>
                                    <div class="salary-items">
                                        <div class="salary-item">
                                            <span class="salary-label">Jumlah Penyesuaian:</span>
                                            <span
                                                class="salary-value {{ $karyawan['penyesuaian'] >= 0 ? 'salary-positive' : 'salary-negative' }}">
                                                {{ $karyawan['penyesuaian'] >= 0 ? '+' : '' }}Rp
                                                {{ number_format($karyawan['penyesuaian'], 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    @if(!empty($karyawan['catatan_penyesuaian']))
                                        <div class="penyesuaian-note">
                                            <strong>Catatan:</strong> {{ $karyawan['catatan_penyesuaian'] }}
                                        </div>
                                    @endif
                                </div>
                            @endif

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

            <!-- PAGINATION -->
            @if(isset($pagination) && $pagination->hasPages())
                <div class="pagination-container">
                    <div class="pagination-links">
                        {{-- Previous Page Link --}}
                        @if ($pagination->onFirstPage())
                            <span class="disabled">« Sebelumnya</span>
                        @else
                            <a href="{{ $pagination->previousPageUrl() }}">« Sebelumnya</a>
                        @endif

                        {{-- Smart Page Numbers (show max 5 pages) --}}
                        @php
                            $start = max(1, $pagination->currentPage() - 2);
                            $end = min($pagination->lastPage(), $pagination->currentPage() + 2);
                        @endphp

                        {{-- First Page --}}
                        @if($start > 1)
                            <a href="{{ $pagination->url(1) }}">1</a>
                            @if($start > 2)
                                <span class="disabled">...</span>
                            @endif
                        @endif

                        {{-- Page Range --}}
                        @for($i = $start; $i <= $end; $i++)
                            @if ($i == $pagination->currentPage())
                                <span class="current">{{ $i }}</span>
                            @else
                                <a href="{{ $pagination->url($i) }}">{{ $i }}</a>
                            @endif
                        @endfor

                        {{-- Last Page --}}
                        @if($end < $pagination->lastPage())
                            @if($end < $pagination->lastPage() - 1)
                                <span class="disabled">...</span>
                            @endif
                            <a href="{{ $pagination->url($pagination->lastPage()) }}">{{ $pagination->lastPage() }}</a>
                        @endif

                        {{-- Next Page Link --}}
                        @if ($pagination->hasMorePages())
                            <a href="{{ $pagination->nextPageUrl() }}">Selanjutnya »</a>
                        @else
                            <span class="disabled">Selanjutnya »</span>
                        @endif
                    </div>

                    <div class="pagination-info">
                        Menampilkan {{ $pagination->firstItem() }} - {{ $pagination->lastItem() }} dari
                        {{ $pagination->total() }} karyawan
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
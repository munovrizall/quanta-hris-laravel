{{-- Include external CSS --}}
@push('styles')
  <link rel="stylesheet" href="{{ asset('css/slip-gaji-detail.css') }}">
@endpush

<x-filament-widgets::widget class="fi-karyawan-gaji-widget">
  <div class="slip-gaji-grid">
    @foreach($karyawanData as $karyawan)
      <div class="slip-gaji-card">
        {{-- Cetak Button --}}
        <button class="cetak-button-compact"
          onclick="window.open('{{ route('slip-gaji.cetak-individual', ['karyawan_id' => $karyawan['karyawan_id'], 'tahun' => $periodeTahun, 'bulan' => $periodeBulan]) }}', '_blank')"
          type="button">
          <svg class="cetak-icon-compact" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"
              clip-rule="evenodd" />
          </svg>
          Cetak
        </button>

        <!-- Employee Header -->
        <div class="employee-header-compact">
          <div class="employee-id-compact">
            {{ $karyawan['karyawan_id'] }}
          </div>
          <h3 class="employee-name-compact">
            {{ $karyawan['nama_lengkap'] }}
          </h3>
          <div class="employee-badges-compact">
            <span class="badge-compact badge-blue-compact">
              {{ $karyawan['jabatan'] }}
            </span>
            <span class="badge-compact badge-purple-compact">
              {{ $karyawan['departemen'] }}
            </span>
          </div>
        </div>

        <!-- Income Section -->
        <div class="salary-group-compact">
          <div class="salary-group-title-compact">
            <svg class="icon-compact" viewBox="0 0 20 20">
              <path
                d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
              <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" />
            </svg>
            Pendapatan
          </div>
          <div class="salary-items-compact">
            <div class="salary-item-compact">
              <span class="salary-label-compact">Gaji Pokok:</span>
              <span class="salary-value-compact">Rp {{ number_format($karyawan['gaji_pokok'], 0, ',', '.') }}</span>
            </div>

            @if(isset($karyawan['tunjangan_breakdown']) && !empty($karyawan['tunjangan_breakdown']['breakdown']))
              @foreach($karyawan['tunjangan_breakdown']['breakdown'] as $item)
                <div class="salary-item-compact">
                  <span class="salary-label-compact">{{ $item['label'] }}:</span>
                  <span class="salary-value-compact salary-positive-compact">Rp
                    {{ number_format($item['amount'], 0, ',', '.') }}</span>
                </div>
              @endforeach
            @endif

            @if($karyawan['total_lembur'] > 0)
              <div class="salary-item-compact">
                <span class="salary-label-compact">Upah Lembur:</span>
                <span class="salary-value-compact salary-positive-compact">Rp
                  {{ number_format($karyawan['lembur_pay'], 0, ',', '.') }}</span>
              </div>
              <div class="breakdown-detail-compact">
                {{ $karyawan['total_lembur'] }} jam ({{ $karyawan['total_lembur_sessions'] }} sesi)
              </div>
            @endif

            <div class="salary-item-compact"
              style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgb(229 231 235);">
              <span class="salary-label-compact" style="font-weight: 600;">Total Pendapatan:</span>
              <span class="salary-value-compact salary-positive-compact" style="font-weight: 600;">
                Rp {{ number_format($karyawan['pph21_detail']['penghasilan_bruto'], 0, ',', '.') }}
              </span>
            </div>
          </div>
        </div>

        <!-- Deduction Section -->
        <div class="salary-group-compact">
          <div class="salary-group-title-compact">
            <svg class="icon-compact" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" />
            </svg>
            Potongan
          </div>
          <div class="salary-items-compact">
            @if($karyawan['total_alfa'] > 0)
              <div class="salary-item-compact">
                <span class="salary-label-compact">Potongan Alfa:</span>
                <span class="salary-value-compact salary-negative-compact">
                  Rp {{ number_format($karyawan['potongan_detail']['alfa']['total_potongan'], 0, ',', '.') }}
                </span>
              </div>
              <div class="breakdown-detail-compact">
                {{ $karyawan['total_alfa'] }} hari × Rp
                {{ number_format($karyawan['potongan_detail']['alfa']['potongan_per_hari'], 0, ',', '.') }}/hari
              </div>
            @endif

            @if($karyawan['total_tidak_tepat'] > 0)
              <div class="salary-item-compact">
                <span class="salary-label-compact">Potongan Terlambat:</span>
                <span class="salary-value-compact salary-negative-compact">
                  Rp {{ number_format($karyawan['potongan_detail']['keterlambatan']['total_potongan'], 0, ',', '.') }}
                </span>
              </div>
              <div class="breakdown-detail-compact">
                {{ $karyawan['total_tidak_tepat'] }} hari × Rp
                {{ number_format($karyawan['potongan_detail']['keterlambatan']['potongan_per_hari'], 0, ',', '.') }}/hari
              </div>
            @endif

            @if(isset($karyawan['bpjs_breakdown']) && !empty($karyawan['bpjs_breakdown']['breakdown']))
              @foreach($karyawan['bpjs_breakdown']['breakdown'] as $item)
                <div class="salary-item-compact">
                  <span class="salary-label-compact">{{ $item['label'] }}:</span>
                  <span class="salary-value-compact salary-negative-compact">
                    Rp {{ number_format($item['amount'], 0, ',', '.') }}
                  </span>
                </div>
                <div class="breakdown-detail-compact">
                  {{ $item['description'] }}
                </div>
              @endforeach
            @endif

            <div class="salary-item-compact">
              <span class="salary-label-compact">Pajak PPh21:</span>
              <span class="salary-value-compact salary-negative-compact">
                Rp {{ number_format($karyawan['pph21_detail']['jumlah'], 0, ',', '.') }}
              </span>
            </div>
            <div class="breakdown-detail-compact">
              {{ $karyawan['pph21_detail']['tarif_persen'] }} dari penghasilan bruto Rp
              {{ number_format($karyawan['pph21_detail']['penghasilan_bruto'], 0, ',', '.') }}
            </div>
            <div class="breakdown-detail-compact">
              PTKP: {{ $karyawan['pph21_detail']['golongan_ptkp'] }} ({{ $karyawan['pph21_detail']['kategori_ter'] }})
            </div>

            <div class="salary-item-compact"
              style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgb(229 231 235);">
              <span class="salary-label-compact" style="font-weight: 600;">Total Potongan:</span>
              <span class="salary-value-compact salary-negative-compact" style="font-weight: 600;">
                Rp {{ number_format($karyawan['potongan_total'], 0, ',', '.') }}
              </span>
            </div>
          </div>
        </div>

        {{-- Penyesuaian Section --}}
        @if(isset($karyawan['penyesuaian']) && ($karyawan['penyesuaian'] != 0 || !empty($karyawan['catatan_penyesuaian'])))
          <div class="penyesuaian-section-compact">
            <div class="penyesuaian-title-compact">
              <svg class="icon-compact" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" />
              </svg>
              Penyesuaian
            </div>
            <div class="salary-items-compact">
              <div class="salary-item-compact">
                <span class="salary-label-compact">Jumlah Penyesuaian:</span>
                <span
                  class="salary-value-compact {{ $karyawan['penyesuaian'] >= 0 ? 'salary-positive-compact' : 'salary-negative-compact' }}">
                  {{ $karyawan['penyesuaian'] >= 0 ? '+' : '' }}Rp
                  {{ number_format($karyawan['penyesuaian'], 0, ',', '.') }}
                </span>
              </div>
            </div>
            @if(!empty($karyawan['catatan_penyesuaian']))
              <div class="penyesuaian-note-compact">
                <strong>Catatan:</strong> {{ $karyawan['catatan_penyesuaian'] }}
              </div>
            @endif
          </div>
        @endif

        <!-- Total Salary -->
        <div class="total-salary-compact">
          <div class="total-salary-item-compact">
            <span class="total-salary-label-compact">GAJI BERSIH:</span>
            <span class="total-salary-value-compact">
              Rp {{ number_format($karyawan['total_gaji'], 0, ',', '.') }}
            </span>
          </div>
        </div>
      </div>
    @endforeach
  </div>

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

        {{-- Smart Page Numbers --}}
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
        Menampilkan {{ $pagination->firstItem() }} - {{ $pagination->lastItem() }} dari {{ $pagination->total() }}
        karyawan
      </div>
    </div>
  @endif
</x-filament-widgets::widget>
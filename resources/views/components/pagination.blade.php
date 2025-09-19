@props(['pagination'])

<style>
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
</style>

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
    {{ $pagination->total() }} karyawan
  </div>
</div>
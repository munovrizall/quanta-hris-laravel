<!-- HEADER -->
<header class="header">
    <table class="table-no-border" style="width:100%;">
        <tr>
            <td class="w-50">
                @php
                    $logoPath = storage_path('app/public/images/logo/smartcool_logo.png');
                    $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
                @endphp
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo" class="logo">
                @else
                    <span style="color:red; font-size:13px;">Logo not found</span>
                @endif
            </td>
            <td class="w-50 company-info">
                <span style="font-size: 16px; font-weight: bold;">PT.QUANTA TEKNIK GEMILANG</span><br>
                OFFICE: Cyber Building 6th Floors Kuningan Barat no.8 Jakarta<br>
                WORKSHOP: Jalan Raya Bojongsari No.99D Bojongsari Baru Depok<br>
                Telp: 021-50919091 | Email: herein@smartcool.id | www.smartcool.id
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 10px;">
                <div style="font-size: 20px; font-weight:bold; text-align:center; border-top: 2px solid #222; border-bottom: 2px solid #222; padding: 7px 0; margin-bottom: 8px;">
                    {{ $judulDokumen }} - {{ $periode }}
                </div>
            </td>
        </tr>
    </table>
</header>

<!-- HEADER -->
<header class="header">
    <table class="table-no-border" style="width:100%;">
        <tr>
            <td class="w-50" style="vertical-align: top;">
                @php
                    $logoPath = storage_path('app/public/smartcool_logo.png');
                    $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
                @endphp
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo" class="logo" style="max-width: 200px; max-height: 80px;">
                @else
                    <span style="color:red; font-size:13px;">Logo not found</span>
                @endif
            </td>
            <td class="w-50 company-info" style="vertical-align: top; padding-left: 10px;">
                <span style="font-size: 14px; font-weight: bold;">PT.QUANTA TEKNIK GEMILANG</span><br>
                <span style="font-size: 10px;">OFFICE: Cyber Building 6th Floors Kuningan Barat no.8 Jakarta<br>
                WORKSHOP: Jalan Raya Bojongsari No.99D Bojongsari Baru Depok<br>
                Telp: 021-50919091 | Email: herein@smartcool.id | www.smartcool.id</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top: 8px;">
                <div style="font-size: 16px; font-weight:bold; text-align:center; border-top: 1px solid #222; border-bottom: 1px solid #222; padding: 5px 0; margin: 5px 0;">
                    {{ $judulDokumen }} - {{ $periode }}
                </div>
            </td>
        </tr>
    </table>
</header>
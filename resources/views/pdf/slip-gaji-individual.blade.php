<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judulDokumen }} - {{ $karyawan['nama_lengkap'] }}</title>
    <style>
        @page {
            margin: 25px 25px 35px 25px;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 16px;
            color: #222;
        }
        .header, .footer {
            width: 100%;
            position: fixed;
            left: 0;
            right: 0;
        }
        .header {
            top: 0;
            height: 110px;
        }

        .footer {
            bottom: -30px;
            height: 40px;
            font-size: 14px;
            text-align: right;
            padding-top: 5px;
        }
        .footer .page-number:after {
            content: "Halaman " counter(page);
        }
        main {
            position: relative;
            top: 120px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            page-break-inside: auto;
        }
        th, td {
            padding: 10px 12px;
            border: 1.5px solid #bbb;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            font-size: 16px;
        }
        thead th {
            background: #f2f2f2;
            font-weight: bold;
            font-size: 18px;
        }
        .table-no-border td, .table-no-border th {
            border: none;
            padding: 2px 4px;
        }
        .w-50 { width: 50%; }
        .company-info { text-align: right; font-size: 16px; line-height: 1.2; }
        .logo { max-width: 240px; max-height: 120px; }
        .section-title {
            background: #e9e9e9;
            padding: 8px;
            font-weight: bold;
            margin-top: 18px;
            margin-bottom: 12px;
            border: 1.5px solid #bbb;
            font-size: 15px;
            text-align: left;
        }
        .employee-info {
            border: 2px solid #bbb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 22px;
            margin-top: 12px;
            background: none;
        }
        .employee-info h3 {
            font-size: 18px;
            font-weight: bold;
            color: #222;
            margin-top: 0;
            margin-bottom: 12px;
            border-bottom: 1.5px solid #bbb;
            padding-bottom: 7px;
        }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 32%; font-weight: bold; padding: 3px 0; color: #222; font-size: 16px;}
        .info-value { display: table-cell; padding: 3px 0; font-size: 16px;}
        .positive { color: #059669; font-weight: bold; }
        .negative { color: #dc2626; font-weight: bold; }
        .final-total {
            background: none;
            color: #222;
            font-weight: bold;
            font-size: 20px;
        }
        .penyesuaian-section {
            background: none;
            border: 2px solid #bbb;
            border-radius: 8px;
            padding: 12px;
            margin: 18px 0;
        }
        .penyesuaian-title {
            font-weight: bold;
            color: #222;
            margin-bottom: 7px;
            font-size: 15px;
        }
        .breakdown-note {
            font-size: 14px;
            color: #555;
            font-style: italic;
        }
        .table-title {
            font-size: 20px;
            font-weight: bold;
            text-align: left;
            padding: 8px 0 4px 0;
        }
        /* Spacing between header and main content */
        .main-content-spacing {
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <table class="table-no-border" style="width:100%;">
            <tr>
                <td class="w-100">
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
                        SLIP GAJI KARYAWAN
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="page-number"></div>
        <div style="font-size:14px; color:#888;">
            Dokumen ini dicetak pada {{ $tanggalCetak }} | PT. Quanta Teknik Gemilang - Quanta HRIS
        </div>
    </footer>

    <!-- MAIN CONTENT -->
    <main class="main-content-spacing">
        <!-- Informasi Karyawan -->
        <div class="employee-info">
            <h3>Informasi Karyawan</h3>
            <div class="info-grid">
                <div class="info-row">
                    <div class="info-label">ID Karyawan:</div>
                    <div class="info-value">{{ $karyawan['karyawan_id'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Nama Lengkap:</div>
                    <div class="info-value">{{ $karyawan['nama_lengkap'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Jabatan:</div>
                    <div class="info-value">{{ $karyawan['jabatan'] }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Departemen:</div>
                    <div class="info-value">{{ $karyawan['departemen'] }}</div>
                </div>
            </div>
        </div>

        <!-- Absensi Section -->
        <div class="employee-info" style="margin-bottom: 22px;">
            <h3>Rekap Absensi</h3>
            <table>
                <tr>
                    <th>Hadir</th>
                    <th>Alfa</th>
                    <th>Terlambat</th>
                    <th>Cuti</th>
                    <th>Izin</th>
                    <th>Jam Lembur</th>
                </tr>
                <tr>
                    <td style="text-align:center;">{{ $karyawan['total_hadir'] }}</td>
                    <td style="text-align:center;">{{ $karyawan['total_alfa'] }}</td>
                    <td style="text-align:center;">{{ $karyawan['total_tidak_tepat'] }}</td>
                    <td style="text-align:center;">{{ $karyawan['total_cuti'] }}</td>
                    <td style="text-align:center;">{{ $karyawan['total_izin'] }}</td>
                    <td style="text-align:center;">{{ $karyawan['total_lembur'] }}</td>
                </tr>
            </table>
        </div>

        <!-- Pendapatan -->
        <div class="table-title">Pendapatan</div>
        <table>
            <thead>
                <tr>
                    <th style="width:65%;">Komponen</th>
                    <th style="width:35%; text-align:right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Gaji Pokok</td>
                    <td class="positive" style="text-align:right;">Rp {{ number_format($karyawan['gaji_pokok'], 0, ',', '.') }}</td>
                </tr>
                @if(isset($karyawan['tunjangan_breakdown']) && !empty($karyawan['tunjangan_breakdown']['breakdown']))
                    @foreach($karyawan['tunjangan_breakdown']['breakdown'] as $item)
                        <tr>
                            <td>{{ $item['label'] }}</td>
                            <td class="positive" style="text-align:right;">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
                @if($karyawan['total_lembur'] > 0)
                    <tr>
                        <td>Upah Lembur <span class="breakdown-note">({{ $karyawan['total_lembur'] }} jam)</span></td>
                        <td class="positive" style="text-align:right;">Rp {{ number_format($karyawan['lembur_pay'], 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td><strong>Total Pendapatan</strong></td>
                    <td class="positive" style="text-align:right;"><strong>Rp {{ number_format($karyawan['pph21_detail']['penghasilan_bruto'], 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Potongan -->
        <div class="table-title">Potongan</div>
        <table>
            <thead>
                <tr>
                    <th style="width:65%;">Komponen</th>
                    <th style="width:35%; text-align:right;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @if($karyawan['total_alfa'] > 0)
                    <tr>
                        <td>Potongan Alfa <span class="breakdown-note">({{ $karyawan['total_alfa'] }} hari)</span></td>
                        <td class="negative" style="text-align:right;">Rp {{ number_format($karyawan['potongan_detail']['alfa']['total_potongan'], 0, ',', '.') }}</td>
                    </tr>
                @endif
                @if($karyawan['total_tidak_tepat'] > 0)
                    <tr>
                        <td>Potongan Terlambat <span class="breakdown-note">({{ $karyawan['total_tidak_tepat'] }} hari)</span></td>
                        <td class="negative" style="text-align:right;">Rp {{ number_format($karyawan['potongan_detail']['keterlambatan']['total_potongan'], 0, ',', '.') }}</td>
                    </tr>
                @endif
                @if(isset($karyawan['bpjs_breakdown']) && !empty($karyawan['bpjs_breakdown']['breakdown']))
                    @foreach($karyawan['bpjs_breakdown']['breakdown'] as $item)
                        <tr>
                            <td>{{ $item['label'] }} <span class="breakdown-note">({{ $item['description'] }})</span></td>
                            <td class="negative" style="text-align:right;">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endif
                <tr>
                    <td>Pajak PPh21 <span class="breakdown-note">({{ $karyawan['pph21_detail']['tarif_persen'] }})</span></td>
                    <td class="negative" style="text-align:right;">Rp {{ number_format($karyawan['pph21_detail']['jumlah'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td><strong>Total Potongan</strong></td>
                    <td class="negative" style="text-align:right;"><strong>Rp {{ number_format($karyawan['potongan_total'], 0, ',', '.') }}</strong></td>
                </tr>
            </tbody>
        </table>

        <!-- Penyesuaian Section -->
        @if(isset($karyawan['penyesuaian']) && ($karyawan['penyesuaian'] != 0 || !empty($karyawan['catatan_penyesuaian'])))
            <div class="penyesuaian-section">
                <div class="penyesuaian-title">Penyesuaian</div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Jumlah Penyesuaian:</div>
                        <div class="info-value">
                            <span class="{{ $karyawan['penyesuaian'] >= 0 ? 'positive' : 'negative' }}">
                                {{ $karyawan['penyesuaian'] >= 0 ? '+' : '' }}Rp {{ number_format($karyawan['penyesuaian'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    @if(!empty($karyawan['catatan_penyesuaian']))
                        <div class="info-row">
                            <div class="info-label">Catatan:</div>
                            <div class="info-value">{{ $karyawan['catatan_penyesuaian'] }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Final Total -->
        <table style="margin-top: 24px;">
            <tr class="final-total">
                <td style="text-align: center; font-size: 22px;">
                    <strong>GAJI BERSIH</strong>
                </td>
                <td style="text-align: right; font-size: 22px;">
                    <strong>Rp {{ number_format($karyawan['total_gaji'], 0, ',', '.') }}</strong>
                </td>
            </tr>
        </table>
    </main>
</body>
</html>
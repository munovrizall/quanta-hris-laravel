<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judulDokumen }} - {{ $karyawan['nama_lengkap'] }}</title>
    <style>
        @page {
            margin: 40px 40px 50px 40px;
        }
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header, .footer {
            width: 100%;
            position: fixed;
            left: 0;
            right: 0;
        }
        .header {
            top: 0;
            height: 90px;
        }
        .footer {
            bottom: -30px;
            height: 30px;
            font-size: 9px;
            text-align: right;
            padding-top: 5px;
        }
        .footer .page-number:after {
            content: "Halaman " counter(page);
        }
        main {
            position: relative;
            top: 100px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            page-break-inside: auto;
        }
        th, td {
            padding: 4px 6px;
            border: 1px solid #ccc;
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
        }
        thead th {
            background: #f2f2f2;
            font-weight: bold;
        }
        .table-no-border td, .table-no-border th {
            border: none;
            padding: 2px 4px;
        }
        .w-50 { width: 50%; }
        .company-info { text-align: right; font-size: 9px; line-height: 1.2; }
        .logo { max-width: 150px; max-height: 60px; }
        .section-title {
            background: #e9e9e9;
            padding: 5px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
        }
        .employee-info {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 18px;
            background: none;
        }
        .employee-info h3 {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 30%; font-weight: bold; padding: 2px 0; color: #333; }
        .info-value { display: table-cell; padding: 2px 0; }
        .positive { color: #059669; font-weight: bold; }
        .negative { color: #dc2626; font-weight: bold; }
        .final-total {
            background: none;
            color: #333;
            font-weight: bold;
            font-size: 13px;
        }
        .penyesuaian-section {
            background: none;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            margin: 15px 0;
        }
        .penyesuaian-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .breakdown-note {
            font-size: 9px;
            color: #555;
            font-style: italic;
        }
        .two-column { display: table; width: 100%; }
        .column { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
        .column:last-child { padding-right: 0; padding-left: 10px; }
    </style>
</head>
<body>
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
                        <span style="color:red; font-size:9px;">Logo not found</span>
                    @endif
                </td>
                <td class="w-50 company-info">
                    <span style="font-size: 12px; font-weight: bold;">PT.QUANTA TEKNIK GEMILANG</span><br>
                    OFFICE: Cyber Building 6th Floors Kuningan Barat no.8 Jakarta<br>
                    WORKSHOP: Jalan Raya Bojongsari No.99D Bojongsari Baru Depok<br>
                    Telp: 021-50919091 | Email: herein@smartcool.id | www.smartcool.id
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top: 10px;">
                    <div style="font-size: 16px; font-weight:bold; text-align:center; border-top: 1px solid #333; border-bottom: 1px solid #333; padding: 5px 0;">
                        SLIP GAJI KARYAWAN
                    </div>
                </td>
            </tr>
        </table>
    </header>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="page-number"></div>
        <div style="font-size:8px; color:#888;">
            Dokumen ini dicetak pada {{ $tanggalCetak }} | PT. Quanta Teknik Gemilang - Quanta HRIS
        </div>
    </footer>

    <!-- MAIN CONTENT -->
    <main>
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
        <div class="employee-info" style="margin-bottom: 20px;">
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

        <!-- Salary Details in Two Columns -->
        <div class="two-column">
            <!-- Left Column - Income -->
            <div class="column">
                <div class="section-title" style="background:none; border:none;">Pendapatan</div>
                <table>
                    <tr>
                        <th>Komponen</th>
                        <th style="text-align: right;">Jumlah</th>
                    </tr>
                    <tr>
                        <td>Gaji Pokok</td>
                        <td class="positive">Rp {{ number_format($karyawan['gaji_pokok'], 0, ',', '.') }}</td>
                    </tr>
                    @if(isset($karyawan['tunjangan_breakdown']) && !empty($karyawan['tunjangan_breakdown']['breakdown']))
                        @foreach($karyawan['tunjangan_breakdown']['breakdown'] as $item)
                            <tr>
                                <td>{{ $item['label'] }}</td>
                                <td class="positive">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @endif
                    @if($karyawan['total_lembur'] > 0)
                        <tr>
                            <td>Upah Lembur <span class="breakdown-note">({{ $karyawan['total_lembur'] }} jam)</span></td>
                            <td class="positive">Rp {{ number_format($karyawan['lembur_pay'], 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td><strong>Total Pendapatan</strong></td>
                        <td class="positive"><strong>Rp {{ number_format($karyawan['pph21_detail']['penghasilan_bruto'], 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </div>
            <!-- Right Column - Deductions -->
            <div class="column">
                <div class="section-title" style="background:none; border:none;">Potongan</div>
                <table>
                    <tr>
                        <th>Komponen</th>
                        <th style="text-align: right;">Jumlah</th>
                    </tr>
                    @if($karyawan['total_alfa'] > 0)
                        <tr>
                            <td>Potongan Alfa <span class="breakdown-note">({{ $karyawan['total_alfa'] }} hari)</span></td>
                            <td class="negative">Rp {{ number_format($karyawan['potongan_detail']['alfa']['total_potongan'], 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if($karyawan['total_tidak_tepat'] > 0)
                        <tr>
                            <td>Potongan Terlambat <span class="breakdown-note">({{ $karyawan['total_tidak_tepat'] }} hari)</span></td>
                            <td class="negative">Rp {{ number_format($karyawan['potongan_detail']['keterlambatan']['total_potongan'], 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if(isset($karyawan['bpjs_breakdown']) && !empty($karyawan['bpjs_breakdown']['breakdown']))
                        @foreach($karyawan['bpjs_breakdown']['breakdown'] as $item)
                            <tr>
                                <td>{{ $item['label'] }} <span class="breakdown-note">({{ $item['description'] }})</span></td>
                                <td class="negative">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr>
                        <td>Pajak PPh21 <span class="breakdown-note">({{ $karyawan['pph21_detail']['tarif_persen'] }})</span></td>
                        <td class="negative">Rp {{ number_format($karyawan['pph21_detail']['jumlah'], 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Total Potongan</strong></td>
                        <td class="negative"><strong>Rp {{ number_format($karyawan['potongan_total'], 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

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
        <table style="margin-top: 20px;">
            <tr class="final-total">
                <td style="text-align: center; font-size: 14px;">
                    <strong>GAJI BERSIH</strong>
                </td>
                <td style="text-align: right; font-size: 14px;">
                    <strong>Rp {{ number_format($karyawan['total_gaji'], 0, ',', '.') }}</strong>
                </td>
            </tr>
        </table>
    </main>
</body>
</html>
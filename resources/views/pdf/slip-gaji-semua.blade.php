<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $judulDokumen }} - {{ $periode }}</title>
    <style>
        @page {
            margin: 1.5cm 1cm 2cm 1cm;
            @top-center {
                content: "{{ $judulDokumen }}";
            }
            @bottom-center {
                content: "Halaman " counter(page) " dari " counter(pages);
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .periode {
            font-size: 12px;
            color: #6b7280;
        }

        .summary-info {
            background-color: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .slip-container {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            margin-bottom: 15px;
            padding: 12px;
            background-color: #fafafa;
            page-break-inside: avoid;
        }

        .employee-header {
            background-color: #3b82f6;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .salary-grid {
            display: table;
            width: 100%;
        }

        .salary-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }

        .salary-column:last-child {
            padding-right: 0;
            padding-left: 8px;
        }

        .salary-section {
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            background-color: #e5e7eb;
            padding: 4px 8px;
            border-radius: 3px;
            margin-bottom: 5px;
        }

        .income-title {
            background-color: #dcfce7;
            color: #166534;
        }

        .deduction-title {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .salary-table td {
            padding: 2px 4px;
            border-bottom: 1px solid #e5e7eb;
        }

        .salary-table .label {
            width: 60%;
        }

        .salary-table .amount {
            width: 40%;
            text-align: right;
            font-weight: bold;
        }

        .positive {
            color: #059669;
        }

        .negative {
            color: #dc2626;
        }

        .total-row {
            background-color: #f3f4f6;
            font-weight: bold;
            border-top: 1px solid #d1d5db;
        }

        .final-total {
            background-color: #3b82f6;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 6px;
            border-radius: 3px;
            margin-top: 5px;
        }

        .penyesuaian {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 3px;
            padding: 4px;
            margin: 5px 0;
            font-size: 8px;
        }

        .page-break {
            page-break-before: always;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40px;
            border-top: 1px solid #e5e7eb;
            padding: 8px;
            text-align: center;
            background-color: white;
        }

        .print-info {
            font-size: 7px;
            color: #9ca3af;
        }

        .breakdown-note {
            font-size: 7px;
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">PT. QUANTA TECHNOLOGY</div>
        <div class="document-title">{{ $judulDokumen }}</div>
        <div class="periode">Periode: {{ $periode }}</div>
    </div>

    <!-- Summary Information -->
    <div class="summary-info">
        <strong>Total Karyawan: {{ $totalKaryawan }}</strong>
    </div>

    <!-- Slip Gaji untuk setiap karyawan -->
    @foreach($karyawanData as $index => $karyawan)
        @if($index > 0 && $index % 2 == 0)
            <div class="page-break"></div>
        @endif
        
        <div class="slip-container">
            <!-- Employee Header -->
            <div class="employee-header">
                {{ $karyawan['karyawan_id'] }} - {{ $karyawan['nama_lengkap'] }} 
                ({{ $karyawan['jabatan'] }} - {{ $karyawan['departemen'] }})
            </div>

            <!-- Salary Details in Two Columns -->
            <div class="salary-grid">
                <!-- Left Column - Income -->
                <div class="salary-column">
                    <div class="salary-section">
                        <div class="section-title income-title">💰 Pendapatan</div>
                        <table class="salary-table">
                            <tr>
                                <td class="label">Gaji Pokok</td>
                                <td class="amount">Rp {{ number_format($karyawan['gaji_pokok'], 0, ',', '.') }}</td>
                            </tr>
                            
                            @if(isset($karyawan['tunjangan_breakdown']) && !empty($karyawan['tunjangan_breakdown']['breakdown']))
                                @foreach($karyawan['tunjangan_breakdown']['breakdown'] as $item)
                                <tr>
                                    <td class="label">{{ $item['label'] }}</td>
                                    <td class="amount positive">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            @endif

                            @if($karyawan['total_lembur'] > 0)
                            <tr>
                                <td class="label">Lembur <span class="breakdown-note">({{ $karyawan['total_lembur'] }}j)</span></td>
                                <td class="amount positive">Rp {{ number_format($karyawan['lembur_pay'], 0, ',', '.') }}</td>
                            </tr>
                            @endif

                            <tr class="total-row">
                                <td class="label"><strong>Total</strong></td>
                                <td class="amount positive"><strong>Rp {{ number_format($karyawan['pph21_detail']['penghasilan_bruto'], 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Right Column - Deductions -->
                <div class="salary-column">
                    <div class="salary-section">
                        <div class="section-title deduction-title">➖ Potongan</div>
                        <table class="salary-table">
                            @if($karyawan['total_alfa'] > 0)
                            <tr>
                                <td class="label">Alfa <span class="breakdown-note">({{ $karyawan['total_alfa'] }}h)</span></td>
                                <td class="amount negative">Rp {{ number_format($karyawan['potongan_detail']['alfa']['total_potongan'], 0, ',', '.') }}</td>
                            </tr>
                            @endif

                            @if($karyawan['total_tidak_tepat'] > 0)
                            <tr>
                                <td class="label">Terlambat <span class="breakdown-note">({{ $karyawan['total_tidak_tepat'] }}h)</span></td>
                                <td class="amount negative">Rp {{ number_format($karyawan['potongan_detail']['keterlambatan']['total_potongan'], 0, ',', '.') }}</td>
                            </tr>
                            @endif

                            @if(isset($karyawan['bpjs_breakdown']) && !empty($karyawan['bpjs_breakdown']['breakdown']))
                                @foreach($karyawan['bpjs_breakdown']['breakdown'] as $item)
                                <tr>
                                    <td class="label">{{ $item['label'] }}</td>
                                    <td class="amount negative">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            @endif

                            <tr>
                                <td class="label">PPh21</td>
                                <td class="amount negative">Rp {{ number_format($karyawan['pph21_detail']['jumlah'], 0, ',', '.') }}</td>
                            </tr>

                            <tr class="total-row">
                                <td class="label"><strong>Total</strong></td>
                                <td class="amount negative"><strong>Rp {{ number_format($karyawan['potongan_total'], 0, ',', '.') }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Penyesuaian -->
            @if(isset($karyawan['penyesuaian']) && $karyawan['penyesuaian'] != 0)
            <div class="penyesuaian">
                <strong>Penyesuaian: 
                    <span class="{{ $karyawan['penyesuaian'] >= 0 ? 'positive' : 'negative' }}">
                        {{ $karyawan['penyesuaian'] >= 0 ? '+' : '' }}Rp {{ number_format($karyawan['penyesuaian'], 0, ',', '.') }}
                    </span>
                </strong>
                @if(!empty($karyawan['catatan_penyesuaian']))
                    <br><em>{{ $karyawan['catatan_penyesuaian'] }}</em>
                @endif
            </div>
            @endif

            <!-- Final Total -->
            <div class="final-total">
                <strong>GAJI BERSIH: Rp {{ number_format($karyawan['total_gaji'], 0, ',', '.') }}</strong>
            </div>
        </div>
    @endforeach

    <!-- Footer -->
    <div class="footer">
        <div class="print-info">
            Dokumen ini dicetak pada {{ $tanggalCetak }} | PT. Quanta Technology - Sistem HRIS
        </div>
    </div>
</body>
</html>
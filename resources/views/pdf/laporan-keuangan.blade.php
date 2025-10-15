<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $judulDokumen }} - {{ $periode }}</title>
    <style>
        @page {
            margin: 140px 40px 90px 40px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }

         .header, .footer {
            width: 100%;
            position: fixed;
            left: 0;
            right: 0;
        }

         .footer {
            bottom: -30px;
            height: 40px;
            font-size: 14px;
            text-align: right;
            padding-top: 5px;
        }

        .footer .page-number:before {
            content: "Halaman " counter(page);
        }

        .logo {
            max-width: 240px;
            max-height: 120px;
        }

        .company-info {
            text-align: right;
            font-size: 12px;
            line-height: 1.4;
        }

        .w-50 {
            width: 50%;
            vertical-align: top;
        }

        .table-no-border td,
        .table-no-border th {
            border: none;
        }

        .content {
            margin-top: 200px;
        }

        h2 {
            font-size: 16px;
            margin-bottom: 6px;
        }

        h3 {
            font-size: 14px;
            margin: 20px 0 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px 10px;
        }

        th {
            background-color: #f4f4f4;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .meta-info {
            margin-bottom: 16px;
            font-size: 12px;
            color: #555;
        }

        .note {
            font-size: 11px;
            color: #777;
            margin-top: 6px;
        }
    </style>
</head>

<body>
    @include('pdf.partials.laporan-header', [
        'judulDokumen' => $judulDokumen,
        'periode' => $periode,
    ])

    <div class="content">
        <div class="meta-info">
            <strong>Periode:</strong> {{ $summary['period']['label'] }} ({{ $summary['period']['range'] }})<br>
            <strong>Total Karyawan:</strong> {{ $summary['totals']['employee_count'] }} orang
        </div>

        <h2>Ringkasan Biaya Penggajian</h2>
        <table>
            <tbody>
                <tr>
                    <th>Total Biaya Gaji (Bersih)</th>
                    <td class="text-right">Rp {{ number_format($summary['totals']['total_salary'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th>Penghasilan Bruto</th>
                    <td class="text-right">Rp {{ number_format($summary['totals']['gross_income'], 0, ',', '.') }}</td>
                </tr>

                                    <tr>
                    <th>Total Tunjangan</th>
                    <td class="text-right">Rp {{ number_format($summary['totals']['total_allowances'], 0, ',', '.') }}</td>
                </tr>

                                    <tr>
                    <th>Total Lembur</th>
                    <td class="text-right">Rp {{ number_format($summary['totals']['total_overtime'], 0, ',', '.') }}</td>
                </tr>

                                    <tr>
                    <th>Total Potongan</th>
                    <td class="text-right">Rp {{ number_format($summary['totals']['total_deductions'], 0, ',', '.') }}</td>
                </tr>

                                    <tr>
                    <th>Rata-rata Gaji Bersih</th>
                    <td class="text-right">Rp {{ number_format($summary['totals']['average_salary'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <h3>Rincian Biaya per Departemen</h3>
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">No</th>
                    <th>Departemen</th>
                    <th class="text-center" style="width: 15%;">Jumlah Karyawan</th>
                    <th class="text-right" style="width: 20%;">Total Gaji</th>
                    <th class="text-center" style="width: 15%;">Kontribusi</th>
                </tr>
        </thead>
        <tbody>
            @forelse ($departmentBreakdown as $index => $department)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $department['departemen'] }}</td>
                    <td class="text-center">{{ $department['jumlah_karyawan'] }}</td>
                        <td class="text-right">Rp {{ number_format($department['total_gaji'], 0, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($department['persentase'], 2, ',', '.') }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Tidak ada data departemen untuk periode ini.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="note">
            Catatan: Laporan ini dikompilasi otomatis dari sistem Quanta HRIS berdasarkan data penggajian yang tercatat.
        </div>
    </d
iv>

    @include('pdf.partials.laporan-footer', [
        'tanggalCetak' => $tanggalCetak,
    ])
</body>
</html>

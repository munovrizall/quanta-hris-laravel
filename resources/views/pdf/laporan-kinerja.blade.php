<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $judulDokumen }} - {{ $periode }}</title>
    <style>
        @page {
            margin: 160px 40px 120px 40px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            position: fixed;
            top: -140px;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
            background-color: white;
            height: 180px;
            overflow: visible;
        }

        .footer {
            position: fixed;
            bottom: -100px;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 1000;
            background-color: white;
            text-align: right;
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

        .table-no-border,
        .table-no-border td,
        .table-no-border th {
            border: none;
        }

        .content {
            margin-top: 70px;
            padding-top: 0;
        }

        h2 {
            font-size: 16px;
            margin-bottom: 6px;
            page-break-after: avoid;
        }

        h3 {
            font-size: 14px;
            margin: 20px 0 8px;
            page-break-after: avoid;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            page-break-inside: avoid;
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

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            background-color: #eef2ff;
        }

        .meta-info {
            margin-bottom: 16px;
            font-size: 12px;
            color: #555;
            page-break-after: avoid;
        }

        .note {
            font-size: 11px;
            color: #777;
            margin-top: 6px;
            page-break-before: avoid;
        }

        /* Ensure table headers repeat on new pages */
        .performance-table {
            page-break-inside: auto;
        }

        .performance-table thead {
            display: table-header-group;
        }

        .performance-table tbody tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        /* Prevent orphan rows */
        .performance-table tbody tr:nth-last-child(-n+3) {
            page-break-inside: avoid;
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
            <strong>Total Kehadiran:</strong> {{ $summary['attendance']['total'] }} catatan
        </div>

        <h2>Ringkasan Kinerja Kehadiran</h2>
        <table>
            <tbody>
                <tr>
                    <th>Total Kehadiran</th>
                    <td class="text-right">{{ $summary['attendance']['total'] }}</td>
                </tr>
                       
                <tr>
                    <th>Kehadiran Tepat Waktu</th>
                    <td class="text-right">{{ $summary['attendance']['on_time'] }} ({{ number_format($summary['attendance']['on_time_rate'], 2, ',', '.') }}%)</td>
                </tr>

                <tr>
                    <th>Keterlambatan</th>
                    <td class="text-right">{{ $summary['attendance']['late'] }} ({{ number_format($summary['attendance']['late_rate'], 2, ',', '.') }}%)</td>
                </tr>
                <tr>
                    <th>Karyawan Pulang Cepat</th>
                    <td class="text-right">{{ $summary['attendance']['early_leave'] }}</td>
                </tr>
            </tbody>
        </table>

        <h3>Aktivitas Lembur & Perizinan</h3>
        <table>
            <tbody>
                <tr>
                    <th>Total Sesi Lembur</th>
                    <td class="text-right">{{ $summary['lembur']['sessions'] }}</td>
                </tr>
                <tr>
                    <th>Total Jam Lembur</th>
                    <td class="text-right">{{ number_format($summary['lembur']['hours'], 1, ',', '.') }} jam</td>
                </tr>
                       
                <tr>
                    <th>Pengajuan Cuti</th>
                    <td class="text-right">{{ $summary['cuti']['requests'] }} pengajuan ({{ $summary['cuti']['days'] }} hari)</td>
                </tr>

                <tr>
                    <th>Pengajuan Izin</th>
                    <td class="text-right">{{ $summary['izin']['requests'] }} pengajuan ({{ $summary['izin']['days'] }} hari)</td>
                </tr>
            </tbody>
        </table>

        <h3>Performa Harian</h3>
        <table class="performance-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 10%;">Tanggal</th>
                    <th class="text-center" style="width: 18%;">Tepat Waktu</th>
                    <th class="text-center" style="width: 18%;">Terlambat</th>
                    <th class="text-center" style="width: 18%;">Lembur</th>
                    <th class="text-center" style="width: 18%;">Cuti</th>
                    <th class="text-center" style="width: 18%;">Izin</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $labels = $dailyPerformance['labels'] ?? [];
                    $onTime = $dailyPerformance['on_time'] ?? [];
                    $late = $dailyPerformance['late'] ?? [];
                    $lembur = $dailyPerformance['lembur'] ?? [];
                    $cuti = $dailyPerformance['cuti'] ?? [];
                    $izin = $dailyPerformance['izin'] ?? [];
                @endphp
                @forelse ($labels as $index => $label)
                    <tr>
                        <td class="text-center">{{ $label }}</td>
                        <td class="text-center">{{ $onTime[$index] ?? 0 }}</td>
                        <td class="text-center">{{ $late[$index] ?? 0 }}</td>
                        <td class="text-center">{{ $lembur[$index] ?? 0 }}</td>
                        <td class="text-center">{{ $cuti[$index] ?? 0 }}</td>
                        <td class="text-center">{{ $izin[$index] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Tidak ada data performa harian untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="note">
            Catatan: Laporan ini dikompilasi otomatis berdasarkan data kehadiran, cuti, izin, dan lembur karyawan.
        </div>
    </div>

    @include('pdf.partials.laporan-footer', [
        'tanggalCetak' => $tanggalCetak,
    ])
</body>
</html>
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

        .content {
            margin-top: 70px;
        }

        h2 {
            font-size: 16px;
            margin-bottom: 10px;
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

        .note {
            font-size: 11px;
            color: #777;
        }
    </style>
</head>

<body>
    @include('pdf.partials.laporan-header', [
        'judulDokumen' => $judulDokumen,
        'periode' => $periode,
    ])

    <div class="content">
        @php
            $ringkasan = $summary['ringkasan'] ?? [];
            $periodeLabel = $summary['periode']['label'] ?? $periode;
        @endphp

        <h2>Ringkasan Periode ({{ $periodeLabel }})</h2>
        <table>
            <tbody>
                <tr>
                    <th>Total Kehadiran</th>
                    <td class="text-right">{{ $ringkasan['total_kehadiran'] ?? 0 }}</td>
                </tr>
                <tr>
                    <th>Karyawan Terlibat</th>
                    <td class="text-right">{{ $ringkasan['karyawan_terlibat'] ?? 0 }}</td>
                </tr>
                <tr>
                    <th>Kehadiran Tepat Waktu</th>
                    <td class="text-right">
                        {{ $ringkasan['tepat_waktu'] ?? 0 }}
                        ({{ number_format($ringkasan['persentase_tepat'] ?? 0, 2, ',', '.') }}%)
                    </td>
                </tr>
                <tr>
                    <th>Keterlambatan</th>
                    <td class="text-right">
                        {{ $ringkasan['telat'] ?? 0 }}
                        ({{ number_format($ringkasan['persentase_telat'] ?? 0, 2, ',', '.') }}%)
                    </td>
                </tr>
                <tr>
                    <th>Kehadiran Tidak Tepat</th>
                    <td class="text-right">{{ $ringkasan['tidak_tepat'] ?? 0 }}</td>
                </tr>
                <tr>
                    <th>Ketidakhadiran (Alfa)</th>
                    <td class="text-right">{{ $ringkasan['alfa'] ?? 0 }}</td>
                </tr>
                <tr>
                    <th>Pulang Cepat</th>
                    <td class="text-right">{{ $ringkasan['pulang_cepat'] ?? 0 }}</td>
                </tr>
            </tbody>
        </table>

        <h2>Rekapitulasi per Karyawan</h2>
        <table>
            <thead>
                <tr>
                    <th style="width: 18%;">Karyawan</th>
                    <th style="width: 12%; text-align:center;">Total Kehadiran</th>
                    <th style="width: 10%; text-align:center;">Tepat Waktu</th>
                    <th style="width: 10%; text-align:center;">Terlambat</th>
                    <th style="width: 10%; text-align:center;">Tidak Tepat</th>
                    <th style="width: 10%; text-align:center;">Alfa</th>
                    <th style="width: 10%; text-align:center;">Pulang Cepat</th>
                    <th style="width: 10%; text-align:center;">% Tepat</th>
                    <th style="width: 5%; text-align:center;">Lembur</th>
                    <th style="width: 5%; text-align:center;">Cuti</th>
                    <th style="width: 5%; text-align:center;">Izin</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>
                            <strong>{{ $record['nama'] }}</strong><br>
                            <span style="font-size: 11px; color:#666;">
                                {{ $record['jabatan'] }} • {{ $record['departemen'] }}
                            </span>
                        </td>
                        <td class="text-center">{{ $record['total_kehadiran'] }}</td>
                        <td class="text-center">{{ $record['tepat_waktu'] }}</td>
                        <td class="text-center">{{ $record['terlambat'] }}</td>
                        <td class="text-center">{{ $record['tidak_tepat'] }}</td>
                        <td class="text-center">{{ $record['alfa'] }}</td>
                        <td class="text-center">{{ $record['pulang_cepat'] }}</td>
                        <td class="text-center">{{ number_format($record['persentase_tepat'], 2, ',', '.') }}%</td>
                        <td class="text-center">{{ $record['lembur_disetujui'] }}</td>
                        <td class="text-center">{{ $record['cuti_disetujui'] }}</td>
                        <td class="text-center">{{ $record['izin_disetujui'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">
                            Tidak ada data rekapitulasi absensi pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <p class="note">
            Catatan: Rekapitulasi ini dihasilkan otomatis berdasarkan data absensi, lembur, cuti, dan izin yang tercatat dalam sistem untuk periode terkait.
        </p>
    </div>

    @include('pdf.partials.laporan-footer', [
        'tanggalCetak' => $tanggalCetak,
    ])
</body>

</html>

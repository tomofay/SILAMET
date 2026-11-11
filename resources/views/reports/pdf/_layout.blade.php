<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Laporan SILAMET' }}</title>
    <style>
        @page {
            margin: 14mm 14mm 16mm 14mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            color: #0f172a;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #0f172a;
            margin-bottom: 10px;
            padding-bottom: 6px;
        }
        .header-brand {
            width: 54px;
            vertical-align: middle;
        }
        .brand-box {
            width: 44px;
            height: 44px;
            background-color: #1e1b4b;
            color: #ffffff;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            line-height: 44px;
            border-radius: 6px;
        }
        .header-kop {
            vertical-align: middle;
            text-align: left;
            padding-bottom: 6px;
        }
        .kop-instansi {
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.6px;
            color: #475569;
            text-transform: uppercase;
        }
        .kop-sekolah {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 1px;
        }
        .kop-jurusan {
            font-size: 9.5px;
            font-weight: bold;
            color: #1e1b4b;
        }
        .kop-alamat {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-title-bar {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            margin-bottom: 10px;
        }
        .doc-title {
            font-size: 11.5px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.3px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 8.5px;
            color: #475569;
        }
        .summary-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 0;
            margin: 0 -6px 10px -6px;
        }
        .summary-cell {
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
        }
        .summary-label {
            font-size: 7.5px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
        }
        .summary-val {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-top: 2px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        .data-table thead th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 6px 6px;
            border: 1px solid #0f172a;
            font-size: 8px;
            text-transform: uppercase;
        }
        .data-table tbody td {
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .data-table tr {
            page-break-inside: avoid;
        }
        .mono {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 8px;
        }
        .text-center {
            text-align: center !important;
        }
        .text-right {
            text-align: right !important;
        }
        .badge {
            display: inline-block;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            border: 1px solid #cbd5e1;
        }
        .badge-ok {
            background-color: #ecfdf5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        .badge-warn {
            background-color: #fffbeb;
            color: #92400e;
            border-color: #fde68a;
        }
        .badge-danger {
            background-color: #fff1f2;
            color: #9f1239;
            border-color: #fecdd3;
        }
        .badge-info {
            background-color: #eef2ff;
            color: #3730a3;
            border-color: #c7d2fe;
        }
        .badge-muted {
            background-color: #f1f5f9;
            color: #475569;
            border-color: #cbd5e1;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
            page-break-inside: avoid;
            font-size: 8.5px;
        }
        .signature-cell {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-top: 4px;
        }
        .signature-space {
            height: 46px;
        }
    </style>
</head>
<body>
    <!-- KOP SURAT RESMI -->
    <table class="header-table">
        <tr>
            <td class="header-brand">
                <div class="brand-box">S</div>
            </td>
            <td class="header-kop">
                <div class="kop-instansi">Pemerintah Daerah Provinsi Jawa Barat · Dinas Pendidikan</div>
                <div class="kop-sekolah">SMK NEGERI 1 CIBINONG</div>
                <div class="kop-jurusan">SILAMET — Laboratorium Sistem Informasi, Jaringan, dan Aplikasi (SIJA)</div>
                <div class="kop-alamat">Jl. Karadenan No. 7, Kec. Cibinong, Kab. Bogor, Jawa Barat 16913</div>
            </td>
        </tr>
    </table>

    <!-- JUDUL & METADATA -->
    <div class="doc-title-bar">
        <div class="doc-title">{{ $title }}</div>
        <table class="meta-table">
            <tr>
                <td><strong>Periode Data:</strong> {{ $period_label }}</td>
                <td><strong>Filter Status:</strong> {{ $status_filter ? strtoupper($status_filter) : 'Semua Status' }}</td>
                <td class="text-right"><strong>Dicetak:</strong> {{ $generated_at->format('d/m/Y H:i') }} WIB · {{ $printed_by }}</td>
            </tr>
        </table>
    </div>

    @yield('report-content')

    <!-- BLOK TANDA TANGAN -->
    <table class="signature-table">
        <tr>
            <td class="signature-cell">
                <div>Mengetahui,</div>
                <div><strong>Kepala Bengkel / Program SIJA</strong></div>
                <div class="signature-space"></div>
                <div>( ........................................ )</div>
            </td>
            <td class="signature-cell">
                <div>Cibinong, {{ $generated_at->translatedFormat('d F Y') }}</div>
                <div><strong>Petugas / Teknisi Laboratorium</strong></div>
                <div class="signature-space"></div>
                <div><strong>{{ $printed_by }}</strong></div>
                @if(! empty($printed_by_nip))
                    <div class="mono">NIP/NIS. {{ $printed_by_nip }}</div>
                @endif
            </td>
        </tr>
    </table>
</body>
</html>


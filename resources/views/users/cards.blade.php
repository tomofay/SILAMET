<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu User — SILAMET</title>
    
    <!-- Google Fonts: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #0f172a;
            color: #1e293b;
            padding: 24px 16px;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Controls Toolbar */
        .print-controls {
            max-width: 1140px;
            margin: 0 auto 24px auto;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .print-controls .info-title {
            color: #f8fafc;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .print-controls .info-subtitle {
            color: #94a3b8;
            font-size: 12px;
            margin-top: 2px;
        }

        .print-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-print {
            background: #4f46e5;
            color: #ffffff;
            border: 1px solid #6366f1;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.15s ease;
        }

        .btn-print:hover {
            background: #4338ca;
        }

        .btn-back {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 14px;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.15s ease;
        }

        .btn-back:hover {
            color: #ffffff;
            background: #334155;
        }

        /* Responsive Grid Kartu di Layar (Fluid, Anti-Overflow) */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
            gap: 16px;
            max-width: 1140px;
            margin: 0 auto;
            width: 100%;
        }

        /* Kartu Fisik Standard ID Card (ISO CR80 Landscape) - Biru Biasa Solid */
        .card {
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            background: #1e40af;
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            break-inside: avoid;
            transition: border-color 0.15s ease;
            box-sizing: border-box;
        }

        .card:hover {
            border-color: rgba(255, 255, 255, 0.45);
        }

        .card-inner {
            position: relative;
            z-index: 2;
            padding: 16px 18px 14px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 12px;
            height: 100%;
            box-sizing: border-box;
        }

        /* Header Kartu */
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
            padding-bottom: 10px;
        }

        .card-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-logo {
            width: 34px;
            height: 34px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
            flex-shrink: 0;
        }

        .card-title-group {
            display: flex;
            flex-direction: column;
        }

        .card-system-name {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #ffffff;
            line-height: 1.2;
        }

        .card-sub-name {
            font-size: 8.5px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            margin-top: 1px;
        }

        .card-role-badge {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 3px 9px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #ffffff;
            white-space: nowrap;
        }

        .role-siswa {
            background: rgba(16, 185, 129, 0.25);
            border-color: rgba(52, 211, 153, 0.4);
            color: #a7f3d0;
        }

        .role-guru {
            background: rgba(245, 158, 11, 0.25);
            border-color: rgba(251, 191, 36, 0.4);
            color: #fde68a;
        }

        /* Body Kartu: Data Identitas Pengguna (Lebar Penuh, Lega & Rapi) */
        .card-body {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .card-profile-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            font-weight: 800;
            color: #ffffff;
            flex-shrink: 0;
        }

        .card-name-group {
            min-width: 0;
            flex: 1;
        }

        .card-name {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
            color: #ffffff;
            word-break: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 2px;
        }

        .card-meta-list {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .card-meta-row {
            font-size: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.3;
        }

        .card-meta-label {
            font-size: 8.5px;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.65);
            width: 48px;
            flex-shrink: 0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .card-meta-val {
            font-weight: 600;
            color: #ffffff;
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-meta-val.val-mono {
            font-family: 'JetBrains Mono', monospace;
            letter-spacing: 0.3px;
        }

        /* Stiker QR Code Putih Horizontal (Sesuai Foto: Putih, QR di Kiri, Kode di Kanan) */
        .card-qr-sticker {
            background: #ffffff;
            border-radius: 10px;
            padding: 6px 14px 6px 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-sizing: border-box;
            width: 100%;
        }

        /* Kotak QR Code BENAR-BENAR KOTAK BUJUR SANGKAR (1:1) */
        .card-qr-square {
            width: 52px;
            height: 52px;
            aspect-ratio: 1 / 1;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .card-qr-square canvas {
            display: none !important;
        }

        .card-qr-square img {
            width: 100% !important;
            height: 100% !important;
            aspect-ratio: 1 / 1 !important;
            object-fit: contain !important;
            display: block !important;
            margin: 0 auto !important;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: pixelated;
        }

        /* Teks Kode di Sisi Kanan QR Code (Besar & Jelas Seperti di Foto) */
        .card-qr-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.25;
            min-width: 0;
            flex: 1;
        }

        .card-qr-token {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-qr-hint {
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #64748b;
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* Footer Kartu */
        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 8px;
            color: rgba(255, 255, 255, 0.55);
            padding-top: 3px;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
            font-family: 'JetBrains Mono', monospace;
        }

        /* Responsif Khusus Layar Kecil (Smartphone / Mobile < 480px) */
        @media (max-width: 480px) {
            body {
                padding: 16px 12px;
            }

            .print-controls {
                padding: 14px 16px;
                border-radius: 14px;
                gap: 12px;
            }

            .cards-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .card {
                max-width: 100%;
            }

            .card-inner {
                padding: 14px 14px 12px 14px;
                gap: 10px;
            }

            .card-qr-token {
                font-size: 13px;
            }

            .card-qr-square {
                width: 46px;
                height: 46px;
            }
        }

        /* ========================================================================= */
        /* PRINT STYLING KHUSUS A4 — 100% PAS 1 HALAMAN (8 KARTU), ANTI-POTONG */
        /* ========================================================================= */
        @media print {
            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            html, body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .print-controls {
                display: none !important;
            }

            .cards-grid {
                display: grid !important;
                grid-template-columns: repeat(2, 85.6mm) !important;
                justify-content: center !important;
                gap: 2.8mm 4.5mm !important;
                padding: 1.5mm 0 !important;
                margin: 0 auto !important;
                max-width: 100% !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            /* Kartu Standard ID Card CR80: 85.6mm x 52mm - Biru Biasa Solid */
            .card {
                width: 85.6mm !important;
                height: 52mm !important;
                max-width: none !important;
                margin: 0 !important;
                border-radius: 2.5mm !important;
                box-shadow: none !important;
                border: 0.25pt solid #cbd5e1 !important;
                background: #1e40af !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                overflow: hidden !important;
            }

            .card-inner {
                padding: 2.2mm 3.2mm 1.8mm 3.2mm !important;
                gap: 1.2mm !important;
                height: 100% !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                box-sizing: border-box !important;
            }

            .card-header {
                padding-bottom: 1mm !important;
                border-bottom: 0.25pt solid rgba(255, 255, 255, 0.25) !important;
            }

            .card-logo {
                width: 5.2mm !important;
                height: 5.2mm !important;
                font-size: 8.5pt !important;
                border-radius: 1.2mm !important;
            }

            .card-system-name {
                font-size: 8pt !important;
                line-height: 1.1 !important;
            }

            .card-sub-name {
                font-size: 6pt !important;
            }

            .card-role-badge {
                font-size: 6pt !important;
                padding: 1px 5px !important;
            }

            /* Body Identitas di Print */
            .card-body {
                gap: 1mm !important;
            }

            .card-profile-header {
                gap: 2mm !important;
            }

            .card-avatar {
                width: 6.8mm !important;
                height: 6.8mm !important;
                font-size: 9pt !important;
                border-radius: 1.4mm !important;
            }

            .card-name {
                font-size: 8pt !important;
                line-height: 1.15 !important;
                margin-bottom: 0.3mm !important;
            }

            .card-meta-list {
                gap: 0.4mm !important;
            }

            .card-meta-row {
                font-size: 6pt !important;
                line-height: 1.15 !important;
            }

            .card-meta-label {
                font-size: 5.2pt !important;
                width: 22px !important;
            }

            /* Stiker QR Code Putih di Print (QR di Kiri, Kode di Kanan) */
            .card-qr-sticker {
                background: #ffffff !important;
                border-radius: 1.5mm !important;
                border: 0.2pt solid rgba(0, 0, 0, 0.25) !important;
                padding: 1mm 2.5mm 1mm 1.5mm !important;
                gap: 2.5mm !important;
                box-shadow: none !important;
                box-sizing: border-box !important;
                display: flex !important;
                align-items: center !important;
                width: 100% !important;
            }

            .card-qr-square {
                width: 13.5mm !important;
                height: 13.5mm !important;
                aspect-ratio: 1 / 1 !important;
                flex-shrink: 0 !important;
            }

            .card-qr-square canvas {
                display: none !important;
            }

            .card-qr-square img {
                width: 13.5mm !important;
                height: 13.5mm !important;
                aspect-ratio: 1 / 1 !important;
                object-fit: contain !important;
                display: block !important;
            }

            .card-qr-info {
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                line-height: 1.1 !important;
            }

            .card-qr-token {
                font-size: 8pt !important;
                font-weight: 800 !important;
                letter-spacing: 0.3px !important;
                color: #000000 !important;
            }

            .card-qr-hint {
                font-size: 4pt !important;
                font-weight: 700 !important;
                color: #64748b !important;
                margin-top: 0.2mm !important;
            }

            .card-footer {
                font-size: 4.8pt !important;
                padding-top: 0.3mm !important;
                line-height: 1 !important;
                border-top: 0.25pt solid rgba(255, 255, 255, 0.15) !important;
            }

            @page {
                size: A4 portrait;
                margin: 4mm 4mm;
            }
        }
    </style>
</head>
<body>

    <!-- Controls Toolbar (Hilang saat diprint) -->
    <div class="print-controls">
        <div>
            <div class="info-title">Cetak Kartu Pengguna Laboratorium SIJA</div>
            <div class="info-subtitle">
                Total {{ count($users) }} kartu siap dicetak pada kertas A4 (8 kartu per lembar). Format QR Code di kiri dan kode ID di kanan sesuai stiker standar.
            </div>
        </div>
        <div class="print-actions">
            <a href="{{ route('users.index') }}" class="btn-back">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar User</span>
            </a>
            <button onclick="window.print()" class="btn-print">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Kartu (Print / PDF)</span>
            </button>
        </div>
    </div>

    <!-- Grid Kartu Pengguna -->
    <div class="cards-grid">
        @foreach($users as $user)
        @php
            $isSiswa = ($user->role?->value ?? $user->role) === 'siswa';
            $initial = strtoupper(substr($user->name, 0, 1));
            $barcodeVal = \App\Services\BarcodeTokenService::getOrCreateToken($user);
        @endphp
        <div class="card">
            <div class="card-inner">
                <!-- Header Kartu -->
                <div class="card-header">
                    <div class="card-brand">
                        <div class="card-logo">SL</div>
                        <div class="card-title-group">
                            <span class="card-system-name">SILAMET — LAB SIJA</span>
                            <span class="card-sub-name">SMK Negeri 1 Cibinong</span>
                        </div>
                    </div>
                    <span class="card-role-badge {{ $isSiswa ? 'role-siswa' : 'role-guru' }}">
                        {{ $user->role?->label() ?? strtoupper($user->role ?? 'USER') }}
                    </span>
                </div>

                <!-- Body Kartu: Data Identitas Pengguna (Lega & Responsif) -->
                <div class="card-body">
                    <div class="card-profile-header">
                        <div class="card-avatar">
                            {{ $initial }}
                        </div>
                        <div class="card-name-group">
                            <div class="card-name" title="{{ $user->name }}">{{ $user->name }}</div>
                            <div class="card-meta-row">
                                <span class="card-meta-label">NIS/NIP</span>
                                <span class="card-meta-val val-mono">{{ $user->nis_nip ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-meta-list">
                        @if($user->phone)
                        <div class="card-meta-row">
                            <span class="card-meta-label">No. WA</span>
                            <span class="card-meta-val val-mono">{{ $user->phone }}</span>
                        </div>
                        @endif
                        @if($user->email)
                        <div class="card-meta-row">
                            <span class="card-meta-label">Email</span>
                            <span class="card-meta-val">{{ $user->email }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Stiker QR Code Putih Bersih (Sesuai Foto: QR di Kiri, Kode di Kanan) -->
                <div class="card-qr-sticker">
                    <div class="card-qr-square">
                        <div id="qrcode-{{ $user->id }}"></div>
                    </div>
                    <div class="card-qr-info">
                        <div class="card-qr-token">{{ $barcodeVal }}</div>
                        <div class="card-qr-hint">ID USER KIOSK MANDIRI</div>
                    </div>
                </div>

                <!-- Footer Kartu -->
                <div class="card-footer">
                    <span>ID: {{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span>
                    <span>SMK BISA — SIJA HEBAT</span>
                    <span>{{ date('Y') }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Library QRCode.js (Local dengan CDN Fallback) -->
    <script src="{{ asset('js/qrcode.min.js') }}"></script>
    <script>
        if (typeof QRCode === 'undefined') {
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>');
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @foreach($users as $user)
            try {
                const code = "{{ \App\Services\BarcodeTokenService::getOrCreateToken($user) }}";
                const target = document.getElementById("qrcode-{{ $user->id }}");
                if (target) {
                    new QRCode(target, {
                        text: code,
                        width: 140,
                        height: 140,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            } catch (e) {
                console.warn('QR Code generation error for user {{ $user->id }}:', e);
            }
            @endforeach
        });
    </script>
</body>
</html>

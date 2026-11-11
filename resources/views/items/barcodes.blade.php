<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label QR Barang — SILAMET</title>
    
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
        }

        /* Controls Toolbar */
        .print-controls {
            max-width: 1100px;
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

        /* Grid Stiker Label di Layar */
        .labels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 14px;
            max-width: 1100px;
            margin: 0 auto;
        }

        /* Desain Stiker Fisik Bersih (Sesuai Foto Kardus Router) */
        .label-sticker {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 14px;
            page-break-inside: avoid;
            break-inside: avoid;
            transition: border-color 0.15s ease;
        }

        .label-sticker:hover {
            border-color: #6366f1;
        }

        /* Kotak QR Code di Sisi Kiri (Bujur Sangkar Sempurna 1:1) */
        .label-qr-box {
            width: 72px;
            height: 72px;
            aspect-ratio: 1 / 1;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .label-qr-box canvas {
            display: none !important;
        }

        .label-qr-box img {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
            aspect-ratio: 1 / 1 !important;
            object-fit: contain !important;
            display: block !important;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: pixelated;
        }

        /* Kolom Teks di Sisi Kanan (Besar, Jelas, Vertically Centered) */
        .label-info {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            line-height: 1.25;
        }

        /* Kode Barang Besar & Tegas Seperti di Foto */
        .label-code {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 19px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #0f172a;
            word-break: break-all;
        }

        .label-name {
            font-size: 11.5px;
            font-weight: 600;
            color: #475569;
            margin-top: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .label-sub {
            font-size: 9px;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* ========================================================================= */
        /* PRINT STYLING KHUSUS KERTAS LABEL STIKER / A4 */
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

            .labels-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 64mm) !important;
                justify-content: center !important;
                gap: 2.5mm 3.5mm !important;
                padding: 4mm 0 !important;
                margin: 0 auto !important;
                max-width: 100% !important;
            }

            .label-sticker {
                width: 64mm !important;
                height: 24mm !important;
                padding: 2.5mm 3.5mm !important;
                border: 0.2pt solid #cbd5e1 !important;
                border-radius: 1.5mm !important;
                box-shadow: none !important;
                gap: 3mm !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                overflow: hidden !important;
                background: #ffffff !important;
            }

            .label-qr-box {
                width: 19mm !important;
                height: 19mm !important;
                aspect-ratio: 1 / 1 !important;
            }

            .label-qr-box canvas {
                display: none !important;
            }

            .label-qr-box img {
                width: 19mm !important;
                height: 19mm !important;
                aspect-ratio: 1 / 1 !important;
                object-fit: contain !important;
                display: block !important;
            }

            .label-code {
                font-size: 12.5pt !important;
                letter-spacing: 0.3px !important;
                line-height: 1.1 !important;
            }

            .label-name {
                font-size: 7.5pt !important;
                margin-top: 0.6mm !important;
                line-height: 1.1 !important;
                color: #334155 !important;
            }

            .label-sub {
                font-size: 5.5pt !important;
                margin-top: 0.4mm !important;
                color: #64748b !important;
            }

            @page {
                size: A4 portrait;
                margin: 6mm 6mm;
            }
        }
    </style>
</head>
<body>

    <!-- Controls Toolbar (Hilang saat diprint) -->
    <div class="print-controls">
        <div>
            <div class="info-title">Cetak Label QR Barang Inventaris — SILAMET</div>
            <div class="info-subtitle">
                Total {{ count($items) }} stiker siap dicetak pada kertas label stiker / A4. Format minimalis: QR Code di kiri dan kode barang di kanan.
            </div>
        </div>
        <div class="print-actions">
            <a href="{{ route('items.index') }}" class="btn-back">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Master Aset</span>
            </a>
            <button onclick="window.print()" class="btn-print">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Cetak Label (Print / PDF)</span>
            </button>
        </div>
    </div>

    <!-- Grid Stiker Label Barang -->
    <div class="labels-grid">
        @foreach($items as $item)
        @php
            $qrPayload = $item->item_code;
        @endphp
        <div class="label-sticker">
            <!-- QR Code Kotak di Sisi Kiri -->
            <div class="label-qr-box">
                <div id="qrcode-{{ $item->id }}"></div>
            </div>

            <!-- Kode Barang di Sisi Kanan (Besar & Jelas) -->
            <div class="label-info">
                <div class="label-code">{{ $item->item_code }}</div>
                <div class="label-name" title="{{ $item->name }}">{{ $item->name }}</div>
                <div class="label-sub">SILAMET • {{ $item->location }}</div>
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
            @foreach($items as $item)
            try {
                const target = document.getElementById("qrcode-{{ $item->id }}");
                if (target) {
                    new QRCode(target, {
                        text: "{{ $item->item_code }}",
                        width: 140,
                        height: 140,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            } catch (e) {
                console.warn('QR error for item {{ $item->id }}:', e);
            }
            @endforeach
        });
    </script>
</body>
</html>

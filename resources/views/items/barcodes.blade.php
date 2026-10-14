<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Barcode Barang — SILAMET</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }

        .print-controls {
            text-align: center;
            margin-bottom: 24px;
        }

        .print-controls button {
            background: #1e40af;
            color: white;
            border: none;
            padding: 10px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .print-controls button:hover { background: #1e3a8a; }

        .print-controls a {
            margin-left: 12px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }

        .print-controls .info {
            margin-top: 8px;
            font-size: 12px;
            color: #9ca3af;
        }

        .labels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
            max-width: 900px;
            margin: 0 auto;
        }

        .label {
            width: 260px;
            background: white;
            border: 1.5px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px 16px;
            position: relative;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .label-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1e40af 0%, #3b82f6 50%, #06b6d4 100%);
        }

        .label-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-top: 4px;
        }

        .label-school {
            font-size: 8px;
            font-weight: 600;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .label-type {
            font-size: 7px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 2px 6px;
            border-radius: 4px;
        }

        .label-type-aset {
            background: #dbeafe;
            color: #1e40af;
        }

        .label-type-bahan {
            background: #f3f4f6;
            color: #6b7280;
        }

        .label-name {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            line-height: 1.3;
            margin-bottom: 4px;
            max-height: 34px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .label-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 10px;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .label-barcode {
            text-align: center;
            background: #fafafa;
            border-radius: 6px;
            padding: 8px 4px 4px;
            border: 1px solid #f3f4f6;
        }

        .label-barcode svg {
            display: block;
            margin: 0 auto;
        }

        .label-barcode-text {
            font-family: 'JetBrains Mono', monospace;
            font-size: 8px;
            color: #9ca3af;
            margin-top: 4px;
            letter-spacing: 1px;
        }

        .label-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 7px;
            color: #d1d5db;
        }

        .label-location {
            font-size: 8px;
            color: #9ca3af;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .label-epc {
            font-family: 'JetBrains Mono', monospace;
            font-size: 7px;
            color: #c084fc;
            margin-top: 2px;
            word-break: break-all;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .print-controls { display: none; }

            .labels-grid {
                gap: 8px;
                grid-template-columns: repeat(3, 1fr);
            }

            .label {
                box-shadow: none;
                border: 1px solid #e5e7eb;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: A4 portrait;
                margin: 8mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button onclick="window.print()">🖨️ Cetak {{ count($items) }} Label Barcode</button>
        <a href="{{ route('items.index') }}">← Kembali ke Master Aset</a>
        <div class="info">Label barcode berisi kode barang, nama, lokasi, dan EPC tag (jika ada). Siap dicetak pada kertas label stiker.</div>
    </div>

    <div class="labels-grid">
        @foreach($items as $item)
        <div class="label">
            <div class="label-accent"></div>

            <div class="label-header">
                <span class="label-school">SILAMET — Lab SIJA</span>
                <span class="label-type {{ $item->type->value === 'aset' ? 'label-type-aset' : 'label-type-bahan' }}">
                    {{ $item->type->label() }}
                </span>
            </div>

            <div class="label-name">{{ $item->name }}</div>
            <div class="label-code">{{ $item->item_code }}</div>

            <div class="label-barcode">
                <svg id="barcode-{{ $item->id }}"></svg>
                <div class="label-barcode-text">{{ $item->item_code }}</div>
            </div>

            @if($item->epc_tag)
                <div class="label-epc">EPC: {{ $item->epc_tag }}</div>
            @endif

            <div class="label-location">
                📍 {{ $item->location }}
            </div>

            <div class="label-footer">
                <span>ID: {{ str_pad($item->id, 5, '0', STR_PAD_LEFT) }}</span>
                <span>{{ now()->format('Y') }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @foreach($items as $item)
            try {
                JsBarcode("#barcode-{{ $item->id }}", "{{ $item->item_code }}", {
                    format: "CODE128",
                    width: 1.5,
                    height: 40,
                    displayValue: false,
                    margin: 0,
                    background: "transparent",
                    lineColor: "#1e293b"
                });
            } catch (e) {
                console.warn('Barcode error for item {{ $item->id }}:', e);
            }
            @endforeach
        });
    </script>
</body>
</html>

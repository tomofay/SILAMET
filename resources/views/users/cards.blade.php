<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Kartu User — SILAMET</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

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

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            width: 340px;
            height: 215px;
            border-radius: 14px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 40%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(30, 58, 138, 0.3);
            page-break-inside: avoid;
        }

        .card-pattern {
            position: absolute;
            inset: 0;
            opacity: 0.06;
            background-image:
                radial-gradient(circle at 20% 80%, white 1px, transparent 1px),
                radial-gradient(circle at 80% 20%, white 1px, transparent 1px);
            background-size: 30px 30px;
        }

        .card-stripe {
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 100%;
            background: linear-gradient(180deg, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0.03) 100%);
            clip-path: polygon(30% 0%, 100% 0%, 100% 100%, 0% 100%);
        }

        .card-inner {
            position: relative;
            z-index: 1;
            padding: 18px 22px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            padding-bottom: 12px;
        }

        .card-logo {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .card-school {
            flex: 1;
        }

        .card-school-name {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            opacity: 0.9;
        }

        .card-school-sub {
            font-size: 8px;
            opacity: 0.6;
            margin-top: 1px;
        }

        .card-body {
            flex: 1;
            display: flex;
            gap: 16px;
        }

        .card-info {
            flex: 1;
        }

        .card-name {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 6px;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card-detail {
            font-size: 9px;
            opacity: 0.7;
            margin-bottom: 3px;
            display: flex;
            gap: 4px;
        }

        .card-detail-label {
            width: 40px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.6;
            flex-shrink: 0;
        }

        .card-detail-value {
            font-weight: 500;
            opacity: 1;
        }

        .card-role {
            display: inline-block;
            margin-top: 6px;
            background: rgba(255,255,255,0.18);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-barcode {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .card-barcode svg {
            background: white;
            padding: 6px;
            border-radius: 6px;
        }

        .card-barcode-label {
            font-size: 8px;
            font-weight: 600;
            opacity: 0.6;
            letter-spacing: 1px;
        }

        .card-footer {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 7px;
            opacity: 0.4;
        }

        @media print {
            body { background: white; padding: 0; }
            .print-controls { display: none; }
            .cards-grid {
                gap: 12px;
                grid-template-columns: repeat(2, 1fr);
            }
            .card {
                box-shadow: none;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <button onclick="window.print()">🖨️ Cetak Kartu ({{ count($users) }} kartu)</button>
        <a href="{{ route('users.index') }}">← Kembali ke Daftar User</a>
    </div>

    <div class="cards-grid">
        @foreach($users as $user)
        <div class="card">
            <div class="card-pattern"></div>
            <div class="card-stripe"></div>
            <div class="card-inner">
                <div class="card-header">
                    <div class="card-logo">SL</div>
                    <div class="card-school">
                        <div class="card-school-name">SILAMET — Lab SIJA</div>
                        <div class="card-school-sub">SMK Negeri 1 Cibinong</div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="card-info">
                        <div class="card-name">{{ $user->name }}</div>
                        <div class="card-detail">
                            <span class="card-detail-label">NIS/NIP</span>
                            <span class="card-detail-value">{{ $user->nis_nip ?? '—' }}</span>
                        </div>
                        @if($user->email)
                        <div class="card-detail">
                            <span class="card-detail-label">Email</span>
                            <span class="card-detail-value">{{ $user->email }}</span>
                        </div>
                        @endif
                        @if($user->phone)
                        <div class="card-detail">
                            <span class="card-detail-label">Telp</span>
                            <span class="card-detail-value">{{ $user->phone }}</span>
                        </div>
                        @endif
                        <span class="card-role">{{ $user->role?->label() ?? 'User' }}</span>
                    </div>

                    <div class="card-barcode">
                        {{-- Generate barcode Code128 inline SVG --}}
                        <svg id="barcode-{{ $user->id }}" width="80" height="60"></svg>
                        <span class="card-barcode-label">{{ $user->nis_nip }}</span>
                    </div>
                </div>

                <div class="card-footer">
                    <span>ID: {{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span>
                    <span>{{ now()->format('Y') }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- JsBarcode CDN for barcode generation --}}
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @foreach($users as $user)
            try {
                JsBarcode("#barcode-{{ $user->id }}", "{{ $user->nis_nip ?? $user->id }}", {
                    format: "CODE128",
                    width: 1.2,
                    height: 40,
                    displayValue: false,
                    margin: 2,
                    background: "#ffffff",
                    lineColor: "#1e3a8a"
                });
            } catch (e) {
                console.warn('Barcode error for user {{ $user->id }}:', e);
            }
            @endforeach
        });
    </script>
</body>
</html>

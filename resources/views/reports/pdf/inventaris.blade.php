@extends('reports.pdf._layout')

@section('report-content')
    <table class="summary-table">
        <tr>
            <td class="summary-cell">
                <div class="summary-label">Total Barang Terdaftar</div>
                <div class="summary-val">{{ number_format($summary['total']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Aset Tetap</div>
                <div class="summary-val">{{ number_format($summary['aset']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Bahan Habis Pakai</div>
                <div class="summary-val">{{ number_format($summary['bhp']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Tersedia</div>
                <div class="summary-val">{{ number_format($summary['tersedia']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Dipinjam / Maintenance</div>
                <div class="summary-val">{{ number_format($summary['dipinjam']) }} / {{ number_format($summary['maintenance']) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 28px;">No</th>
                <th style="width: 78px;">Kode Aset</th>
                <th style="width: 82px;">Token Barcode</th>
                <th style="width: 135px;">EPC Tag RFID</th>
                <th>Nama Barang</th>
                <th style="width: 92px;">Tipe</th>
                <th class="text-center" style="width: 38px;">Stok</th>
                <th style="width: 82px;">Lokasi / Rak</th>
                <th class="text-center" style="width: 88px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $idx => $item)
                @php
                    $statusBadge = match($item->status->value) {
                        'tersedia' => 'badge-ok',
                        'dipinjam' => 'badge-info',
                        'under_maintenance' => 'badge-warn',
                        default => 'badge-muted',
                    };
                @endphp
                <tr>
                    <td class="text-center mono">{{ $idx + 1 }}</td>
                    <td class="mono">{{ $item->item_code }}</td>
                    <td class="mono">{{ $item->barcode_token ?? '-' }}</td>
                    <td class="mono">{{ $item->epc_tag ?? '-' }}</td>
                    <td><strong>{{ $item->name }}</strong></td>
                    <td>{{ $item->type?->label() ?? '-' }}</td>
                    <td class="text-center mono">{{ $item->stock }}</td>
                    <td class="mono">{{ $item->location ?? '-' }}</td>
                    <td class="text-center"><span class="badge {{ $statusBadge }}">{{ $item->status?->label() ?? '-' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 14px; color: #64748b;">Tidak ada data aset laboratorium sesuai filter yang dipilih.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection


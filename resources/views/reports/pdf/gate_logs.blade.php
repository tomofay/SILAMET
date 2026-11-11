@extends('reports.pdf._layout')

@section('report-content')
    <table class="summary-table">
        <tr>
            <td class="summary-cell">
                <div class="summary-label">Total Deteksi Pintu</div>
                <div class="summary-val">{{ number_format($summary['total']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Akses Sah (Allowed)</div>
                <div class="summary-val" style="color: #065f46;">{{ number_format($summary['allowed']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Pelanggaran / Alarm (Denied)</div>
                <div class="summary-val" style="color: #9f1239;">{{ number_format($summary['denied']) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 28px;">No</th>
                <th style="width: 96px;">Waktu Deteksi</th>
                <th style="width: 145px;">EPC Tag Terbaca</th>
                <th style="width: 78px;">Kode Aset</th>
                <th>Identifikasi Barang</th>
                <th style="width: 82px;">Lokasi Rak</th>
                <th class="text-center" style="width: 76px;">Status Gate</th>
                <th class="text-center" style="width: 68px;">Foto Kamera</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $idx => $log)
                <tr>
                    <td class="text-center mono">{{ $idx + 1 }}</td>
                    <td class="mono">{{ optional($log->created_at)->format('d/m/Y H:i:s') ?? '-' }}</td>
                    <td class="mono">{{ $log->epc_tag_scanned }}</td>
                    <td class="mono">{{ $log->item?->item_code ?? '-' }}</td>
                    <td>
                        @if($log->item)
                            <strong>{{ $log->item->name }}</strong>
                            @if($log->item->trashed())
                                <span class="badge badge-muted">DIHAPUS</span>
                            @endif
                        @else
                            <span style="color: #64748b; font-style: italic;">Tag Asing / Tidak Terdaftar</span>
                        @endif
                    </td>
                    <td class="mono">{{ $log->item?->location ?? '-' }}</td>
                    <td class="text-center">
                        @if($log->action_status->value === 'allowed')
                            <span class="badge badge-ok">ALLOWED</span>
                        @else
                            <span class="badge badge-danger">DENIED</span>
                        @endif
                    </td>
                    <td class="text-center mono">{{ $log->capture_image_path ? 'Tersedia' : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center" style="padding: 14px; color: #64748b;">Tidak ada log deteksi gerbang RFID pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection


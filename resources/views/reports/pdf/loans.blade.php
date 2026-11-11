@extends('reports.pdf._layout')

@section('report-content')
    <table class="summary-table">
        <tr>
            <td class="summary-cell">
                <div class="summary-label">Total Transaksi</div>
                <div class="summary-val">{{ number_format($summary['total']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Masih Dipinjam (Aktif)</div>
                <div class="summary-val">{{ number_format($summary['aktif']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Melewati Jatuh Tempo</div>
                <div class="summary-val" style="color: {{ $summary['overdue'] > 0 ? '#9f1239' : '#0f172a' }};">{{ number_format($summary['overdue']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Sudah Dikembalikan</div>
                <div class="summary-val">{{ number_format($summary['selesai']) }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 26px;">No</th>
                <th style="width: 68px;">NIS / NIP</th>
                <th>Peminjam</th>
                <th style="width: 72px;">Kode Alat</th>
                <th>Nama Barang</th>
                <th style="width: 74px;">Durasi</th>
                <th style="width: 82px;">Waktu Pinjam</th>
                <th style="width: 82px;">Jatuh Tempo</th>
                <th style="width: 82px;">Dikembalikan</th>
                <th class="text-center" style="width: 58px;">Kondisi</th>
                <th class="text-center" style="width: 76px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($loans as $idx => $loan)
                @php
                    $isOverdue = $loan->isOverdue();
                    $statusBadge = match(true) {
                        $isOverdue => 'badge-danger',
                        $loan->status->value === 'selesai' => 'badge-ok',
                        $loan->status->value === 'menunggu_approval_kembali' => 'badge-warn',
                        default => 'badge-info',
                    };
                @endphp
                <tr>
                    <td class="text-center mono">{{ $idx + 1 }}</td>
                    <td class="mono">{{ $loan->user?->nis_nip ?? '-' }}</td>
                    <td><strong>{{ $loan->user?->name ?? '-' }}</strong></td>
                    <td class="mono">{{ $loan->item?->item_code ?? '-' }}</td>
                    <td>{{ $loan->item?->name ?? '-' }}</td>
                    <td>{{ $loan->usage_duration?->label() ?? '-' }}</td>
                    <td class="mono">{{ optional($loan->borrowed_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="mono">{{ optional($loan->due_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="mono">{{ optional($loan->returned_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="text-center">
                        @if($loan->condition_in === 'bagus')
                            <span class="badge badge-ok">Bagus</span>
                        @elseif($loan->condition_in === 'rusak')
                            <span class="badge badge-danger">Rusak</span>
                        @else
                            <span class="mono">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $statusBadge }}">
                            {{ $isOverdue ? 'Terlambat' : ($loan->status?->label() ?? '-') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center" style="padding: 14px; color: #64748b;">Tidak ada catatan peminjaman pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection


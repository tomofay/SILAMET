@extends('reports.pdf._layout')

@section('report-content')
    <table class="summary-table">
        <tr>
            <td class="summary-cell">
                <div class="summary-label">Total Tiket</div>
                <div class="summary-val">{{ number_format($summary['total']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Menunggu (Open)</div>
                <div class="summary-val" style="color: #9f1239;">{{ number_format($summary['open']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Dalam Perbaikan</div>
                <div class="summary-val">{{ number_format($summary['in_progress']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Selesai (Resolved)</div>
                <div class="summary-val" style="color: #065f46;">{{ number_format($summary['resolved']) }}</div>
            </td>
            <td class="summary-cell">
                <div class="summary-label">Total Biaya Servis</div>
                <div class="summary-val">Rp {{ number_format($summary['total_cost'], 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 26px;">No</th>
                <th style="width: 78px;">Tgl Lapor</th>
                <th style="width: 70px;">Kode</th>
                <th style="width: 115px;">Barang</th>
                <th style="width: 88px;">Pelapor</th>
                <th>Kendala</th>
                <th>Tindakan &amp; Teknisi</th>
                <th class="text-right" style="width: 74px;">Biaya (Rp)</th>
                <th class="text-center" style="width: 68px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $idx => $ticket)
                @php
                    $cost = (float) $ticket->logs->sum('cost');
                    $statusBadge = match($ticket->status->value) {
                        'open' => 'badge-danger',
                        'in_progress' => 'badge-warn',
                        'resolved' => 'badge-ok',
                        default => 'badge-muted',
                    };
                @endphp
                <tr>
                    <td class="text-center mono">{{ $idx + 1 }}</td>
                    <td class="mono">{{ optional($ticket->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="mono">{{ $ticket->item?->item_code ?? '-' }}</td>
                    <td><strong>{{ $ticket->item?->name ?? '-' }}</strong></td>
                    <td>{{ $ticket->reportedBy?->name ?? '-' }}</td>
                    <td>{{ $ticket->issue_description }}</td>
                    <td>
                        @forelse($ticket->logs as $log)
                            <div style="margin-bottom: 2px;">
                                <strong>[{{ $log->service_type?->label() ?? '-' }}]</strong> {{ $log->action_taken }}
                                @if($log->handledBy)
                                    <span style="color: #64748b;">({{ $log->handledBy->name }})</span>
                                @endif
                            </div>
                        @empty
                            <span style="color: #64748b;">Belum ada tindakan</span>
                        @endforelse
                    </td>
                    <td class="text-right mono">{{ number_format($cost, 0, ',', '.') }}</td>
                    <td class="text-center"><span class="badge {{ $statusBadge }}">{{ $ticket->status?->label() ?? '-' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 14px; color: #64748b;">Tidak ada tiket perbaikan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection


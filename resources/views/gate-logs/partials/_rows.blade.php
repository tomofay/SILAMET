@forelse($logs as $log)
    @php
        $photoUrl = $log->capture_image_path ? route('gate-logs.photo', $log->id) : null;
        $modalItemName = $log->item
            ? ($log->item->trashed() ? $log->item->name . ' (DIHAPUS)' : $log->item->name)
            : 'Tag Asing / Tidak Terdaftar';
    @endphp
    <tr class="transition-colors {{ $log->action_status->value === 'denied' ? 'bg-rose-50/30 hover:bg-rose-50/60' : 'hover:bg-neutral-50' }}">
        <td class="px-4 py-3 whitespace-nowrap">
            <div class="font-mono font-semibold text-neutral-900 text-xs">
                {{ optional($log->created_at)->format('d/m/Y H:i:s') }}
            </div>
            <div class="text-[11px] text-neutral-500 mt-0.5">
                {{ optional($log->created_at)->diffForHumans() }}
            </div>
        </td>
        <td class="px-4 py-3 whitespace-nowrap">
            <span class="inline-block px-2 py-0.5 rounded-md bg-neutral-100 text-neutral-800 font-mono text-xs font-semibold border border-neutral-200">
                {{ $log->epc_tag_scanned }}
            </span>
        </td>
        <td class="px-4 py-3">
            @if($log->item)
                <div class="flex items-center gap-1.5">
                    <span class="font-semibold text-neutral-900 text-xs">{{ $log->item->name }}</span>
                    @if($log->item->trashed())
                        <span class="font-mono text-[10px] font-semibold px-1.5 py-0.5 rounded bg-neutral-100 text-neutral-500 border border-neutral-200">DIHAPUS</span>
                    @endif
                </div>
                <div class="text-[11px] text-neutral-500 font-mono mt-0.5">{{ $log->item->item_code }} · Rak: {{ $log->item->location ?? '-' }}</div>
            @else
                <span class="inline-flex items-center gap-1 text-xs text-neutral-400 italic">
                    (Tag Asing / Tidak Terdaftar di Database)
                </span>
            @endif
        </td>
        <td class="px-4 py-3 whitespace-nowrap">
            @if($log->action_status->value === 'allowed')
                <span class="inline-flex items-center gap-1.5 font-mono text-[11px] font-bold px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    ALLOWED
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 font-mono text-[11px] font-bold px-2.5 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200">
                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    DENIED (ALARM)
                </span>
            @endif
        </td>
        <td class="px-4 py-3 whitespace-nowrap">
            @if($photoUrl)
                <button type="button"
                    data-photo="{{ $photoUrl }}"
                    data-epoch="{{ $log->epc_tag_scanned }}"
                    data-item="{{ $modalItemName }}"
                    data-time="{{ optional($log->created_at)->format('d/m/Y H:i:s') }}"
                    class="open-photo-modal group inline-flex items-center gap-2 rounded-lg border border-neutral-200 bg-white p-1 pr-2 hover:border-rose-300 hover:bg-rose-50/40 transition-colors"
                    title="Perbesar Foto Snapshot Pelanggaran"
                    aria-label="Perbesar Foto Snapshot {{ $log->epc_tag_scanned }}">
                    <img src="{{ $photoUrl }}"
                        alt="Snapshot {{ $log->epc_tag_scanned }}"
                        loading="lazy"
                        class="w-14 h-9 object-cover rounded border border-neutral-200 bg-neutral-900"
                        onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden'); this.nextElementSibling.classList.add('inline-flex');">
                    <span class="hidden w-14 h-9 rounded border border-neutral-200 bg-neutral-100 text-neutral-400 items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <span class="text-[11px] font-semibold text-neutral-600 group-hover:text-rose-700">Lihat</span>
                </button>
            @else
                <span class="font-mono text-xs text-neutral-400">—</span>
            @endif
        </td>
        <td class="px-4 py-3 text-right whitespace-nowrap">
            <div class="inline-flex items-center justify-end gap-1">
                @if($photoUrl)
                    <button type="button"
                        data-photo="{{ $photoUrl }}"
                        data-epoch="{{ $log->epc_tag_scanned }}"
                        data-item="{{ $modalItemName }}"
                        data-time="{{ optional($log->created_at)->format('d/m/Y H:i:s') }}"
                        class="open-photo-modal btn-icon-action text-rose-600 hover:text-rose-800 hover:bg-rose-50 hover:border-rose-200"
                        title="Lihat Foto Snapshot Pelanggaran"
                        aria-label="Lihat Foto Snapshot {{ $log->epc_tag_scanned }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                @endif
                <a href="{{ route('gate-logs.show', $log->id) }}" class="btn-icon-action hover:text-primary-600" title="Detail Log Gate" aria-label="Detail Log Gate #{{ $log->id }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </a>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" class="px-6 py-12 text-center">
            <div class="p-2.5 rounded-lg bg-neutral-100 text-neutral-500 inline-flex items-center justify-center mb-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <p class="font-semibold text-neutral-800 text-sm">Belum Ada Aktivitas Gerbang</p>
            <p class="text-xs text-neutral-500 mt-1">Reader UHF RFID HW-VX6330 siap mendeteksi tag yang melintasi pintu lab.</p>
        </td>
    </tr>
@endforelse


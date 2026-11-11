<x-app-layout>
    <x-slot name="heading">Edit Tiket M&R #{{ $ticket->id }}</x-slot>
    <x-slot name="title">Edit Tiket #{{ $ticket->id }}</x-slot>

    <div class="max-w-2xl space-y-4">
        <div>
            <a href="{{ route('maintenance.show', $ticket->id) }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 px-3 py-1.5 rounded-lg border border-neutral-200 bg-white hover:bg-neutral-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Detail Tiket</span>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <h2 class="text-base font-bold text-neutral-900 tracking-tight mb-5 pb-3 border-b border-neutral-100">Edit Tiket <span class="font-mono">#{{ $ticket->id }}</span></h2>

            <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="issue_description" value="Deskripsi Kendala" />
                    <textarea id="issue_description" name="issue_description" rows="4"
                              class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">{{ old('issue_description', $ticket->issue_description) }}</textarea>
                    <x-input-error :messages="$errors->get('issue_description')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 w-full rounded-lg border-neutral-300 text-sm focus:border-primary focus:ring-primary">
                        <option value="open" @selected(old('status', $ticket->status->value) === 'open')>Open</option>
                        <option value="in_progress" @selected(old('status', $ticket->status->value) === 'in_progress')>In Progress</option>
                        <option value="resolved" @selected(old('status', $ticket->status->value) === 'resolved')>Resolved</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-neutral-100">
                    <a href="{{ route('maintenance.show', $ticket->id) }}" class="px-4 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 rounded-lg hover:bg-neutral-50 transition-colors">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-primary-700 text-white text-xs font-semibold rounded-lg border border-primary/40 transition-colors">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

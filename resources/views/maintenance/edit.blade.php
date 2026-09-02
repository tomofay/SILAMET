<x-app-layout>
    <div class="max-w-2xl bg-white rounded-lg shadow-sm border border-neutral-200 p-6">
        <h2 class="text-lg font-semibold text-neutral-800 mb-5">Edit Tiket #{{ $ticket->id }}</h2>

        <form method="POST" action="{{ route('maintenance.update', $ticket->id) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="issue_description" value="Deskripsi Kendala" />
                <textarea id="issue_description" name="issue_description" rows="4"
                          class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary">{{ old('issue_description', $ticket->issue_description) }}</textarea>
                <x-input-error :messages="$errors->get('issue_description')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="mt-1 w-full rounded-md border-neutral-300 shadow-sm focus:border-primary focus:ring-primary">
                    <option value="open" @selected(old('status', $ticket->status->value) === 'open')>Open</option>
                    <option value="in_progress" @selected(old('status', $ticket->status->value) === 'in_progress')>In Progress</option>
                    <option value="resolved" @selected(old('status', $ticket->status->value) === 'resolved')>Resolved</option>
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <a href="{{ route('maintenance.show', $ticket->id) }}" class="px-4 py-2 text-sm text-neutral-600 hover:text-neutral-900">Batal</a>
                <button type="submit" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-dark">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-app-layout>

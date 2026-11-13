<x-app-layout>
    <x-slot name="heading">Profil Pengguna</x-slot>
    <x-slot name="title">Pengaturan Profil</x-slot>

    <div class="max-w-3xl space-y-6">
        <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="rounded-2xl border border-rose-200 bg-white p-6 shadow-sm">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>

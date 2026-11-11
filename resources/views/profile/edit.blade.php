<x-app-layout>
    <x-slot name="heading">Profil Pengguna</x-slot>
    <x-slot name="title">Profil</x-slot>

    <div class="max-w-3xl space-y-4">
        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="bg-white rounded-xl border border-neutral-200 p-5 sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="bg-white rounded-xl border border-rose-200 p-5 sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>

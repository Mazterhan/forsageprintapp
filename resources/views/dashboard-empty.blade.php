<x-app-layout>
    @section('title', __('Аналітика'))

    <x-slot name="header">
        @include('dashboard.partials.header-tabs', ['activeTab' => null])
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-6 sm:px-8">
            <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500 shadow-sm">
                Для вашої ролі не налаштовано доступних розділів аналітики.
            </div>
        </div>
    </div>
</x-app-layout>

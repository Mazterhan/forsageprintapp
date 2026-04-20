<x-app-layout>
    @section('title', __('Редагування замовника'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Редагування замовника') }}
            </h2>
            <a href="{{ route('orders.clients.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                {{ __('Повернутись до замовників') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12 space-y-6">
            @if (session('status'))
                <div class="text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ __('Будь ласка, виправте помилки нижче.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('orders.clients.update', $client) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @include('clients.partials.form', ['client' => $client])

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Зберегти зміни
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

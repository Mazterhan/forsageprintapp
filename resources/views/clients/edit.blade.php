<x-app-layout>
    @section('title', __('Edit client'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit client') }}
            </h2>
            <a href="{{ route('orders.clients.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                {{ __('Back to clients') }}
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
                    {{ __('Please fix the errors below.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('orders.clients.update', $client) }}" class="space-y-6">
                @csrf
                @method('PATCH')

                @include('clients.partials.form', ['client' => $client])

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

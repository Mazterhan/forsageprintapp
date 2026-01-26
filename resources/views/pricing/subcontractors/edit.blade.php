<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Коригування інформації про підрядну організацію') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('pricing.subcontractors.update', $subcontractor) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')
                        @include('pricing.subcontractors.partials.form', ['subcontractor' => $subcontractor])
                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Зберегти') }}</x-primary-button>
                            <a href="{{ route('pricing.subcontractors.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Скинути') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

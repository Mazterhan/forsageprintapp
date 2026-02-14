<x-app-layout>
    @section('title', __('Тип виробу'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Тип виробу') }}
            </h2>
            <a href="{{ route('orders.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Повернутись до замовлень') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('orders.product-types.store') }}" id="product-types-form" class="space-y-4">
                        @csrf

                        <div id="type-fields" class="space-y-3">
                            @php
                                $oldTypes = old('types');
                                $initialTypes = is_array($oldTypes) ? $oldTypes : $types;
                            @endphp
                            @foreach ($initialTypes as $index => $type)
                                <div>
                                    <label class="block font-medium text-sm text-gray-700" for="type_{{ $index }}">
                                        {{ __('Тип виробу') }}
                                    </label>
                                    <input
                                        id="type_{{ $index }}"
                                        name="types[]"
                                        type="text"
                                        value="{{ $type }}"
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full product-type-input"
                                    >
                                </div>
                            @endforeach
                            <div>
                                <label class="block font-medium text-sm text-gray-700" for="type_new">
                                    {{ __('Тип виробу') }}
                                </label>
                                <input
                                    id="type_new"
                                    name="types[]"
                                    type="text"
                                    value=""
                                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full product-type-input"
                                >
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Зберегти зміни у типах виробів') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const container = document.getElementById('type-fields');
            if (!container) return;

            const buildField = () => {
                const index = container.querySelectorAll('.product-type-input').length;
                const wrapper = document.createElement('div');
                wrapper.innerHTML = `
                    <label class="block font-medium text-sm text-gray-700" for="type_${index}">Тип виробу</label>
                    <input
                        id="type_${index}"
                        name="types[]"
                        type="text"
                        value=""
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full product-type-input"
                    >
                `;
                return wrapper;
            };

            const ensureTrailingEmptyField = () => {
                const inputs = Array.from(container.querySelectorAll('.product-type-input'));
                if (inputs.length === 0) {
                    container.appendChild(buildField());
                    return;
                }
                const last = inputs[inputs.length - 1];
                if (last.value.trim() !== '') {
                    container.appendChild(buildField());
                }
            };

            container.addEventListener('input', (event) => {
                if (!(event.target instanceof HTMLInputElement)) return;
                if (!event.target.classList.contains('product-type-input')) return;
                ensureTrailingEmptyField();
            });

            ensureTrailingEmptyField();
        })();
    </script>
</x-app-layout>

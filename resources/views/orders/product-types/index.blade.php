<x-app-layout>
    @section('title', __('Тип виробу'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Тип виробу') }}
            </h2>
            <a href="{{ route('admin.editgroupsandcategories') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Повернутись до довідників, груп та категорій') }}
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
            @if ($errors->has('types'))
                <div class="mb-4 text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ $errors->first('types') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.product-types.store') }}" id="product-types-form" class="space-y-4">
                        @csrf

                        <div id="type-fields" class="space-y-3">
                            @php
                                $oldTypes = old('types');
                                $initialTypes = is_array($oldTypes) ? $oldTypes : $types;
                            @endphp
                            @foreach ($initialTypes as $index => $type)
                                <div class="entry-row">
                                    <label class="block font-medium text-sm text-gray-700" for="type_{{ $index }}">
                                        {{ __('Тип виробу') }}
                                    </label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input
                                            id="type_{{ $index }}"
                                            name="types[]"
                                            type="text"
                                            value="{{ $type }}"
                                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full product-type-input"
                                        >
                                        <button type="button" class="remove-entry inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                            {{ __('Удалить') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                            <div class="entry-row">
                                <label class="block font-medium text-sm text-gray-700" for="type_new">
                                    {{ __('Тип виробу') }}
                                </label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input
                                        id="type_new"
                                        name="types[]"
                                        type="text"
                                        value=""
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full product-type-input"
                                    >
                                    <button type="button" class="remove-entry inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                        {{ __('Удалить') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        @php
                            $oldMatrix = old('matrix', []);
                        @endphp
                        @if (!empty($types) && isset($categories) && $categories->count() > 0)
                            <div class="pt-4">
                                <div class="font-semibold text-gray-800 mb-2">
                                    {{ __('Матриця доступності типів виробу по категоріях товарів') }}
                                </div>
                                <div class="overflow-x-auto border border-gray-300 rounded-md">
                                    <table class="min-w-full border-collapse border border-gray-300">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider border border-gray-300">
                                                    {{ __('Категорія товарів') }}
                                                </th>
                                                @foreach ($types as $typeName)
                                                    <th class="px-3 py-2 text-center text-xs font-bold text-gray-800 border border-gray-300">
                                                        {{ $typeName }}
                                                    </th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white">
                                            @foreach ($categories as $category)
                                                <tr class="{{ $loop->odd ? '[background-color:#DDEBF7]' : 'bg-white' }}">
                                                    <td class="px-3 py-2 text-sm text-gray-900 whitespace-nowrap border border-gray-300">
                                                        {{ $category->name }}
                                                    </td>
                                                    @foreach ($types as $typeName)
                                                        @php
                                                            $key = $category->id . '|' . $typeName;
                                                            $checked = (string) data_get($oldMatrix, $category->id . '.' . $typeName, array_key_exists($key, $rules ?? []) ? (($rules[$key] ?? false) ? '1' : '0') : '0');
                                                        @endphp
                                                        <td class="px-3 py-2 text-center border border-gray-300">
                                                            <div class="inline-flex items-center gap-3">
                                                                <label class="inline-flex items-center gap-1 text-xs text-gray-700">
                                                                    <input
                                                                        type="radio"
                                                                        name="matrix[{{ $category->id }}][{{ $typeName }}]"
                                                                        value="1"
                                                                        {{ $checked === '1' ? 'checked' : '' }}
                                                                    >
                                                                    <span>{{ __('так') }}</span>
                                                                </label>
                                                                <label class="inline-flex items-center gap-1 text-xs text-gray-700">
                                                                    <input
                                                                        type="radio"
                                                                        name="matrix[{{ $category->id }}][{{ $typeName }}]"
                                                                        value="0"
                                                                        {{ $checked !== '1' ? 'checked' : '' }}
                                                                    >
                                                                    <span>{{ __('ні') }}</span>
                                                                </label>
                                                            </div>
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

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
                wrapper.className = 'entry-row';
                wrapper.innerHTML = `
                    <label class="block font-medium text-sm text-gray-700" for="type_${index}">Тип виробу</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input
                            id="type_${index}"
                            name="types[]"
                            type="text"
                            value=""
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full product-type-input"
                        >
                        <button type="button" class="remove-entry inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                            Удалить
                        </button>
                    </div>
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

            const syncRemoveButtons = () => {
                container.querySelectorAll('.entry-row').forEach((row) => {
                    const input = row.querySelector('.product-type-input');
                    const removeButton = row.querySelector('.remove-entry');
                    if (!input || !removeButton) return;
                    const filled = input.value.trim() !== '';
                    removeButton.classList.toggle('hidden', !filled);
                    removeButton.disabled = !filled;
                });
            };

            container.addEventListener('input', (event) => {
                if (!(event.target instanceof HTMLInputElement)) return;
                if (!event.target.classList.contains('product-type-input')) return;
                ensureTrailingEmptyField();
                syncRemoveButtons();
            });

            container.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) return;
                if (!target.classList.contains('remove-entry')) return;
                const row = target.closest('.entry-row');
                if (!row) return;
                row.remove();
                ensureTrailingEmptyField();
                syncRemoveButtons();
            });

            ensureTrailingEmptyField();
            syncRemoveButtons();
        })();
    </script>
</x-app-layout>

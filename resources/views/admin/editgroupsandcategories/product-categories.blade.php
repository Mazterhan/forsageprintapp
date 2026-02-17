<x-app-layout>
    @section('title', __('Категорії товарів'))
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Категорії товарів') }}
            </h2>
            <a href="{{ route('admin.editgroupsandcategories') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('Повернутись') }}
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
            @if ($errors->has('categories'))
                <div class="mb-4 text-sm font-bold text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ $errors->first('categories') }}
                </div>
            @endif
            <div id="material-type-client-error" class="hidden mb-4 text-sm font-bold text-red-700 bg-red-100 px-4 py-2 rounded"></div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.product-categories.store') }}" id="product-categories-form" class="space-y-4">
                        @csrf

                        <div id="category-fields" class="space-y-3">
                            @php
                                $oldCategories = old('categories');
                                $oldMaterialTypes = old('material_types');
                                $initialRows = is_array($oldCategories)
                                    ? collect($oldCategories)->map(function ($name, $index) use ($oldMaterialTypes) {
                                        return [
                                            'name' => $name,
                                            'material_type' => is_array($oldMaterialTypes) ? ($oldMaterialTypes[$index] ?? '') : '',
                                        ];
                                    })->all()
                                    : $categories;
                            @endphp
                            @foreach ($initialRows as $index => $row)
                                <div class="entry-row">
                                    <label class="block font-medium text-sm text-gray-700" for="category_{{ $index }}">{{ __('Категорія товарів') }}</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input
                                            id="category_{{ $index }}"
                                            name="categories[]"
                                            type="text"
                                            value="{{ $row['name'] ?? '' }}"
                                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full category-input"
                                        >
                                        <select name="material_types[]" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-[180px] material-type-select">
                                            <option value="">{{ __('Тип матеріалу') }}</option>
                                            @foreach ($materialTypeOptions as $option)
                                                <option value="{{ $option }}" @selected(($row['material_type'] ?? '') === $option)>{{ $option }}</option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="remove-entry inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                            {{ __('Удалить') }}
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                            <div class="entry-row">
                                <label class="block font-medium text-sm text-gray-700" for="category_new">{{ __('Категорія товарів') }}</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input
                                        id="category_new"
                                        name="categories[]"
                                        type="text"
                                        value=""
                                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full category-input"
                                    >
                                    <select name="material_types[]" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-[180px] material-type-select">
                                        <option value="">{{ __('Тип матеріалу') }}</option>
                                        @foreach ($materialTypeOptions as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="remove-entry inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                                        {{ __('Удалить') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('Зберегти зміни у категоріях товарів') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const container = document.getElementById('category-fields');
            const form = document.getElementById('product-categories-form');
            const clientError = document.getElementById('material-type-client-error');
            if (!container) return;

            const buildField = () => {
                const index = container.querySelectorAll('.category-input').length;
                const wrapper = document.createElement('div');
                wrapper.className = 'entry-row';
                wrapper.innerHTML = `
                    <label class="block font-medium text-sm text-gray-700" for="category_${index}">Категорія товарів</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input
                            id="category_${index}"
                            name="categories[]"
                            type="text"
                            value=""
                            class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-full category-input"
                        >
                        <select name="material_types[]" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block w-[180px] material-type-select">
                            <option value="">Тип матеріалу</option>
                            <option value="Листовий">Листовий</option>
                            <option value="Рулонний">Рулонний</option>
                            <option value="Без типу матеріалу">Без типу матеріалу</option>
                        </select>
                        <button type="button" class="remove-entry inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50 whitespace-nowrap">
                            Удалить
                        </button>
                    </div>
                `;
                return wrapper;
            };

            const ensureTrailingEmptyField = () => {
                const inputs = Array.from(container.querySelectorAll('.category-input'));
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
                    const input = row.querySelector('.category-input');
                    const removeButton = row.querySelector('.remove-entry');
                    if (!input || !removeButton) return;
                    const filled = input.value.trim() !== '';
                    removeButton.classList.toggle('hidden', !filled);
                    removeButton.disabled = !filled;
                });
            };

            container.addEventListener('input', (event) => {
                if (!(event.target instanceof HTMLInputElement)) return;
                if (!event.target.classList.contains('category-input')) return;
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

            form?.addEventListener('submit', (event) => {
                const rows = Array.from(container.querySelectorAll('.entry-row'));
                const hasMissingMaterialType = rows.some((row) => {
                    const categoryInput = row.querySelector('.category-input');
                    const materialTypeSelect = row.querySelector('.material-type-select');
                    if (!(categoryInput instanceof HTMLInputElement)) return false;
                    if (!(materialTypeSelect instanceof HTMLSelectElement)) return false;
                    if (categoryInput.value.trim() === '') return false;
                    return materialTypeSelect.value.trim() === '';
                });

                if (hasMissingMaterialType) {
                    event.preventDefault();
                    if (clientError) {
                        clientError.textContent = 'Оберіть "Тип матеріалу" для кожної категорії товарів.';
                        clientError.classList.remove('hidden');
                    }
                    return;
                }

                if (clientError) {
                    clientError.textContent = '';
                    clientError.classList.add('hidden');
                }
            });

            ensureTrailingEmptyField();
            syncRemoveButtons();
        })();
    </script>
</x-app-layout>

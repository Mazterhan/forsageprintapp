<x-app-layout>
    @section('title', __('Додати позицію'))
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Додати позицію') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-[1700px] mx-auto px-6 sm:px-8 lg:px-12 space-y-6">
            @if ($errors->any())
                <div class="text-sm text-red-700 bg-red-100 px-4 py-2 rounded">
                    {{ __('Перевірте коректність заповнення полів.') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('tariffs.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-8">
                                <x-input-label for="name" :value="__('Назва')" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required />
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="internal_code" :value="__('Внутрішній код')" />
                                <x-text-input id="internal_code" type="text" class="mt-1 block w-full" value="{{ $internalCode }}" disabled />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                            <div class="md:col-span-6">
                                <x-input-label for="category" :value="__('Категорія')" />
                                <input
                                    id="category"
                                    name="category"
                                    type="text"
                                    list="category-options"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    value="{{ old('category') }}"
                                    placeholder="{{ __('Оберіть категорію') }}"
                                    required
                                >
                                <datalist id="category-options">
                                    @foreach ($productCategories as $category)
                                        <option value="{{ $category }}"></option>
                                    @endforeach
                                </datalist>
                                <x-input-error class="mt-2" :messages="$errors->get('category')" />
                            </div>
                            <div class="md:col-span-6">
                                <x-input-label for="product_group_name" :value="__('Внутрішня назва товару (Група товарів)')" />
                                <input
                                    id="product_group_name"
                                    name="product_group_name"
                                    type="text"
                                    list="product-group-options"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                    value="{{ old('product_group_name') }}"
                                    placeholder="{{ __('Оберіть значення') }}"
                                >
                                <datalist id="product-group-options">
                                    @foreach ($productGroups as $group)
                                        <option value="{{ $group->name }}" data-id="{{ $group->id }}"></option>
                                    @endforeach
                                </datalist>
                                <input type="hidden" id="product_group_id" name="product_group_id" value="{{ old('product_group_id') }}">
                                <x-input-error class="mt-2" :messages="$errors->get('product_group_id')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            <div class="md:col-span-4">
                                <x-input-label for="sale_price" :value="__('Роздрібна ціна')" />
                                <x-text-input id="sale_price" name="sale_price" type="text" class="mt-1 block w-full" value="{{ old('sale_price') }}" required />
                                <x-input-error class="mt-2" :messages="$errors->get('sale_price')" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="wholesale_price" :value="__('Оптова ціна')" />
                                <x-text-input id="wholesale_price" name="wholesale_price" type="text" class="mt-1 block w-full" value="{{ old('wholesale_price') }}" required />
                                <x-input-error class="mt-2" :messages="$errors->get('wholesale_price')" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="urgent_price" :value="__('VIP ціна')" />
                                <x-text-input id="urgent_price" name="urgent_price" type="text" class="mt-1 block w-full" value="{{ old('urgent_price') }}" required />
                                <x-input-error class="mt-2" :messages="$errors->get('urgent_price')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            <div class="md:col-span-4">
                                <x-input-label for="roll_width_m" :value="__('Ширина (м)')" />
                                <x-text-input id="roll_width_m" name="roll_width_m" type="text" class="mt-1 block w-full" value="{{ old('roll_width_m') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('roll_width_m')" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="roll_length_m" :value="__('Довжина (м)')" />
                                <x-text-input id="roll_length_m" name="roll_length_m" type="text" class="mt-1 block w-full" value="{{ old('roll_length_m') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('roll_length_m')" />
                            </div>
                            <div class="md:col-span-4">
                                <x-input-label for="sheet_thickness_mm" :value="__('Товщина (мм)')" />
                                <x-text-input id="sheet_thickness_mm" name="sheet_thickness_mm" type="text" class="mt-1 block w-full" value="{{ old('sheet_thickness_mm') }}" />
                                <x-input-error class="mt-2" :messages="$errors->get('sheet_thickness_mm')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Зберегти') }}</x-primary-button>
                            <a href="{{ route('tariffs.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('повернутись') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const productGroupNameInput = document.getElementById('product_group_name');
            const productGroupIdInput = document.getElementById('product_group_id');
            const productGroupOptions = Array.from(document.querySelectorAll('#product-group-options option'));

            const syncProductGroupId = () => {
                if (!productGroupNameInput || !productGroupIdInput) {
                    return;
                }

                const selected = productGroupOptions.find((option) => option.value === productGroupNameInput.value);
                productGroupIdInput.value = selected?.dataset.id ?? '';
            };

            productGroupNameInput?.addEventListener('input', syncProductGroupId);
            productGroupNameInput?.addEventListener('change', syncProductGroupId);
            syncProductGroupId();
        })();
    </script>
</x-app-layout>

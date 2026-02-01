<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('Назва підрозділу')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $department->name) }}" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="lead_user_id" :value="__('Керівник підрозділу')" />
        <select id="lead_user_id" name="lead_user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            <option value="">{{ __('Оберіть користувача') }}</option>
            @foreach ($activeUsers as $user)
                <option value="{{ $user->id }}" @selected(old('lead_user_id', $department->lead_user_id) == $user->id)>
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('lead_user_id')" />
    </div>

    <div>
        <x-input-label for="category_input" :value="__('Категорія роботи підрозділу')" />
        <div class="mt-1 flex flex-wrap items-center gap-3">
            <x-text-input id="category_input" type="text" class="block w-full md:flex-1" placeholder="Введіть категорію" />
            <button type="button" id="add-category" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('додати категорію') }}
            </button>
        </div>
        <div class="mt-3 space-y-2" id="category-list">
            @foreach ($categories as $category)
                <div class="flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2">
                    <span class="text-sm text-gray-700">{{ $category->name }}</span>
                    <input type="hidden" name="categories[]" value="{{ $category->name }}">
                    <button type="button" class="text-sm text-gray-600 hover:text-gray-900 remove-item">
                        {{ __('видалити') }}
                    </button>
                </div>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('categories')" />
    </div>

    <div>
        <x-input-label for="position_input" :value="__('Позиції співробітників підрозділу')" />
        <div class="mt-1 flex flex-wrap items-center gap-3">
            <x-text-input id="position_input" type="text" class="block w-full md:flex-1" placeholder="Введіть посаду" />
            <select id="position_category_id" class="block w-full md:w-64 border-gray-300 rounded-md shadow-sm">
                <option value="">{{ __('Категорія роботи підрозділу') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <button type="button" id="add-position" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                {{ __('додати посаду') }}
            </button>
        </div>
        <div class="mt-3 space-y-2" id="position-list">
            @foreach ($positions as $position)
                @php
                    $isLocked = in_array($position->id, $lockedPositions ?? [], true);
                    $categoryName = $categories->firstWhere('id', $position->department_category_id)?->name;
                @endphp
                <div class="flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2">
                    <div class="flex flex-col">
                        <span class="text-sm text-gray-700">{{ $position->name }}</span>
                        @if ($categoryName)
                            <span class="text-xs text-gray-500">{{ $categoryName }}</span>
                        @endif
                    </div>
                    <input type="hidden" name="positions_name[]" value="{{ $position->name }}">
                    <input type="hidden" name="positions_category_id[]" value="{{ $position->department_category_id }}">
                    <button type="button" class="text-sm {{ $isLocked ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:text-gray-900 remove-item' }}" {{ $isLocked ? 'disabled' : '' }}>
                        {{ __('Видалити') }}
                    </button>
                </div>
            @endforeach
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('positions_name')" />
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ __('Зберегти') }}</x-primary-button>
        <a href="{{ route('admin.departments.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
            {{ __('Повернутись') }}
        </a>
    </div>
</div>

<script>
    (function () {
        const addItem = (input, list, label) => {
            const value = input.value.trim();
            if (!value) {
                return;
            }

            const exists = Array.from(list.querySelectorAll('input[type="hidden"]'))
                .some((el) => el.value.toLowerCase() === value.toLowerCase());
            if (exists) {
                input.value = '';
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2';

            const text = document.createElement('span');
            text.className = 'text-sm text-gray-700';
            text.textContent = value;

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = label;
            hidden.value = value;

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'text-sm text-gray-600 hover:text-gray-900 remove-item';
            remove.textContent = 'видалити';

            wrapper.appendChild(text);
            wrapper.appendChild(hidden);
            wrapper.appendChild(remove);
            list.appendChild(wrapper);

            input.value = '';
        };

        document.getElementById('add-category')?.addEventListener('click', () => {
            addItem(
                document.getElementById('category_input'),
                document.getElementById('category-list'),
                'categories[]'
            );
        });

        document.getElementById('add-position')?.addEventListener('click', () => {
            const nameInput = document.getElementById('position_input');
            const categorySelect = document.getElementById('position_category_id');
            const list = document.getElementById('position-list');
            const value = nameInput.value.trim();
            if (!value) {
                return;
            }

            const exists = Array.from(list.querySelectorAll('input[name="positions_name[]"]'))
                .some((el) => el.value.toLowerCase() === value.toLowerCase());
            if (exists) {
                nameInput.value = '';
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2';

            const textWrap = document.createElement('div');
            textWrap.className = 'flex flex-col';
            const text = document.createElement('span');
            text.className = 'text-sm text-gray-700';
            text.textContent = value;
            textWrap.appendChild(text);

            const categoryText = categorySelect?.options[categorySelect.selectedIndex]?.textContent?.trim();
            if (categoryText) {
                const catLabel = document.createElement('span');
                catLabel.className = 'text-xs text-gray-500';
                catLabel.textContent = categoryText;
                textWrap.appendChild(catLabel);
            }

            const hiddenName = document.createElement('input');
            hiddenName.type = 'hidden';
            hiddenName.name = 'positions_name[]';
            hiddenName.value = value;

            const hiddenCategory = document.createElement('input');
            hiddenCategory.type = 'hidden';
            hiddenCategory.name = 'positions_category_id[]';
            hiddenCategory.value = categorySelect?.value || '';

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'text-sm text-gray-600 hover:text-gray-900 remove-item';
            remove.textContent = 'Видалити';

            wrapper.appendChild(textWrap);
            wrapper.appendChild(hiddenName);
            wrapper.appendChild(hiddenCategory);
            wrapper.appendChild(remove);
            list.appendChild(wrapper);

            nameInput.value = '';
        });

        document.addEventListener('click', (event) => {
            if (event.target && event.target.classList.contains('remove-item')) {
                event.target.closest('div')?.remove();
            }
        });
    })();
</script>

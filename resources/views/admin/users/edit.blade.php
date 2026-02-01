<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Редагування користувача') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $user->name) }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $user->email) }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <x-input-label for="role" :value="__('Role')" />
                            <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="admin" @selected(old('role', $user->role) === 'admin')>admin</option>
                                <option value="manager" @selected(old('role', $user->role) === 'manager')>manager</option>
                                <option value="user" @selected(old('role', $user->role) === 'user')>user</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('role')" />
                        </div>

                        <div>
                            <x-input-label for="department_id" :value="__('Назва підрозділу')" />
                            <select id="department_id" name="department_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('Оберіть підрозділ') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id', $user->department_id) == $department->id)>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('department_id')" />
                        </div>

                        <div>
                            <x-input-label for="department_category_id" :value="__('Категорія роботи підрозділу')" />
                            <select id="department_category_id" name="department_category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('Оберіть категорію') }}</option>
                                @foreach ($departments as $department)
                                    @foreach ($department->categories as $category)
                                        <option value="{{ $category->id }}"
                                            data-department-id="{{ $department->id }}"
                                            @selected(old('department_category_id', $user->department_category_id) == $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('department_category_id')" />
                        </div>

                        <div>
                            <x-input-label for="department_position_id" :value="__('Позиції/посади співробітників підрозділу')" />
                            <select id="department_position_id" name="department_position_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('Оберіть посаду') }}</option>
                                @foreach ($departments as $department)
                                    @foreach ($department->positions as $position)
                                        <option value="{{ $position->id }}"
                                            data-department-id="{{ $department->id }}"
                                            @selected(old('department_position_id', $user->department_position_id) == $position->id)>
                                            {{ $position->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('department_position_id')" />
                        </div>

                        <div>
                            <x-input-label for="is_active" :value="__('Status')" />
                            <select id="is_active" name="is_active" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="1" @selected(old('is_active', $user->is_active) == 1)>{{ __('Active') }}</option>
                                <option value="0" @selected(old('is_active', $user->is_active) == 0)>{{ __('Inactive') }}</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('is_active')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('SAVE') }}</x-primary-button>
                            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const deptSelect = document.getElementById('department_id');
            const categorySelect = document.getElementById('department_category_id');
            const positionSelect = document.getElementById('department_position_id');

            const filterCategories = () => {
                const deptId = deptSelect.value;
                Array.from(categorySelect.options).forEach((opt) => {
                    if (!opt.value) return;
                    const match = opt.dataset.departmentId === deptId;
                    opt.hidden = !match;
                    opt.disabled = !match;
                });
                if (categorySelect.selectedOptions.length && categorySelect.selectedOptions[0].disabled) {
                    categorySelect.value = '';
                }
            };

            const filterPositions = () => {
                const deptId = deptSelect.value;
                Array.from(positionSelect.options).forEach((opt) => {
                    if (!opt.value) return;
                    const match = opt.dataset.departmentId === deptId;
                    opt.hidden = !match;
                    opt.disabled = !match;
                });
                if (positionSelect.selectedOptions.length && positionSelect.selectedOptions[0].disabled) {
                    positionSelect.value = '';
                }
            };

            deptSelect.addEventListener('change', () => {
                filterCategories();
                filterPositions();
            });

            filterCategories();
            filterPositions();
        })();
    </script>
</x-app-layout>

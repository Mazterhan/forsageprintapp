<x-app-layout>
    @section('title', __('Створити Користувача'))
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Створити Користувача') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Ім\'я користувача')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') }}" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <x-input-label for="role" :value="__('Роль користувача')" />
                            <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="admin" @selected(old('role') === 'admin')>admin</option>
                                <option value="manager" @selected(old('role') === 'manager')>manager</option>
                                <option value="user" @selected(old('role') === 'user')>user</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('role')" />
                        </div>

                        <div>
                            <x-input-label for="department_id" :value="__('Назва підрозділу')" />
                            <select id="department_id" name="department_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">{{ __('Оберіть підрозділ') }}</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
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
                                            @selected(old('department_category_id') == $category->id)>
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
                                            @selected(old('department_position_id') == $position->id)>
                                            {{ $position->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('department_position_id')" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Пароль')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" :value="__('Підтвердіть пароль')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Створити') }}</x-primary-button>
                            <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                                {{ __('Відмінити') }}
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

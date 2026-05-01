<x-app-layout>
    @section('title', __('Редагування користувача'))
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Редагування користувача') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 text-sm text-green-700 bg-green-100 px-4 py-2 rounded">
                    {{ session('status') }}
                </div>
            @endif
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
                            <x-input-label for="role" :value="__('Роль користувача')" />
                            <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="admin" @selected(old('role', $user->role) === 'admin')>admin</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->slug }}" @selected(old('role', $user->role) === $role->slug)>{{ $role->name }}</option>
                                @endforeach
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

            <div class="mt-6 flex items-center gap-3">
                <input id="toggle-reset-password-enable" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                <button id="toggle-reset-password-button" type="button" disabled class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-sm text-white disabled:opacity-50 disabled:cursor-not-allowed">
                    {{ __('Скидання пароля') }}
                </button>
            </div>

            <div id="reset-password-section" class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg {{ $errors->resetUserPassword->isNotEmpty() ? '' : 'hidden' }}">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Скидання пароля') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Вкажіть новий пароль для користувача.') }}</p>

                    <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="mt-6 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="reset_reason" :value="__('Причина скидання паролю')" />
                            <textarea
                                id="reset_reason"
                                name="reset_reason"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm resize-y"
                            >{{ old('reset_reason') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->resetUserPassword->get('reset_reason')" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Новий пароль')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <x-input-error class="mt-2" :messages="$errors->resetUserPassword->get('password')" />
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" :value="__('Підтвердження пароля')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                            <x-input-error class="mt-2" :messages="$errors->resetUserPassword->get('password_confirmation')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Скинути пароль') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Історія скидання пароля') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead style="background-color: #FCEEDF;">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">№</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Дата</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Ким скинуто пароль</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Причина</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse(($passwordResetLogs ?? collect()) as $index => $log)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-700">{{ $index + 1 }}</td>
                                        <td class="px-4 py-2 text-gray-700">{{ optional($log->created_at)->timezone('Europe/Kiev')->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-2 text-gray-700">{{ $log->resetBy?->name ?? '—' }}</td>
                                        <td class="px-4 py-2 text-gray-700">{{ $log->reason }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                            {{ __('Історія скидання пароля порожня.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const deptSelect = document.getElementById('department_id');
            const categorySelect = document.getElementById('department_category_id');
            const positionSelect = document.getElementById('department_position_id');
            const resetToggleCheckbox = document.getElementById('toggle-reset-password-enable');
            const resetToggleButton = document.getElementById('toggle-reset-password-button');
            const resetSection = document.getElementById('reset-password-section');

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

            if (resetToggleCheckbox && resetToggleButton && resetSection) {
                const syncResetButtonState = () => {
                    resetToggleButton.disabled = !resetToggleCheckbox.checked;
                };

                resetToggleCheckbox.addEventListener('change', syncResetButtonState);
                resetToggleButton.addEventListener('click', () => {
                    resetSection.classList.toggle('hidden');
                });

                syncResetButtonState();
            }

            filterCategories();
            filterPositions();
        })();
    </script>
</x-app-layout>

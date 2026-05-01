<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Для продовження роботи потрібно встановити новий пароль відповідно до політики надійності.') }}
    </div>

    <form method="POST" action="{{ route('password.force.update') }}">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" :value="__('Новий пароль')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->forcePasswordChange->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Підтвердження пароля')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->forcePasswordChange->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-4 text-xs text-gray-600 leading-relaxed">
            {{ __('Пароль має містити щонайменше 12 символів, велику і малу латинські літери, цифру та спеціальний символ.') }}
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Зберегти новий пароль') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

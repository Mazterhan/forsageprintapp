<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('Назва')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $subcontractor?->name) }}" required />
        <x-input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div>
        <x-input-label for="address" :value="__('Адреса')" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" value="{{ old('address', $subcontractor?->address) }}" />
        <x-input-error class="mt-2" :messages="$errors->get('address')" />
    </div>

    <div>
        <x-input-label for="category" :value="__('Категорія')" />
        <x-text-input id="category" name="category" type="text" class="mt-1 block w-full" value="{{ old('category', $subcontractor?->category) }}" />
        <x-input-error class="mt-2" :messages="$errors->get('category')" />
    </div>
</div>

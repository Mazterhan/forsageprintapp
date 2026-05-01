<?php

namespace App\Http\Requests\Admin;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'role' => ['required', 'string', $this->roleRule()],
            'is_active' => ['required', 'boolean'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'department_category_id' => ['nullable', 'integer', 'exists:department_categories,id'],
            'department_position_id' => ['nullable', 'integer', 'exists:department_positions,id'],
        ];
    }

    private function roleRule(): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === 'admin' || Role::query()->where('slug', (string) $value)->exists()) {
                return;
            }

            $fail('Оберіть коректну роль користувача.');
        };
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed'],
            'role' => ['required', 'string', $this->roleRule()],
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

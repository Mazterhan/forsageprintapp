<?php

namespace App\Http\Requests\Admin;

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
            'role' => ['required', 'in:admin,manager,user'],
            'is_active' => ['required', 'boolean'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'department_category_id' => ['nullable', 'integer', 'exists:department_categories,id'],
            'department_position_id' => ['nullable', 'integer', 'exists:department_positions,id'],
        ];
    }
}

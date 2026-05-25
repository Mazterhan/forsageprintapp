<?php

namespace Tests\Support;

use App\Models\Role;
use App\Models\User;

trait CreatesRoles
{
    protected function createAdminUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'is_active' => true,
            'force_password_change' => false,
        ], $attributes));
    }

    protected function createRole(array $permissions = [], array $attributes = []): Role
    {
        return Role::factory()
            ->withPermissions($permissions)
            ->create($attributes);
    }

    protected function createUserWithRole(array $permissions = [], array $userAttributes = [], array $roleAttributes = []): User
    {
        $role = $this->createRole($permissions, $roleAttributes);

        return User::factory()->create(array_merge([
            'role' => $role->slug,
            'is_active' => true,
            'force_password_change' => false,
        ], $userAttributes));
    }

    protected function createFullAccessRole(array $attributes = []): Role
    {
        return Role::factory()
            ->fullAccess()
            ->create($attributes);
    }

    protected function createFullAccessUser(array $userAttributes = [], array $roleAttributes = []): User
    {
        $role = $this->createFullAccessRole($roleAttributes);

        return User::factory()->create(array_merge([
            'role' => $role->slug,
            'is_active' => true,
            'force_password_change' => false,
        ], $userAttributes));
    }
}

<?php

namespace App\Rules;

use App\Models\PasswordBlocklist;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongUserPassword implements ValidationRule
{
    public function __construct(
        private readonly ?User $user = null,
        private readonly ?string $name = null,
        private readonly ?string $email = null,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;
        $lowerPassword = mb_strtolower($password, 'UTF-8');

        if (mb_strlen($password, 'UTF-8') < 12) {
            $fail('Пароль має містити щонайменше 12 символів.');
            return;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $fail('Пароль має містити щонайменше одну велику літеру A-Z.');
            return;
        }

        if (!preg_match('/[a-z]/', $password)) {
            $fail('Пароль має містити щонайменше одну малу літеру a-z.');
            return;
        }

        if (!preg_match('/[0-9]/', $password)) {
            $fail('Пароль має містити щонайменше одну цифру.');
            return;
        }

        if (!preg_match('/[!@#$%^&*_\-+=?]/', $password)) {
            $fail('Пароль має містити щонайменше один спеціальний символ: ! @ # $ % ^ & * _ - + = ?.');
            return;
        }

        if ($this->isBlocked($lowerPassword)) {
            $fail('Пароль занадто простий або поширений. Оберіть інший пароль.');
            return;
        }

        if ($this->containsSequence($lowerPassword)) {
            $fail('Пароль не повинен містити очевидні послідовності символів.');
            return;
        }

        if ($this->containsUserDataPart($lowerPassword)) {
            $fail('Пароль не повинен збігатися з іменем користувача, email або частиною цих даних.');
        }
    }

    private function isBlocked(string $lowerPassword): bool
    {
        return PasswordBlocklist::query()
            ->whereRaw('LOWER(password) = ?', [$lowerPassword])
            ->exists();
    }

    private function containsSequence(string $lowerPassword): bool
    {
        $sequences = [
            '123456',
            '234567',
            '345678',
            '456789',
            'abcdef',
            'bcdefg',
            'qwerty',
            'asdfgh',
            'zxcvbn',
        ];

        foreach ($sequences as $sequence) {
            if (str_contains($lowerPassword, $sequence)) {
                return true;
            }
        }

        return false;
    }

    private function containsUserDataPart(string $lowerPassword): bool
    {
        foreach ($this->userDataParts() as $part) {
            if (mb_strlen($part, 'UTF-8') >= 4 && str_contains($lowerPassword, $part)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function userDataParts(): array
    {
        $name = (string) ($this->name ?? $this->user?->name ?? '');
        $email = (string) ($this->email ?? $this->user?->email ?? '');
        $values = [$name, $email];

        if (str_contains($email, '@')) {
            [$localPart, $domain] = explode('@', $email, 2);
            $values[] = $localPart;
            $values[] = preg_replace('/\.[^.]+$/', '', $domain) ?: $domain;
        }

        $parts = [];
        foreach ($values as $value) {
            $normalized = mb_strtolower(trim($value), 'UTF-8');
            if ($normalized === '') {
                continue;
            }

            $parts[] = $normalized;
            foreach (preg_split('/[^a-z0-9а-яіїєґ]+/iu', $normalized) ?: [] as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $parts[] = $piece;
                }
            }
        }

        return array_values(array_unique($parts));
    }
}

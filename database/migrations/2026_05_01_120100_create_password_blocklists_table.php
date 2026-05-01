<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_blocklists', function (Blueprint $table): void {
            $table->id();
            $table->string('password')->unique();
            $table->timestamps();
        });

        $now = now();
        $passwords = [
            '123456',
            '1234567',
            '12345678',
            '123456789',
            '1234567890',
            '111111',
            '000000',
            'password',
            'password1',
            'qwerty',
            'qwerty123',
            'admin',
            'admin123',
            'abcdef',
            'abc123',
            'letmein',
            'welcome',
            'iloveyou',
        ];

        DB::table('password_blocklists')->insert(array_map(static fn (string $password): array => [
            'password' => $password,
            'created_at' => $now,
            'updated_at' => $now,
        ], $passwords));
    }

    public function down(): void
    {
        Schema::dropIfExists('password_blocklists');
    }
};

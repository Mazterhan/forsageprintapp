<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'admin@example.com')
            ->update([
                'password' => '$2y$12$L9exITyhrIrfD.NnvWjY0OhaO9NQNbsDdqeO1FiqL6qkZKvuzySkS',
                'force_password_change' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Password hashes cannot be safely restored without knowing the previous value.
    }
};

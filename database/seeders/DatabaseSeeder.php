<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Every demo page sits behind auth, so without a seeded login the app is
     * unreachable. Created pre-verified: the User model implements
     * MustVerifyEmail, and we want logging in to stay one click. The
     * verification flow is still exercisable via /register (decision #9).
     *
     * Phase 9 adds the ~5 further users the chat demo needs.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => 'password',   // 'hashed' cast handles this
                'email_verified_at' => now(),
            ]
        );

        $this->call(ChatSeeder::class);
    }
}

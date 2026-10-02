<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Signed, tamper-proof token identifying a demo user, used by the side-by-side
 * view so two panes in one browser can act as two different people without
 * separate sessions. Scoped to seeded demo accounts only (the public login set),
 * so a token grants nothing you could not already do by signing in.
 */
class DemoToken
{
    public static function issue(User $user): string
    {
        return Crypt::encryptString('demo:'.$user->id);
    }

    public static function resolve(?string $token): ?User
    {
        if (! $token) {
            return null;
        }
        try {
            $raw = Crypt::decryptString($token);
        } catch (Throwable) {
            return null;
        }
        if (! str_starts_with($raw, 'demo:')) {
            return null;
        }

        $user = User::find((int) substr($raw, 5));
        if (! $user) {
            return null;
        }

        // Demo users only: a seeded @example.com human, never the assistant bot.
        if (! str_ends_with($user->email, '@example.com') || $user->email === config('llm.assistant_email')) {
            return null;
        }

        return $user;
    }
}

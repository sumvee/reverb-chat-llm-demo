<?php

use App\Http\Controllers\ChatController;
use App\Models\User;
use App\Support\DemoToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
| Focused WebSockets demo: a chat page behind a simple login, a public landing,
| and a side-by-side view that shows two people chatting in one window.
*/

// Login + logout only. Registration, password reset and email verification are
// not part of this demo; users are seeded (see ChatSeeder).
Auth::routes(['register' => false, 'reset' => false, 'verify' => false]);

// Public landing: a high-level overview of the app's design.
Route::view('/', 'home')->name('home');

// Side-by-side demo: two people in one window, no incognito needed. Picks two
// seeded demo users and hands each pane a signed token so it can act as that
// user (see App\Support\DemoToken). Public, like the landing.
Route::get('/split', function (Request $request) {
    $pick = function (?string $email, string $fallback) {
        $u = $email ? User::where('email', $email)->first() : null;
        $ok = $u && str_ends_with($u->email, '@example.com') && $u->email !== config('llm.assistant_email');
        return $ok ? $u : User::where('email', $fallback)->firstOrFail();
    };
    $left = $pick($request->query('left'), 'demo@example.com');
    $right = $pick($request->query('right'), 'adison@example.com');

    return view('split', [
        'left' => ['name' => $left->name, 'id' => $left->id, 'token' => DemoToken::issue($left)],
        'right' => ['name' => $right->name, 'id' => $right->id, 'token' => DemoToken::issue($right)],
    ]);
})->name('split');

// Chat with the AI, no sign-in needed. Picks a random seeded person (to spread
// visitors across the six bot threads) and opens their conversation with the bot.
Route::get('/ai', function () {
    $me = User::where('email', 'like', '%@example.com')
        ->where('email', '!=', config('llm.assistant_email'))
        ->inRandomOrder()->firstOrFail();
    $bot = User::where('email', config('llm.assistant_email'))->firstOrFail();

    return view('ai', [
        'me' => ['name' => $me->name, 'token' => DemoToken::issue($me)],
        'bot' => ['id' => $bot->id, 'name' => $bot->name],
    ]);
})->name('ai');

// Broadcasting auth for the side-by-side panes: the acting user comes from a
// demo token, so each pane authorizes channels as its own user.
Route::post('/broadcasting/auth-demo', fn (Request $r) => Broadcast::auth($r))->middleware('chat.auth');

// The chat page and its JSON backend. A split pane acts as its demo-token user;
// with no token, normal session auth applies (see ChatAuthenticate).
Route::middleware('chat.auth')->group(function () {
    Route::view('/chat', 'chat')->name('chat');

    Route::get('/chat/conversations', [ChatController::class, 'conversations'])->name('chat.conversations');
    Route::get('/chat/{conversation}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/{conversation}/send', [ChatController::class, 'send'])->name('chat.send');
});

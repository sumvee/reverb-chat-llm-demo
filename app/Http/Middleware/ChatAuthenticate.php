<?php

namespace App\Http\Middleware;

use App\Support\DemoToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Auth gate for the chat routes and the side-by-side broadcasting auth.
 *
 * A valid demo token (header or input) identifies the acting user and wins, even
 * over a session, so the two panes of the side-by-side view each act as their own
 * person. With no token, normal session auth applies. If neither yields a user,
 * reject (401 for JSON, redirect to login otherwise).
 *
 * This is a single gate on purpose: splitting it into "set user from token" plus
 * the framework's `auth` middleware let Laravel's middleware priority run `auth`
 * first, which 401s a guest before the token can set the user.
 */
class ChatAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-Demo-Token') ?: $request->input('demo_token');

        if ($token && ($user = DemoToken::resolve($token))) {
            Auth::setUser($user);
        }

        if (! Auth::check()) {
            if ($request->expectsJson() || $request->ajax()) {
                abort(401);
            }

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}

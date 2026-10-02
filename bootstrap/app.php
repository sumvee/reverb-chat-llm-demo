<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // On the VPS the app runs behind host apache, which terminates TLS and
        // forwards X-Forwarded-Proto/For. Trust it so the app knows requests are
        // HTTPS and generates secure URLs, redirects and cookies. Only apache (on
        // 127.0.0.1) can reach the container port, so trusting all proxies is safe
        // here; on localhost there is no proxy, so this is a no-op.
        $middleware->trustProxies(at: '*');

        // Chat auth gate: a signed demo token (side-by-side view) or the session.
        $middleware->alias(['chat.auth' => \App\Http\Middleware\ChatAuthenticate::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

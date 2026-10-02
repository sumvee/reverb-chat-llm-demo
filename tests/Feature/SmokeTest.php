<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Route smoke tests for the focused demo. No browser, CI-safe. */
class SmokeTest extends TestCase
{
    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_chat_requires_auth(): void
    {
        $this->get('/chat')->assertRedirect('/login');
    }

    public function test_home_landing_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('WebSockets');
    }
}

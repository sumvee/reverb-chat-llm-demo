@extends('layouts.base')

@section('title', 'Sign in')

@section('content')
<div class="auth">
    <form class="auth__card" method="POST" action="{{ route('login') }}">
        @csrf
        <div class="auth__brand">Live <b>WebSockets</b> Demo</div>
        <div class="auth__sub">Real-time chat and a streaming local LLM, over Laravel Reverb.</div>

        @if ($errors->any())
            <div class="auth__err">Those credentials do not match our records.</div>
        @endif

        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>

        <button class="auth__btn" type="submit">Sign in</button>

        <div class="auth__logins">
            Demo logins (password <code>password</code>):<br>
            User 1 <code>demo@example.com</code> &middot; User 2 <code>adison@example.com</code>
        </div>
    </form>
</div>
@endsection

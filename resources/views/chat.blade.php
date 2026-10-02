@extends('layouts.base')

@section('title', 'Live WebSockets Demo')

@section('content')
<div class="app">
    <header class="topbar">
        <div class="brand">Live <b>WebSockets</b> Demo</div>
        <div class="status">
            <span class="ws-dot" data-ws-status></span>
            <span data-ws-label>connecting...</span>
        </div>
        <div class="spacer"></div>
        <div class="me">
            <span class="name">Signed in as <b>{{ auth()->user()->name }}</b></span>
            <form class="logout" method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        </div>
    </header>

    <main class="chat" data-chat-widget>
        <aside class="convs">
            <div class="convs__head">Chats <span class="pill" data-chat-presence>...</span></div>
            <div class="convs__list" data-chat-conversations></div>
        </aside>
        <section class="thread">
            <div class="thread__head" data-chat-title>Select a chat</div>
            <div class="thread__msgs" data-chat-messages></div>
            <form class="composer" data-chat-form>
                <input class="composer__input" type="text" placeholder="Write a message..." autocomplete="off" data-chat-input>
                <button class="composer__send" type="submit">Send</button>
            </form>
        </section>
    </main>
</div>
@endsection

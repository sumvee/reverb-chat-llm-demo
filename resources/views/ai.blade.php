@extends('layouts.base')

@section('title', 'Chat with the AI - Live WebSockets Demo')

@section('content')
<div class="split">
    <header class="split-top">
        <a class="split-back" href="{{ url('/') }}">Back</a>
        <div class="brand">Live <b>WebSockets</b> Demo <span class="split-sub">chat with the AI</span></div>
        <div class="split-note">A small model (qwen2.5:0.5b) on the server streams its reply token by token. You are {{ $me['name'] }}.</div>
    </header>

    <div class="ai-wrap">
        <section class="split-pane ai-pane">
            <div class="split-pane-head">
                <span class="split-who">{{ $bot['name'] }}</span>
                <span class="status"><span class="ws-dot" data-ws-status></span><span data-ws-label>connecting...</span></span>
            </div>
            <main class="chat chat--split" data-chat-widget
                  data-demo-token="{{ $me['token'] }}"
                  data-open-with="{{ $bot['id'] }}">
                <section class="thread">
                    <div class="thread__msgs" data-chat-messages></div>
                    <form class="composer" data-chat-form>
                        <input class="composer__input" type="text" autocomplete="off"
                               placeholder="Ask the AI anything..." data-chat-input>
                        <button class="composer__send" type="submit">Send</button>
                    </form>
                </section>
            </main>
        </section>
    </div>
</div>
@endsection

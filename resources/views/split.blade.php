@extends('layouts.base')

@section('title', 'Side by side - Live WebSockets Demo')

@section('content')
<div class="split">
    <header class="split-top">
        <a class="split-back" href="{{ url('/') }}">Back</a>
        <div class="brand">Live <b>WebSockets</b> Demo <span class="split-sub">side by side</span></div>
        <div class="split-note">Two people, one window. Type in either pane and watch it arrive in the other, live.</div>
    </header>

    <div class="split-panes">
        @foreach ([['me' => $left, 'other' => $right], ['me' => $right, 'other' => $left]] as $pane)
            <section class="split-pane">
                <div class="split-pane-head">
                    <span class="split-who">{{ $pane['me']['name'] }}</span>
                    <span class="status"><span class="ws-dot" data-ws-status></span><span data-ws-label>connecting...</span></span>
                </div>
                <main class="chat chat--split" data-chat-widget
                      data-demo-token="{{ $pane['me']['token'] }}"
                      data-open-with="{{ $pane['other']['id'] }}">
                    <section class="thread">
                        <div class="thread__msgs" data-chat-messages></div>
                        <form class="composer" data-chat-form>
                            <input class="composer__input" type="text" autocomplete="off"
                                   placeholder="Message as {{ $pane['me']['name'] }}..." data-chat-input>
                            <button class="composer__send" type="submit">Send</button>
                        </form>
                    </section>
                </main>
            </section>
        @endforeach
    </div>
</div>
@endsection

@extends('layouts.base')

@section('title', 'Live WebSockets Demo')

@php($go = auth()->check() ? url('/chat') : route('login'))
@php($goLabel = auth()->check() ? 'Open the chat' : 'Sign in for the full app')

@section('content')
<div class="lp">
    <header class="lp-top">
        <div class="brand">Live <b>WebSockets</b> Demo</div>
        <div class="lp-nav-links">
            <a class="lp-cta-sm" href="{{ url('/split') }}">Side by side</a>
            <a class="lp-cta-sm" href="{{ url('/ai') }}">Chat with AI</a>
            <a class="lp-cta-sm" href="{{ $go }}">{{ $goLabel }}</a>
        </div>
    </header>

    <section class="lp-hero">
        <h1>Real-time chat and a streaming local LLM, over WebSockets.</h1>
        <p>Messages appear the instant they are sent, presence shows who is online, and a small language model running on the server streams its replies into the chat token by token. Built on Laravel 12 and Reverb.</p>
        <div class="lp-ctas">
            <a class="lp-cta" href="{{ url('/split') }}">See two people live</a>
            <a class="lp-cta" href="{{ url('/ai') }}">Chat with the AI</a>
            <a class="lp-cta lp-cta--ghost" href="{{ $go }}">{{ $goLabel }}</a>
        </div>
        <p class="lp-cta-note">The first two need no sign-in. Sign in for the full app: pick who you are and message anyone, including the AI.</p>
    </section>

    <section class="lp-section">
        <h2>Try it live, in two windows</h2>
        <p class="lp-note">The chat is multi-user, so it is best seen with two people signed in at once.</p>
        <div class="lp-try">
            <ol class="lp-steps">
                <li><a href="{{ $go }}">Open the chat</a> and sign in as <b>Demo User</b>.</li>
                <li>Open a <b>private / incognito window</b> (or a different browser) and sign in as one of the people on the right.<br>
                    <span class="hint">A normal second window shares your session, so the second person needs a separate private window.</span></li>
                <li>In both windows, open the conversation with the other person, send a message, and watch it arrive <b>instantly</b>, with no refresh.</li>
                <li>Or open the <b>AI Chatbot</b> contact and watch a local model stream its reply token by token.</li>
            </ol>
            <div class="lp-logins">
                <div class="lp-logins-head"><span>Sign-in</span><span>password: <code>password</code></span></div>
                <ul>
                    <li><b>Demo User</b> <code>demo@example.com</code> <span class="tag">talks to everyone</span></li>
                    <li><b>Adison Lee</b> <code>adison@example.com</code></li>
                    <li><b>Lisa Lamar</b> <code>lisa@example.com</code></li>
                    <li><b>Troy Norman</b> <code>troy@example.com</code></li>
                    <li><b>Sadi Orlaf</b> <code>sadi@example.com</code></li>
                    <li><b>Amanda Yang</b> <code>amanda@example.com</code></li>
                </ul>
                <p class="lp-logins-note">Six people can be signed in at once, one per window. Pair <b>Demo User</b> with anyone else (Demo has a conversation with each). The <b>AI Chatbot</b> is a bot you message, not a login.</p>
            </div>
        </div>
        <p class="lp-note" style="margin-top:18px">No second window to spare? <a href="{{ url('/split') }}">Open the side-by-side demo</a> to watch two people chat in a single window.</p>
    </section>

    <section class="lp-section">
        <h2>How it is built</h2>
        <p class="lp-note">Solid arrows are HTTP; dotted arrows are the WebSocket and real-time paths.</p>
        <div class="lp-diagram-card">
<pre class="mermaid">
flowchart LR
  B["Your browser"]
  A["apache TLS proxy"]
  subgraph net["Docker compose network"]
    N["nginx"]
    L["php-fpm Laravel"]
    DB[("MySQL")]
    W["queue worker"]
    O["Ollama local LLM"]
    R(["Laravel Reverb"])
  end
  B -->|HTTPS and WSS| A
  A -->|HTTP| N
  A -.->|app websocket| R
  N --> L
  L --> DB
  L -->|queue bot reply| W
  W -->|generate| O
  W -.->|stream tokens| R
  L -.->|broadcast| R
  R -.->|live events| A
  classDef hub fill:#eef0ff,stroke:#4f46e5,color:#1c1f26,stroke-width:2px;
  class R,O hub;
</pre>
        </div>
    </section>

    <section class="lp-section">
        <h2>How a message travels</h2>
        <div class="lp-flows">
            <div class="flowcard">
                <h3>Person to person</h3>
                <ol>
                    <li>You send a message: a <b>POST</b> to the Laravel app.</li>
                    <li>Laravel saves it and <b>broadcasts</b> it over Reverb.</li>
                    <li>The other person's Echo client receives it and <b>appends it live</b>, with no refresh.</li>
                </ol>
            </div>
            <div class="flowcard">
                <h3>You and the AI assistant</h3>
                <ol>
                    <li>You message the <b>AI Chatbot</b> contact.</li>
                    <li>Laravel queues a job; the <b>worker</b> calls the local model through Ollama.</li>
                    <li>As the model generates, the worker <b>streams the tokens</b> over Reverb.</li>
                    <li>Your browser renders each token <b>as it arrives</b>.</li>
                </ol>
            </div>
        </div>
    </section>

    <section class="lp-section">
        <h2>Design choices</h2>
        <div class="lp-feats">
            <div class="feat">
                <h3>Instant delivery</h3>
                <p>Broadcasts are sent synchronously (ShouldBroadcastNow), so chat stays instant even though the slow LLM work runs on a queue.</p>
            </div>
            <div class="feat">
                <h3>No self-echo</h3>
                <p>The client sends its socket id, so the server's toOthers() never bounces your own message back to you.</p>
            </div>
            <div class="feat">
                <h3>Live presence</h3>
                <p>A Reverb presence channel tracks who is connected and marks each contact online in real time.</p>
            </div>
            <div class="feat">
                <h3>Streaming, not blocking</h3>
                <p>The assistant reply is a queued job; tokens are broadcast as start, token and done frames while the model runs.</p>
            </div>
            <div class="feat">
                <h3>Local model</h3>
                <p>qwen2.5:0.5b runs on the server through Ollama. No external API, CPU-only, swappable by one env value.</p>
            </div>
            <div class="feat">
                <h3>Lightweight UI</h3>
                <p>Hand-written CSS and one small JS bundle. No UI framework, no theme, no icon font.</p>
            </div>
        </div>
    </section>

    <div class="lp-stack"><b>Stack:</b> Laravel 12, PHP 8.3, MySQL 8, nginx, Laravel Reverb, Ollama, Vite.</div>

    <footer class="lp-foot">
        <span>A focused real-time demo.</span>
        <a class="lp-cta-sm" href="{{ $go }}">{{ $goLabel }}</a>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
<script>
    // The diagram card is always light, so the light base theme reads in both
    // page color schemes. Accent matches the app's indigo.
    mermaid.initialize({
        startOnLoad: true,
        theme: 'base',
        flowchart: { padding: 14, nodeSpacing: 55, rankSpacing: 70, useMaxWidth: true },
        themeVariables: {
            primaryColor: '#f0f1f4',
            primaryBorderColor: '#c9ccd6',
            primaryTextColor: '#1c1f26',
            lineColor: '#8a92a3',
            secondaryColor: '#f6f7f9',
            tertiaryColor: '#ffffff',
        },
    });
</script>
@endsection

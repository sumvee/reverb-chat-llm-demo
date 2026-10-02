<?php

// Local LLM (Ollama) settings for the in-chat AI assistant. Defaults point at the
// compose service, so no env wiring is needed; override via env if desired.
return [
    'ollama_url' => env('OLLAMA_URL', 'http://ollama:11434'),
    'model' => env('LLM_MODEL', 'qwen2.5:0.5b'),

    // The seeded bot user is identified by this email (see ChatSeeder).
    'assistant_email' => env('ASSISTANT_EMAIL', 'assistant@example.com'),
    'assistant_name' => env('ASSISTANT_NAME', 'AI Chatbot'),

    // Kept deliberately short and directive: a 0.5B model rambles and invents
    // facts when asked to describe itself, so we tell it to just answer.
    'system_prompt' => env('LLM_SYSTEM_PROMPT',
        'You are a helpful chatbot in a demo app. Answer the user\'s latest '.
        'message directly and factually in one to three short sentences. Do not '.
        'describe yourself unless the user explicitly asks what you are.'),

    // Keep replies short so CPU-only generation stays snappy.
    'max_tokens' => (int) env('LLM_MAX_TOKENS', 200),
    'timeout' => (int) env('LLM_TIMEOUT', 120),

    // Low temperature keeps a tiny model on-topic and less prone to drifting.
    'temperature' => (float) env('LLM_TEMPERATURE', 0.3),

    // Only the last N messages are sent as context; a long history degrades a
    // 0.5B model badly (it starts mixing unrelated turns together).
    'history_limit' => (int) env('LLM_HISTORY_LIMIT', 8),
];

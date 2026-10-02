<?php

namespace App\Jobs;

use App\Events\AssistantStream;
use App\Models\Conversation;
use App\Models\User;
use App\Services\OllamaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs the local LLM for one user turn and broadcasts its reply back into the
 * conversation. Queued (not inline) so the user's send request returns at once;
 * the worker does the slow CPU generation and MessageSent (ShouldBroadcastNow)
 * delivers the answer live over Reverb when it is ready.
 */
class GenerateAssistantReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 150;

    public function __construct(public int $conversationId, public int $botUserId) {}

    public function handle(OllamaClient $llm): void
    {
        $conversation = Conversation::find($this->conversationId);
        $bot = User::find($this->botUserId);
        if (! $conversation || ! $bot) {
            return;
        }

        $limit = (int) config('llm.history_limit', 8);
        $history = $conversation->messages()
            ->with('sender:id')
            ->get()
            ->filter(fn ($m) => trim((string) $m->body) !== '')   // skip empty stream anchors
            ->map(fn ($m) => [
                'role' => $m->sender_id === $bot->id ? 'assistant' : 'user',
                'content' => $m->body,
            ])
            ->slice(-$limit)        // only the most recent turns; long history confuses a 0.5B
            ->values()
            ->all();

        array_unshift($history, ['role' => 'system', 'content' => (string) config('llm.system_prompt')]);

        // Anchor the stream with an empty message, then stream tokens into it.
        $message = $conversation->messages()->create(['sender_id' => $bot->id, 'body' => '']);
        $base = ['message_id' => $message->id, 'sender_id' => $bot->id, 'sender_name' => $bot->name];

        broadcast(new AssistantStream($conversation->id, $base + ['phase' => 'start']));

        $pending = '';
        $lastFlush = microtime(true);
        $flush = function (bool $force = false) use (&$pending, &$lastFlush, $conversation, $base) {
            if ($pending === '') {
                return;
            }
            if ($force || strlen($pending) >= 24 || (microtime(true) - $lastFlush) > 0.12) {
                broadcast(new AssistantStream($conversation->id, $base + ['phase' => 'token', 'delta' => $pending]));
                $pending = '';
                $lastFlush = microtime(true);
            }
        };

        try {
            $full = $llm->streamChat($history, function (string $delta) use (&$pending, $flush) {
                $pending .= $delta;
                $flush();
            });
        } catch (Throwable $e) {
            report($e);
            $full = '';
        }

        if ($full === '') {
            $pending = "I couldn't reach the local model just now. Please try again in a moment.";
            $full = $pending;
        }
        $flush(true);

        $message->update(['body' => $full]);
        broadcast(new AssistantStream($conversation->id, $base + [
            'phase' => 'done',
            'time' => $message->created_at->format('g:i A'),
        ]));
    }
}

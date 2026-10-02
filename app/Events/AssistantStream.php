<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * One frame of a streaming assistant reply, pushed live over Reverb as the local
 * LLM generates it. phase is 'start' (open an empty bubble), 'token' (append a
 * delta) or 'done' (finalise). ShouldBroadcastNow so the worker emits each frame
 * immediately rather than queueing it.
 */
class AssistantStream implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param array<string, mixed> $payload */
    public function __construct(public int $conversationId, public array $payload) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'AssistantStream';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->payload + ['conversation_id' => $this->conversationId];
    }
}

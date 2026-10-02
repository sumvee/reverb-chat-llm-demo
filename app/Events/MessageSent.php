<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// ShouldBroadcastNow (not ShouldBroadcast): deliver synchronously even when the
// queue connection is 'database'. The assistant reply is dispatched from a queue
// worker, and we want its broadcast to go out immediately, not wait for another
// queue pass. Human-to-human chat likewise stays instant.
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.'.$this->message->conversation_id)];
    }

    /**
     * A stable, simple broadcast name. Without this the event broadcasts as the
     * FQCN "App\Events\MessageSent", and Echo's .listen('MessageSent') resolves
     * names against its namespace, so they don't match. With broadcastAs, the
     * browser listens with a leading dot: .listen('.MessageSent').
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /** Flat payload the browser renders directly -- avoids shipping the model. */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toIso8601String(),
            'time' => $this->message->created_at->format('g:i A'),
        ];
    }
}

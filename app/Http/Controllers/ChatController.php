<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Jobs\GenerateAssistantReply;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON backend for the live chat. Both surfaces (the /page/page_chat messenger
 * and the slide-out in bottom-setting) talk to these endpoints; the real-time
 * delivery is handled by Reverb broadcasting MessageSent.
 */
class ChatController extends Controller
{
    /** The current user's conversations, each with the other participant. */
    public function conversations(Request $request): JsonResponse
    {
        $me = $request->user();

        $items = Conversation::forUser($me)
            ->with(['userOne', 'userTwo'])
            ->withCount('messages')
            ->get()
            ->map(function (Conversation $c) use ($me) {
                $other = $c->otherParticipant($me);

                return [
                    'id' => $c->id,
                    'other_id' => $other->id,
                    'name' => $other->name,
                    'messages_count' => $c->messages_count,
                    'is_bot' => $other->email === config('llm.assistant_email'),
                ];
            });

        return response()->json(['me' => ['id' => $me->id, 'name' => $me->name], 'conversations' => $items]);
    }

    /** Messages in a conversation the user is part of. */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->hasParticipant($request->user()), 403);

        return response()->json([
            'messages' => $conversation->messages()->with('sender:id,name')->get()->map(fn (Message $m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_name' => $m->sender->name,
                'body' => $m->body,
                'time' => $m->created_at->format('g:i A'),
                'mine' => $m->sender_id === $request->user()->id,
            ]),
        ]);
    }

    /** Post a message; broadcast it to the other participant in real time. */
    public function send(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->hasParticipant($request->user()), 403);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $message = $conversation->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        // Broadcast to every subscriber; the client skips its own message (which
        // it rendered optimistically). This avoids relying on the socket id, which
        // is ambiguous when one page holds several Echo connections (the split view).
        broadcast(new MessageSent($message));

        // If the other participant is the AI assistant, queue a reply. The job
        // runs the local LLM and broadcasts the answer back when it is ready.
        $other = $conversation->otherParticipant($request->user());
        if ($other->email === config('llm.assistant_email')) {
            GenerateAssistantReply::dispatch($conversation->id, $other->id);
        }

        return response()->json([
            'id' => $message->id,
            'time' => $message->created_at->format('g:i A'),
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds the chat demo: six people with a conversation between every pair (so any
 * two can be signed into different windows and chat), plus an AI Chatbot bot
 * every person can message. Idempotent: existing conversations are left alone.
 */
class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $humans = collect([
            ['name' => 'Demo User',   'email' => 'demo@example.com'],
            ['name' => 'Adison Lee',  'email' => 'adison@example.com'],
            ['name' => 'Lisa Lamar',  'email' => 'lisa@example.com'],
            ['name' => 'Troy Norman', 'email' => 'troy@example.com'],
            ['name' => 'Sadi Orlaf',  'email' => 'sadi@example.com'],
            ['name' => 'Amanda Yang', 'email' => 'amanda@example.com'],
        ])->map(fn ($u) => User::updateOrCreate(
            ['email' => $u['email']],
            ['name' => $u['name'], 'password' => Hash::make('password'), 'email_verified_at' => now()]
        ))->values();

        $openers = [
            ['Hey, did you get a chance to look at the report?', 'Not yet, will review it this afternoon.'],
            ['The new dashboard looks great!', 'Thanks, took a while to get the charts right.'],
            ['Are we still on for the 3pm sync?', 'Yep, see you then.'],
            ['Can you share the notes from standup?', 'Sent them over just now.'],
            ['Lunch later?', 'Absolutely, the usual place?'],
            ['Did the deploy go through?', 'All green, shipped a few minutes ago.'],
            ['Quick question about the API when you have a sec.', 'Sure, fire away.'],
            ['Nice work on the demo today.', 'Appreciate it!'],
        ];

        // A conversation between every pair of people.
        $pair = 0;
        for ($a = 0; $a < $humans->count(); $a++) {
            for ($b = $a + 1; $b < $humans->count(); $b++) {
                $one = $humans[$a];
                $two = $humans[$b];
                $convo = Conversation::firstOrCreate(['user_one_id' => $one->id, 'user_two_id' => $two->id]);

                if (! $convo->messages()->exists()) {
                    [$first, $reply] = $openers[$pair % count($openers)];
                    Message::create(['conversation_id' => $convo->id, 'sender_id' => $two->id, 'body' => $first]);
                    Message::create(['conversation_id' => $convo->id, 'sender_id' => $one->id, 'body' => $reply]);
                }
                $pair++;
            }
        }

        // The AI assistant: a bot everyone can chat with (answered by the local LLM).
        $assistant = User::updateOrCreate(
            ['email' => config('llm.assistant_email')],
            ['name' => config('llm.assistant_name'), 'password' => Hash::make(Str::random(40)), 'email_verified_at' => now()]
        );

        $greeting = "Hi! I'm a small language model (Qwen2.5 0.5B) running locally on this server. "
            ."Ask me anything and watch my reply stream back token by token over WebSockets. "
            ."I'm CPU-only, so give me a few seconds to think.";

        foreach ($humans as $human) {
            $convo = Conversation::firstOrCreate(['user_one_id' => $human->id, 'user_two_id' => $assistant->id]);
            if (! $convo->messages()->exists()) {
                Message::create(['conversation_id' => $convo->id, 'sender_id' => $assistant->id, 'body' => $greeting]);
            }
        }
    }
}

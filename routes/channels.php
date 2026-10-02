<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// A private conversation channel: only its two participants may subscribe.
// Getting this wrong would let anyone read anyone's messages.
Broadcast::channel('conversation.{conversation}', function (User $user, Conversation $conversation) {
    return $conversation->hasParticipant($user);
});

// Presence channel driving the status dots. The returned array is the member
// info other subscribers see; null/false denies membership.
Broadcast::channel('chat', function (User $user) {
    return ['id' => $user->id, 'name' => $user->name];
});

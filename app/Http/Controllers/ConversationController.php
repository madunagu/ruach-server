<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

/**
 * Private and group messaging.
 *
 * Every lookup is resolved through the caller's own memberships, so a
 * conversation id cannot be used to read or post into somebody else's thread:
 * an id the caller is not part of answers 404 exactly like one that does not
 * exist.
 */
class ConversationController extends Controller
{
    /** Threads the caller takes part in, most recently active first. */
    public function list(Request $request)
    {
        $perPage = (int) ($request['perPage'] ?? 30);
        $viewerId = Auth::id();

        $conversations = Conversation::whereHas('participants', function ($query) use ($viewerId) {
            $query->where('users.id', $viewerId);
        })
            ->with([
                'participants:id,name,avatar',
                'event:id,name',
                'latestMessage' => fn ($query) => $query->limit(1),
            ])
            // A thread that has never been posted in has a null sort key; fall
            // back to its creation time so it still appears somewhere sensible.
            ->orderByRaw('COALESCE(last_message_at, created_at) desc')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($conversations->items())->map(function ($conversation) use ($viewerId) {
                return $this->summarise($conversation, $viewerId);
            })->values(),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ],
        ]);
    }

    /**
     * Opens a thread, creating it if it does not exist.
     *
     * Takes either `user_id` for a private conversation or `event_id` for a
     * group's. Both are idempotent: asking twice returns the same thread, so a
     * retried request cannot produce a duplicate.
     */
    public function open(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required_without:event_id|nullable|integer|exists:users,id',
            'event_id' => 'required_without:user_id|nullable|integer|exists:events,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $viewerId = Auth::id();

        if ($request['event_id'] !== null) {
            return $this->openEventGroup((int) $request['event_id']);
        }

        $otherId = (int) $request['user_id'];
        if ($otherId === $viewerId) {
            return response()->json([
                'errors' => 'You cannot start a conversation with yourself.',
            ], 422);
        }

        $conversation = Conversation::between($viewerId, $otherId);

        if ($conversation === null) {
            $conversation = DB::transaction(function () use ($viewerId, $otherId) {
                $thread = Conversation::create([
                    'uuid' => (string) Str::uuid(),
                    'type' => Conversation::TYPE_PRIVATE,
                    'event_id' => null,
                    'title' => null,
                ]);

                // Whoever opens the thread starts as having read it, so it does
                // not immediately show up as unread for them.
                $thread->addParticipant($viewerId);
                $thread->addParticipant($otherId);

                return $thread;
            });
        } else {
            // Re-opening after being removed, or a stale client, rejoins rather
            // than 404ing on a thread the user can see in their list.
            $conversation->addParticipant($viewerId);
        }

        return response()->json([
            'data' => $this->detail($conversation->fresh(['participants', 'latestMessage']), $viewerId),
        ], $conversation->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Returns the group thread for an event, creating it on first use.
     *
     * Only the event's attendees and its organiser may join, so an unrelated
     * user cannot walk into a private event's discussion by guessing its id.
     */
    private function openEventGroup(int $eventId)
    {
        $viewerId = Auth::id();

        $event = Event::with('attendees:id,name,avatar', 'user:id')
            ->find($eventId);

        if ($event === null) {
            return response()->json(['data' => false], 404);
        }

        $isAttendee = $event->attendees->contains('id', $viewerId);
        $isOrganiser = $event->user_id === $viewerId;

        if (! $isAttendee && ! $isOrganiser) {
            return response()->json([
                'errors' => 'Join this event before taking part in its chat.',
            ], 403);
        }

        $conversation = Conversation::forEvent($eventId);

        if ($conversation === null) {
            $conversation = DB::transaction(function () use ($event, $viewerId) {
                $thread = Conversation::create([
                    'uuid' => (string) Str::uuid(),
                    'type' => Conversation::TYPE_EVENT,
                    'event_id' => $event->id,
                    'title' => $event->name,
                ]);

                $thread->addParticipant($viewerId);

                return $thread;
            });
        } else {
            $conversation->addParticipant($viewerId);
        }

        // Bring in anyone who joined the event after the thread was created.
        foreach ($event->attendees as $attendee) {
            $conversation->addParticipant($attendee->id);
        }

        return response()->json([
            'data' => $this->detail(
                $conversation->fresh(['participants', 'event:id,name', 'latestMessage']),
                $viewerId
            ),
        ], $conversation->wasRecentlyCreated ? 201 : 200);
    }

    /** The messages in a thread, oldest first. */
    public function messages(Request $request)
    {
        $conversation = $this->findForUser((int) $request->route('id'));
        if ($conversation === null) {
            return response()->json(['data' => false], 404);
        }

        $perPage = (int) ($request['perPage'] ?? 50);

        $messages = $conversation->messages()
            ->with('user:id,name,avatar')
            ->paginate($perPage);

        return response()->json([
            'data' => collect($messages->items())->map(fn (Message $message) => $this->message($message))->values(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /** Posts a message into a thread the caller belongs to. */
    public function send(Request $request)
    {
        $conversation = $this->findForUser((int) $request->route('id'));
        if ($conversation === null) {
            return response()->json(['data' => false], 404);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 422);
        }

        $message = DB::transaction(function () use ($conversation, $request) {
            $message = $conversation->messages()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => Auth::id(),
                'body' => $request['body'],
            ]);

            $conversation->touchLastMessage();
            // The sender has by definition read their own message; without this
            // they would see an unread badge on a thread they just wrote to.
            $conversation->markReadFor(Auth::id());

            return $message;
        });

        return response()->json([
            'data' => $this->message($message->load('user:id,name,avatar')),
        ], 201);
    }

    /** Marks everything currently in the thread as read for the caller. */
    public function read(Request $request)
    {
        $conversation = $this->findForUser((int) $request->route('id'));
        if ($conversation === null) {
            return response()->json(['data' => false], 404);
        }

        $conversation->markReadFor(Auth::id());

        return response()->json(['data' => true]);
    }

    /** Total unread messages across every thread, for the badge. */
    public function unreadCount()
    {
        return response()->json([
            'data' => ['unread' => Conversation::unreadTotalFor(Auth::id())],
        ]);
    }

    /**
     * Resolves a thread the caller is part of, or null.
     *
     * Scoping the query by membership is what stops a conversation id being
     * used to read or write into someone else's messages.
     */
    private function findForUser(int $id): ?Conversation
    {
        $viewerId = Auth::id();

        return Conversation::where('id', $id)
            ->whereHas('participants', function ($query) use ($viewerId) {
                $query->where('users.id', $viewerId);
            })
            ->first();
    }

    /** Compact form for the conversation list. */
    private function summarise(Conversation $conversation, int $viewerId): array
    {
        $latest = $conversation->latestMessage->first();

        return [
            'id' => $conversation->id,
            'uuid' => $conversation->uuid,
            'type' => $conversation->type,
            'title' => $conversation->displayTitle($viewerId),
            'event_id' => $conversation->event_id,
            'participants' => $conversation->participants->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar,
            ])->values(),
            'last_message' => $latest ? [
                'id' => $latest->id,
                'body' => $latest->body,
                'user_id' => $latest->user_id,
                'created_at' => optional($latest->created_at)->toIso8601String(),
            ] : null,
            'last_message_at' => optional($conversation->last_message_at)->toIso8601String(),
            'unread_count' => $conversation->unreadCountFor($viewerId),
        ];
    }

    /** Full form for one opened thread. */
    private function detail(Conversation $conversation, int $viewerId): array
    {
        return array_merge($this->summarise($conversation, $viewerId), [
            'event' => $conversation->event ? [
                'id' => $conversation->event->id,
                'name' => $conversation->event->name,
            ] : null,
        ]);
    }

    private function message(Message $message): array
    {
        return [
            'id' => $message->id,
            'uuid' => $message->uuid,
            'conversation_id' => $message->conversation_id,
            'body' => $message->body,
            'read_at' => optional($message->read_at)->toIso8601String(),
            'created_at' => optional($message->created_at)->toIso8601String(),
            'user' => $message->user ? [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'avatar' => $message->user->avatar,
            ] : null,
        ];
    }
}
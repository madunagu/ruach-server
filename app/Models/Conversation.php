<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A thread of messages.
 *
 * One shape covers both cases the app needs: a `private` conversation between
 * two users, and an `event` conversation whose participants are that event's
 * attendees. Keeping them in one table means the client has a single list, a
 * single read endpoint and a single send endpoint rather than two of each.
 *
 * Deliberately not soft-deleting: there is no delete flow yet, and a hard
 * delete would be the honest behaviour to add if one ever arrives.
 *
 * @property int $id
 * @property string $uuid
 * @property string $type 'private' or 'event'
 * @property int|null $event_id
 * @property string|null $title
 * @property \Illuminate\Support\Carbon|null $last_message_at
 */
class Conversation extends Model
{
    protected $table = 'conversations';

    public const TYPE_PRIVATE = 'private';
    public const TYPE_EVENT = 'event';

    protected $fillable = [
        'uuid',
        'type',
        'event_id',
        'title',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'conversation_user',
            'conversation_id',
            'user_id'
        )->withPivot('last_read_message_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** The newest message, used for the preview line in the list. */
    public function latestMessage(): HasMany
    {
        return $this->hasMany(Message::class)->orderByDesc('id');
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_EVENT;
    }

    /**
     * Finds the private conversation between two users, or null.
     *
     * Both orderings are checked because the pair is not ordered in the data:
     * the same two people must always land on the same thread.
     */
    public static function between(int $a, int $b): ?self
    {
        $ids = [$a, $b];
        sort($ids);

        $thread = static::where('type', self::TYPE_PRIVATE)
            ->whereHas('participants', function ($query) use ($ids) {
                $query->whereIn('users.id', $ids);
            })
            ->get()
            ->first(function (self $conversation) use ($ids) {
                $members = $conversation->participants->pluck('id')->sort()->values()->all();

                return $members === $ids;
            });

        return $thread;
    }

    /**
     * The group's conversation for an event, or null.
     */
    public static function forEvent(int $eventId): ?self
    {
        return static::where('type', self::TYPE_EVENT)
            ->where('event_id', $eventId)
            ->first();
    }

    /**
     * Adds a participant without disturbing anyone already present.
     *
     * `syncWithoutDetaching` keeps the membership idempotent, so opening the
     * same event chat twice does not duplicate a row or reset their read mark.
     */
    public function addParticipant(int $userId): void
    {
        $this->participants()->syncWithoutDetaching([
            $userId => ['last_read_message_id' => 0],
        ]);
    }

    /** Marks everything currently in the thread as read for one participant. */
    public function markReadFor(int $userId): void
    {
        // Read up to the newest message present now, rather than stamping a
        // time: anything sent after this call counts as unread, and anything
        // already here does not.
        $latest = (int) ($this->messages()->max('id') ?? 0);

        $this->participants()->updateExistingPivot($userId, [
            'last_read_message_id' => $latest,
        ]);
    }

    /**
     * Bumps the ordering key so the thread floats to the top of the list.
     */
    public function touchLastMessage(): void
    {
        $this->forceFill(['last_message_at' => now()])->save();
    }

    /**
     * Title to show in the list.
     *
     * A group uses its own name; a private thread is named after the *other*
     * participant, so this needs the viewer's id to answer correctly.
     */
    public function displayTitle(int $viewerId): string
    {
        if ($this->title !== null && $this->title !== '') {
            return $this->title;
        }

        // A private thread is named after the *other* participant, so the viewer has
        // to be excluded. `firstWhere` only compares with equality, hence the
        // explicit predicate rather than an `id != $viewerId` lookup.
        $other = $this->participants
            ->firstWhere(fn (User $user) => $user->id !== $viewerId);

        return $other?->name ?? 'Conversation';
    }

    /**
     * How many messages this participant has not opened.
     */
    public function unreadCountFor(int $userId): int
    {
        $pivot = $this->participants->firstWhere('id', $userId);
        $since = (int) ($pivot?->pivot?->last_read_message_id ?? 0);

        return $this->messages()
            ->where('user_id', '!=', $userId)
            ->where('id', '>', $since)
            ->count();
    }

    /**
     * Unread messages across every thread this user is in.
     *
     * Used for the badge, so it deliberately excludes the user's own messages:
     * a count of everything ever sent is not what a notification badge means.
     */
    public static function unreadTotalFor(int $userId): int
    {
        $total = 0;

        $threads = static::whereHas('participants', function ($query) use ($userId) {
            $query->where('users.id', $userId);
        })->with('participants:id')->get();

        foreach ($threads as $thread) {
            $total += $thread->unreadCountFor($userId);
        }

        return $total;
    }
}
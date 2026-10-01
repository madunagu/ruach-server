<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An in-app notification about social activity.
 *
 * Distinct from Laravel's built-in `notifications` table: this one is
 * user-visible list content that the app renders, not a transport for mail or
 * push.
 *
 * @property int $id
 * @property int $notifiable_id
 * @property string $notifiable_type
 * @property string $type
 * @property int|null $actor_id
 * @property int|null $subject_id
 * @property string|null $subject_type
 * @property string|null $body
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $read_at
 */
class Notification extends Model
{
    /** Someone liked something the recipient owns. */
    public const LIKE = 'like';

    /** Someone commented, either on the recipient's content or a reply. */
    public const COMMENT = 'comment';

    /** Someone started following the recipient. */
    public const FOLLOW = 'follow';

    /** Someone is attending an event the recipient hosts or posted. */
    public const ATTEND = 'attend';

    /** A direct message arrived. */
    public const MESSAGE = 'message';

    protected $fillable = [
        'notifiable_id',
        'notifiable_type',
        'type',
        'actor_id',
        'subject_id',
        'subject_type',
        'body',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /** The user who receives this notification. */
    public function notifiable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }

    /** The user who performed the action. */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** The content the action was performed on, when there is one. */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /** Only unread rows. */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $type === null || $type === ''
            ? $query
            : $query->where('type', $type);
    }

    public function markAsRead(): bool
    {
        if ($this->read_at !== null) {
            return false;
        }

        $this->read_at = now();
        return $this->save();
    }

    /**
     * Records an activity notification, skipping self-directed and duplicate
     * entries.
     *
     * A user should not be told that they liked their own post, and a retried
     * request should not produce a second copy of the same notification.
     */
    public static function record(array $attributes): ?self
    {
        $actorId = $attributes['actor_id'] ?? null;
        $notifiableId = $attributes['notifiable_id'] ?? null;

        if ($actorId === null || $notifiableId === null) {
            return null;
        }
        if ((int) $actorId === (int) $notifiableId) {
            return null;
        }

        return static::firstOrCreate(
            [
                'notifiable_id' => $notifiableId,
                'notifiable_type' => 'user',
                'type' => $attributes['type'],
                'actor_id' => $actorId,
                'subject_id' => $attributes['subject_id'] ?? null,
                'subject_type' => $attributes['subject_type'] ?? null,
            ],
            [
                'body' => $attributes['body'] ?? null,
                'data' => $attributes['data'] ?? null,
            ]
        );
    }
}
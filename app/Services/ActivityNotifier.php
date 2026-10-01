<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * Turns social actions into in-app notifications.
 *
 * Centralised so every entry point (likes, comments, follows, attendance)
 * produces the same shape, and so a failure to notify can never break the
 * action the user actually asked for.
 */
class ActivityNotifier
{
    /** Notifies that [actorId] liked [subject]. */
    public function liked(int $actorId, $subject, string $type): void
    {
        $ownerId = $this->ownerOf($subject);
        if ($ownerId === null) {
            return;
        }

        $this->safe(function () use ($actorId, $ownerId, $subject, $type) {
            Notification::record([
                'notifiable_id' => $ownerId,
                'notifiable_type' => 'user',
                'type' => Notification::LIKE,
                'actor_id' => $actorId,
                'subject_id' => $subject->id,
                'subject_type' => $type,
                'data' => ['name' => $subject->name ?? null],
            ]);
        });
    }

    /**
     * Notifies about a new comment.
     *
     * A reply goes to the commenter being replied to; a top-level comment goes
     * to the owner of the commented-on content.
     */
    public function commented(int $actorId, $comment, $subject = null): void
    {
        $recipientId = null;

        if (!empty($comment->parent_id)) {
            $parent = $comment->parent()->first();
            $recipientId = $parent?->user_id;
        }

        if ($recipientId === null && $subject !== null) {
            $recipientId = $this->ownerOf($subject);
        }

        if ($recipientId === null) {
            return;
        }

        $this->safe(function () use ($actorId, $recipientId, $comment, $subject) {
            Notification::record([
                'notifiable_id' => $recipientId,
                'notifiable_type' => 'user',
                'type' => Notification::COMMENT,
                'actor_id' => $actorId,
                'subject_id' => $comment->id,
                'subject_type' => 'comment',
                'data' => [
                    'comment' => $comment->comment,
                    'commentable_id' => $comment->commentable_id,
                    'commentable_type' => $comment->commentable_type,
                    'subject_name' => $subject?->name ?? null,
                ],
            ]);
        });
    }

    /** Notifies [recipientId] that someone started following them. */
    public function followed(int $actorId, int $recipientId): void
    {
        $this->safe(function () use ($actorId, $recipientId) {
            Notification::record([
                'notifiable_id' => $recipientId,
                'notifiable_type' => 'user',
                'type' => Notification::FOLLOW,
                'actor_id' => $actorId,
                'subject_id' => $actorId,
                'subject_type' => 'user',
            ]);
        });
    }

    /**
     * Notifies that someone is attending an event.
     *
     * The host is the event's author; falling back to the poster covers events
     * created on someone else's behalf.
     */
    public function attending(int $actorId, $event): void
    {
        $recipientId = $event->user_id ?? $event->poster_id ?? null;
        if ($recipientId === null) {
            return;
        }

        $this->safe(function () use ($actorId, $recipientId, $event) {
            Notification::record([
                'notifiable_id' => $recipientId,
                'notifiable_type' => 'user',
                'type' => Notification::ATTEND,
                'actor_id' => $actorId,
                'subject_id' => $event->id,
                'subject_type' => 'event',
                'data' => ['name' => $event->name ?? null],
            ]);
        });
    }

    /** Notifies that a direct message was received. */
    public function messaged(int $actorId, int $recipientId, string $message): void
    {
        $this->safe(function () use ($actorId, $recipientId, $message) {
            Notification::record([
                'notifiable_id' => $recipientId,
                'notifiable_type' => 'user',
                'type' => Notification::MESSAGE,
                'actor_id' => $actorId,
                'subject_id' => $actorId,
                'subject_type' => 'user',
                'data' => ['message' => $message],
            ]);
        });
    }

    /**
     * Resolves the user who should be told about activity on [subject].
     *
     * Returns null when the subject has no identifiable owner, so callers can
     * skip rather than notify the wrong person.
     */
    private function ownerOf($subject): ?int
    {
        if ($subject === null) {
            return null;
        }

        if ($subject instanceof User) {
            return (int) $subject->id;
        }

        foreach (['user_id', 'poster_id'] as $column) {
            $value = $subject->{$column} ?? null;
            if ($value !== null) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * Runs a notification write without letting it break the request.
     *
     * Notifying is a side effect of the user's actual action; if it fails the
     * action must still succeed.
     */
    private function safe(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
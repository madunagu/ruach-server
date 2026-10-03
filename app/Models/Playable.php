<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single item that can be played and put in a playlist.
 *
 * A video upload also produces an AudioPost for the same recording, so a
 * video-backed playable has both ids and an audio-backed playable has only the
 * audio one. Everything user-facing (a playlist row, a now-playing entry, a
 * history item) refers to the playable rather than to either post, which keeps
 * a single recording from appearing twice.
 *
 * @property int $id
 * @property int|null $audio_post_id
 * @property int|null $video_post_id
 * @property int $user_id
 */
class Playable extends Model
{
    protected $table = 'playables';

    protected $fillable = [
        'audio_post_id',
        'video_post_id',
        'user_id',
    ];

    protected $casts = [
        'audio_post_id' => 'integer',
        'video_post_id' => 'integer',
    ];

    public function audioPost(): BelongsTo
    {
        return $this->belongsTo(AudioPost::class, 'audio_post_id');
    }

    public function videoPost(): BelongsTo
    {
        return $this->belongsTo(VideoPost::class, 'video_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function playlists(): BelongsToMany
    {
        return $this->belongsToMany(
            Playlist::class,
            'playlist_playable',
            'playable_id',
            'playlist_id'
        )->withPivot('rank');
    }

    /**
     * True when this playable has a video stream to show.
     *
     * Drives whether the player's video toggle is offered at all.
     */
    public function hasVideo(): bool
    {
        return $this->video_post_id !== null;
    }

    /** True when there is audio to play, which every playable must have. */
    public function hasAudio(): bool
    {
        return $this->audio_post_id !== null;
    }

    /**
     * Finds the playable backing a post, if one exists.
     *
     * @param string $relation either audio_post_id or video_post_id
     */
    public static function forPost(string $relation, int $postId): ?self
    {
        return static::where($relation, $postId)->first();
    }

    /**
     * Returns the playable for an audio post, creating it if absent.
     *
     * Called on audio creation so every playable has a single canonical row,
     * rather than depending on a later reconciliation step.
     */
    public static function forAudioPost(AudioPost $audio): self
    {
        return static::firstOrCreate(
            ['audio_post_id' => $audio->id],
            ['video_post_id' => null, 'user_id' => $audio->user_id]
        );
    }

    /**
     * Returns the playable for a video post, creating it if absent.
     *
     * When the video's audio has already been registered, that playable is
     * upgraded to carry the video too, so one recording stays one playable.
     */
    public static function forVideoPost(VideoPost $video, ?AudioPost $audio = null): self
    {
        if ($audio !== null) {
            $playable = static::forAudioPost($audio);
            $playable->video_post_id = $video->id;
            $playable->save();

            return $playable;
        }

        return static::firstOrCreate(
            ['video_post_id' => $video->id],
            ['audio_post_id' => null, 'user_id' => $video->user_id]
        );
    }

    /**
     * Playables this user owns, newest first, with both posts eager loaded.
     */
    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('user_id', $userId)->orderByDesc('id');
    }
}
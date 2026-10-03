<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * A named, ordered collection of playables.
 *
 * Order lives in the pivot's `rank` rather than on the pivot row's id, so a
 * reorder only touches the rows that moved.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $user_id
 */
class Playlist extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Members in display order, with both underlying posts loaded. */
    public function playables(): BelongsToMany
    {
        return $this->belongsToMany(
            Playable::class,
            'playlist_playable',
            'playlist_id',
            'playable_id'
        )
            ->withPivot('rank')
            ->orderBy('playlist_playable.rank')
            ->orderBy('playlist_playable.id');
    }

    /**
     * Appends a playable, or moves it to the end if already present.
     *
     * Re-adding an existing member is treated as a reorder rather than an
     * error, which makes a retried request idempotent.
     */
    public function addPlayable(Playable $playable): self
    {
        if ($this->playables()->where('playables.id', $playable->id)->exists()) {
            return $this;
        }

        $this->playables()->attach($playable->id, [
            'rank' => $this->nextRank(),
        ]);

        return $this;
    }

    public function removePlayable(Playable $playable): self
    {
        $this->playables()->detach($playable->id);

        return $this;
    }

    /**
     * Rewrites the member order to match [ids].
     *
     * Ranks are rewritten densely so a repeated reorder cannot accumulate gaps
     * or leave two members sharing a rank.
     */
    public function reorder(array $ids): self
    {
        $rank = 0;
        foreach ($ids as $id) {
            $this->playables()
                ->updateExistingPivot((int) $id, ['rank' => $rank++]);
        }

        return $this;
    }

    /** One past the highest rank currently used. */
    public function nextRank(): int
    {
        $max = DB::table('playlist_playable')
            ->where('playlist_id', $this->id)
            ->max('rank');

        return $max === null ? 0 : ((int) $max) + 1;
    }
}
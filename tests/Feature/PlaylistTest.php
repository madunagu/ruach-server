<?php

namespace Tests\Feature;

use App\Models\AudioPost;
use App\Models\Playlist;
use App\Models\Playable;
use App\Models\User;
use App\Models\VideoPost;
use App\Services\PlayableFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlaylistTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        // Hash explicitly: the factory's plain value cannot be verified by the
        // `hashed` cast on this PHP/Laravel combination.
        $this->owner = User::factory()->create([
            'name' => 'Owner',
            'password' => Hash::make('secret'),
        ]);
        $this->stranger = User::factory()->create([
            'name' => 'Stranger',
            'password' => Hash::make('secret'),
        ]);
    }

    private function audioPlayable(string $name = 'Track'): Playable
    {
        $audio = AudioPost::create([
            'name' => $name,
            'src_url' => 'https://example.test/a.mp3',
            'size' => 1000,
            'length' => 120,
            'user_id' => $this->owner->id,
        ]);

        return Playable::forAudioPost($audio);
    }

    private function videoPlayable(string $name = 'Clip'): Playable
    {
        $video = VideoPost::create([
            'name' => $name,
            'src_url' => 'https://example.test/v.mp4',
            'size' => 5000,
            'length' => 180,
            'user_id' => $this->owner->id,
        ]);

        return Playable::forVideoPost($video);
    }

    public function test_an_audio_post_gets_a_playable(): void
    {
        $audio = AudioPost::create([
            'name' => 'Song',
            'src_url' => 'https://example.test/a.mp3',
            'user_id' => $this->owner->id,
        ]);

        $playable = app(PlayableFactory::class)->forAudio($audio);

        $this->assertNotNull($playable->id);
        $this->assertSame($audio->id, $playable->audio_post_id);
        $this->assertNull($playable->video_post_id);
        $this->assertFalse($playable->hasVideo());
        $this->assertTrue($playable->hasAudio());
    }

    public function test_a_video_post_produces_one_playable_with_both_sides(): void
    {
        $video = VideoPost::create([
            'name' => 'Clip',
            'src_url' => 'https://example.test/v.mp4',
            'user_id' => $this->owner->id,
        ]);

        $playable = app(PlayableFactory::class)->forVideo(
            $video,
            'video/full/v.mp4'
        );

        // One playable, not two: the audio mirror is attached to the same row.
        $this->assertSame($video->id, $playable->video_post_id);
        $this->assertNotNull($playable->audio_post_id);
        $this->assertTrue($playable->hasVideo());
        $this->assertSame(1, Playable::where('video_post_id', $video->id)->count());

        // The mirror is a real audio post carrying the same title and owner.
        $audio = AudioPost::find($playable->audio_post_id);
        $this->assertNotNull($audio);
        $this->assertSame('Clip', $audio->name);
        $this->assertSame($this->owner->id, $audio->user_id);
    }

    public function test_the_same_playable_is_reused_for_the_same_audio(): void
    {
        $audio = AudioPost::create([
            'name' => 'Song',
            'src_url' => 'https://example.test/a.mp3',
            'user_id' => $this->owner->id,
        ]);

        $first = Playable::forAudioPost($audio);
        $second = Playable::forAudioPost($audio);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Playable::count());
    }

    public function test_a_user_can_create_and_list_playlists(): void
    {
        $response = $this->actingAs($this->owner)->postJson('/api/playlists', [
            'name' => 'Morning worship',
            'description' => 'For the commute',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Morning worship');

        $this->actingAs($this->owner)
            ->getJson('/api/playlists')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_playlist_requires_a_name(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/playlists', ['description' => 'no name'])
            ->assertStatus(422);
    }

    public function test_playlists_are_scoped_to_their_owner(): void
    {
        $playlist = Playlist::create([
            'name' => 'Private',
            'user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->stranger)
            ->getJson("/api/playlists/{$playlist->id}")
            ->assertNotFound();

        $this->actingAs($this->stranger)
            ->putJson("/api/playlists/{$playlist->id}", ['name' => 'Hijacked'])
            ->assertNotFound();

        $this->actingAs($this->stranger)
            ->deleteJson("/api/playlists/{$playlist->id}")
            ->assertNotFound();

        $this->assertSame('Private', $playlist->fresh()->name);
    }

    public function test_members_can_be_added_and_removed(): void
    {
        $playlist = Playlist::create([
            'name' => 'Mix',
            'user_id' => $this->owner->id,
        ]);
        $first = $this->audioPlayable('One');
        $second = $this->audioPlayable('Two');

        $this->actingAs($this->owner)
            ->postJson("/api/playlists/{$playlist->id}/playables", [
                'playable_id' => $first->id,
            ])
            ->assertOk();

        $this->actingAs($this->owner)
            ->postJson("/api/playlists/{$playlist->id}/playables", [
                'playable_id' => $second->id,
            ])
            ->assertOk();

        $this->assertSame(2, $playlist->playables()->count());

        $this->actingAs($this->owner)
            ->deleteJson(
                "/api/playlists/{$playlist->id}/playables/{$first->id}"
            )
            ->assertOk();

        $this->assertSame(1, $playlist->playables()->count());
    }

    public function test_adding_the_same_playable_twice_is_idempotent(): void
    {
        $playlist = Playlist::create([
            'name' => 'Mix',
            'user_id' => $this->owner->id,
        ]);
        $playable = $this->audioPlayable();

        foreach (range(1, 3) as $_) {
            $this->actingAs($this->owner)
                ->postJson("/api/playlists/{$playlist->id}/playables", [
                    'playable_id' => $playable->id,
                ])
                ->assertOk();
        }

        $this->assertSame(1, $playlist->playables()->count());
    }

    public function test_reorder_writes_dense_ranks_in_the_given_order(): void
    {
        $playlist = Playlist::create([
            'name' => 'Mix',
            'user_id' => $this->owner->id,
        ]);
        $a = $this->audioPlayable('A');
        $b = $this->audioPlayable('B');
        $c = $this->audioPlayable('C');
        foreach ([$a, $b, $c] as $p) {
            $playlist->addPlayable($p);
        }

        $this->actingAs($this->owner)
            ->postJson("/api/playlists/{$playlist->id}/reorder", [
                'playable_ids' => [$c->id, $a->id, $b->id],
            ])
            ->assertOk();

        $this->assertSame(
            [$c->id, $a->id, $b->id],
            $playlist->playables()->pluck('playables.id')->all()
        );

        $ranks = DB::table('playlist_playable')
            ->where('playlist_id', $playlist->id)
            ->orderBy('rank')
            ->pluck('rank')
            ->all();
        $this->assertSame([0, 1, 2], $ranks);
    }

    public function test_reordering_repeatedly_does_not_accumulate_gaps(): void
    {
        $playlist = Playlist::create([
            'name' => 'Mix',
            'user_id' => $this->owner->id,
        ]);
        $a = $this->audioPlayable('A');
        $b = $this->audioPlayable('B');
        $playlist->addPlayable($a);
        $playlist->addPlayable($b);

        foreach (range(1, 5) as $_) {
            $playlist->reorder([$b->id, $a->id]);
        }

        $ranks = DB::table('playlist_playable')
            ->where('playlist_id', $playlist->id)
            ->orderBy('rank')
            ->pluck('rank')
            ->all();
        $this->assertSame([0, 1], $ranks);
    }

    public function test_reorder_cannot_pull_in_an_unrelated_playable(): void
    {
        $playlist = Playlist::create([
            'name' => 'Mine',
            'user_id' => $this->owner->id,
        ]);
        $mine = $this->audioPlayable('Mine');
        $theirs = Playable::forAudioPost(
            AudioPost::create([
                'name' => 'Theirs',
                'src_url' => 'https://example.test/t.mp3',
                'user_id' => $this->stranger->id,
            ])
        );

        $playlist->addPlayable($mine);

        $this->actingAs($this->owner)
            ->postJson("/api/playlists/{$playlist->id}/reorder", [
                'playable_ids' => [$theirs->id, $mine->id],
            ])
            ->assertOk();

        // The foreign playable must not have joined.
        $this->assertSame([$mine->id], $playlist->playables()->pluck('playables.id')->all());
    }

    public function test_reorder_keeps_members_the_client_omitted(): void
    {
        $playlist = Playlist::create([
            'name' => 'Mix',
            'user_id' => $this->owner->id,
        ]);
        $a = $this->audioPlayable('A');
        $b = $this->audioPlayable('B');
        $c = $this->audioPlayable('C');
        foreach ([$a, $b, $c] as $p) {
            $playlist->addPlayable($p);
        }

        // Client sends only one id; the rest must survive.
        $this->actingAs($this->owner)
            ->postJson("/api/playlists/{$playlist->id}/reorder", [
                'playable_ids' => [$c->id],
            ])
            ->assertOk();

        $this->assertSame(3, $playlist->playables()->count());
        $this->assertSame($c->id, $playlist->playables()->pluck('playables.id')->first());
    }

    public function test_adding_an_unknown_playable_is_rejected(): void
    {
        $playlist = Playlist::create([
            'name' => 'Mix',
            'user_id' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->postJson("/api/playlists/{$playlist->id}/playables", [
                'playable_id' => 999999,
            ])
            ->assertStatus(422);
    }

    public function test_a_stranger_cannot_modify_another_users_playlist(): void
    {
        $playlist = Playlist::create([
            'name' => 'Private',
            'user_id' => $this->owner->id,
        ]);
        $playable = $this->audioPlayable();

        $this->actingAs($this->stranger)
            ->postJson("/api/playlists/{$playlist->id}/playables", [
                'playable_id' => $playable->id,
            ])
            ->assertNotFound();

        $this->assertSame(0, $playlist->playables()->count());
    }

    public function test_the_library_lists_only_the_callers_playables(): void
    {
        $this->audioPlayable('Mine');
        Playable::forAudioPost(
            AudioPost::create([
                'name' => 'Theirs',
                'src_url' => 'https://example.test/t.mp3',
                'user_id' => $this->stranger->id,
            ])
        );

        $response = $this->actingAs($this->owner)->getJson('/api/playlists/library');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_deleting_a_playlist_detaches_its_members(): void
    {
        $playlist = Playlist::create([
            'name' => 'Temp',
            'user_id' => $this->owner->id,
        ]);
        $playlist->addPlayable($this->audioPlayable());

        $this->actingAs($this->owner)
            ->deleteJson("/api/playlists/{$playlist->id}")
            ->assertOk();

        $this->assertDatabaseMissing('playlist_playable', [
            'playlist_id' => $playlist->id,
        ]);
        // The playable itself survives; only the membership is gone.
        $this->assertSame(1, Playable::count());
    }

    public function test_a_video_playable_can_play_audio_alone(): void
    {
        $video = VideoPost::create([
            'name' => 'Clip',
            'src_url' => 'https://example.test/v.mp4',
            'user_id' => $this->owner->id,
        ]);
        $playable = app(PlayableFactory::class)->forVideo($video, 'video/full/v.mp4');

        // The audio side is what a player falls back to when video is off.
        $this->assertTrue($playable->hasVideo());
        $this->assertTrue($playable->hasAudio());
        $this->assertSame(
            'https://example.test/v.mp4',
            $playable->audioPost->src_url
        );
    }
}
<?php

namespace Tests\Feature;

use App\Models\AudioPost;
use App\Models\Devotional;
use App\Models\Event;
use App\Models\Feed;
use App\Models\Hierarchy;
use App\Models\Post;
use App\Models\User;
use App\Models\VideoPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_contains_posts_from_followed_users(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();
        $stranger = User::factory()->create();

        // Follow the user.
        $follower->following()->attach($followed->id);

        // Create posts from followed user and stranger.
        $followedPost = Post::factory()->create(['user_id' => $followed->id]);
        $strangerPost = Post::factory()->create(['user_id' => $stranger->id]);

        // Create feed entries.
        Feed::create(['parentable_type' => 'post', 'postable_type' => 'user', 'postable_id' => $followed->id, 'parentable_id' => $followedPost->id]);
        Feed::create(['parentable_type' => 'post', 'postable_type' => 'user', 'postable_id' => $stranger->id, 'parentable_id' => $strangerPost->id]);

        $response = $this->actingAs($follower)->getJson('/api/feed');
        $response->assertStatus(200);

        $feedIds = collect($response->json('data'))->pluck('parentable_id');
        $this->assertContains($followedPost->id, $feedIds);
        $this->assertNotContains($strangerPost->id, $feedIds);
    }

    public function test_feed_contains_devotionals(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $follower->following()->attach($followed->id);

        $devotional = Devotional::factory()->create(['user_id' => $followed->id]);
        Feed::create(['parentable_type' => 'devotional', 'postable_type' => 'user', 'postable_id' => $followed->id, 'parentable_id' => $devotional->id]);

        $response = $this->actingAs($follower)->getJson('/api/feed?type=devotional');
        $response->assertStatus(200);

        $feedIds = collect($response->json('data'))->pluck('parentable_id');
        $this->assertContains($devotional->id, $feedIds);
    }

    public function test_feed_filters_by_type(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $follower->following()->attach($followed->id);

        $audio = AudioPost::factory()->create(['user_id' => $followed->id]);
        $video = VideoPost::factory()->create(['user_id' => $followed->id]);

        Feed::create(['parentable_type' => 'audio', 'postable_type' => 'user', 'postable_id' => $followed->id, 'parentable_id' => $audio->id]);
        Feed::create(['parentable_type' => 'video', 'postable_type' => 'user', 'postable_id' => $followed->id, 'parentable_id' => $video->id]);

        $response = $this->actingAs($follower)->getJson('/api/feed?type=audio');
        $response->assertStatus(200);

        $types = collect($response->json('data'))->pluck('parentable_type');
        $this->assertContains('audio', $types);
        $this->assertNotContains('video', $types);
    }

    public function test_hierarchy_reorder(): void
    {
        $user = User::factory()->create();

        $h1 = Hierarchy::create(['name' => 'First', 'rank' => 1, 'user_id' => $user->id]);
        $h2 = Hierarchy::create(['name' => 'Second', 'rank' => 2, 'user_id' => $user->id]);
        $h3 = Hierarchy::create(['name' => 'Third', 'rank' => 3, 'user_id' => $user->id]);

        // Reorder: third, first, second.
        $response = $this->actingAs($user)->postJson('/api/hierarchies/reorder', [
            'ids' => [$h3->id, $h1->id, $h2->id],
        ]);
        $response->assertStatus(200);

        $h1->refresh();
        $h2->refresh();
        $h3->refresh();

        $this->assertEquals(2, $h1->rank);
        $this->assertEquals(3, $h2->rank);
        $this->assertEquals(1, $h3->rank);
    }

    public function test_hierarchy_create_auto_assigns_rank(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/hierarchies', ['name' => 'Role A']);
        $this->actingAs($user)->postJson('/api/hierarchies', ['name' => 'Role B']);

        $hierarchies = Hierarchy::orderBy('rank')->get();
        $this->assertEquals(1, $hierarchies[0]->rank);
        $this->assertEquals(2, $hierarchies[1]->rank);
    }

    public function test_hierarchy_update_works(): void
    {
        $user = User::factory()->create();
        $hierarchy = Hierarchy::create(['name' => 'Old Name', 'rank' => 1, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->putJson("/api/hierarchies/{$hierarchy->id}", [
            'name' => 'New Name',
            'rank' => 5,
        ]);
        $response->assertStatus(200);

        $hierarchy->refresh();
        $this->assertEquals('New Name', $hierarchy->name);
        $this->assertEquals(5, $hierarchy->rank);
    }

    public function test_hierarchy_create_multi_validates(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/hierarchies-multi', [
            ['name' => 'Valid'],
            ['name' => ''],
        ]);
        $response->assertStatus(422);
    }
}

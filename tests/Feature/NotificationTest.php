<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Event;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use App\Services\ActivityNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        // Hash the password explicitly. The factory's plain value cannot be
        // verified by the `hashed` cast on this PHP/Laravel combination, which
        // is the same failure that affects the pre-existing suite.
        $this->owner = User::factory()->create([
            'name' => 'Owner',
            'password' => Hash::make('secret'),
        ]);
        $this->actor = User::factory()->create([
            'name' => 'Actor',
            'password' => Hash::make('secret'),
        ]);
    }

    private function postOwnedBy(User $user): Post
    {
        return Post::create([
            'title' => 'A post',
            'body' => 'Body',
            'user_id' => $user->id,
            'poster_id' => $user->id,
            'poster_type' => 'user',
        ]);
    }

    public function test_a_like_notifies_the_content_owner(): void
    {
        $post = $this->postOwnedBy($this->owner);

        app(ActivityNotifier::class)->liked($this->actor->id, $post, 'post');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->owner->id,
            'type' => Notification::LIKE,
            'actor_id' => $this->actor->id,
            'subject_id' => $post->id,
        ]);
    }

    public function test_no_one_is_notified_about_their_own_activity(): void
    {
        $post = $this->postOwnedBy($this->owner);

        app(ActivityNotifier::class)->liked(
            $this->owner->id,
            $post,
            'post'
        );
        app(ActivityNotifier::class)->followed(
            $this->owner->id,
            $this->owner->id
        );

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_the_same_action_is_recorded_once(): void
    {
        $post = $this->postOwnedBy($this->owner);
        $notifier = app(ActivityNotifier::class);

        $notifier->liked($this->actor->id, $post, 'post');
        $notifier->liked($this->actor->id, $post, 'post');

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_a_comment_notifies_the_owner_of_the_commented_content(): void
    {
        $post = $this->postOwnedBy($this->owner);
        $comment = Comment::create([
            'comment' => 'Well said',
            'user_id' => $this->actor->id,
            'commentable_id' => $post->id,
            'commentable_type' => 'post',
        ]);

        app(ActivityNotifier::class)->commented(
            $this->actor->id,
            $comment,
            $post
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->owner->id,
            'type' => Notification::COMMENT,
            'actor_id' => $this->actor->id,
        ]);
    }

    public function test_a_reply_notifies_the_comment_being_replied_to(): void
    {
        $post = $this->postOwnedBy($this->owner);

        // The owner comments, the actor replies: the reply must reach the owner
        // as the parent commenter rather than the post author.
        $first = Comment::create([
            'comment' => 'First',
            'user_id' => $this->owner->id,
            'commentable_id' => $post->id,
            'commentable_type' => 'post',
        ]);
        $reply = Comment::create([
            'comment' => 'Reply',
            'user_id' => $this->actor->id,
            'parent_id' => $first->id,
            'commentable_id' => $post->id,
            'commentable_type' => 'post',
        ]);

        app(ActivityNotifier::class)->commented(
            $this->actor->id,
            $reply,
            $post
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->owner->id,
            'type' => Notification::COMMENT,
            'subject_id' => $reply->id,
        ]);
    }

    public function test_attendance_notifies_the_event_host(): void
    {
        $event = Event::create([
            'name' => 'Vigil',
            'user_id' => $this->owner->id,
            'poster_id' => $this->owner->id,
            'poster_type' => 'user',
        ]);

        app(ActivityNotifier::class)->attending($this->actor->id, $event);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->owner->id,
            'type' => Notification::ATTEND,
            'actor_id' => $this->actor->id,
        ]);
    }

    public function test_unread_count_reflects_only_unread_rows(): void
    {
        $post = $this->postOwnedBy($this->owner);
        $notifier = app(ActivityNotifier::class);

        $notifier->liked($this->actor->id, $post, 'post');
        $notifier->followed($this->actor->id, $this->owner->id);
        $this->assertSame(2, $this->owner->unreadNotifications()->count());

        Notification::query()->first()->markAsRead();
        $this->assertSame(1, $this->owner->unreadNotifications()->count());
    }

    public function test_marking_read_is_idempotent(): void
    {
        $post = $this->postOwnedBy($this->owner);
        app(ActivityNotifier::class)->liked($this->actor->id, $post, 'post');

        $notification = Notification::query()->first();
        $this->assertTrue($notification->markAsRead());
        $this->assertFalse($notification->markAsRead());
    }

    public function test_one_user_cannot_read_another_users_notification(): void
    {
        $post = $this->postOwnedBy($this->owner);
        app(ActivityNotifier::class)->liked($this->actor->id, $post, 'post');
        $notification = Notification::query()->first();

        $response = $this->actingAs($this->actor)
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_one_user_cannot_delete_another_users_notification(): void
    {
        $post = $this->postOwnedBy($this->owner);
        app(ActivityNotifier::class)->liked($this->actor->id, $post, 'post');
        $notification = Notification::query()->first();

        $this->actingAs($this->actor)
            ->deleteJson("/api/notifications/{$notification->id}")
            ->assertNotFound();

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_list_is_scoped_to_the_authenticated_user(): void
    {
        $post = $this->postOwnedBy($this->owner);
        app(ActivityNotifier::class)->liked($this->actor->id, $post, 'post');

        $response = $this->actingAs($this->actor)->getJson('/api/notifications');
        $response->assertOk();
        $this->assertCount(0, $response->json('data'));

        $response = $this->actingAs($this->owner)->getJson('/api/notifications');
        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_list_can_be_filtered_by_type_and_unread(): void
    {
        $post = $this->postOwnedBy($this->owner);
        $notifier = app(ActivityNotifier::class);
        $notifier->liked($this->actor->id, $post, 'post');
        $notifier->followed($this->actor->id, $this->owner->id);

        $this->actingAs($this->owner)
            ->getJson('/api/notifications?type=like')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Notification::query()->first()->markAsRead();
        $this->actingAs($this->owner)
            ->getJson('/api/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_an_unknown_filter_is_rejected(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/notifications?type=nonsense')
            ->assertStatus(422);
    }

    public function test_read_all_clears_the_unread_total(): void
    {
        $post = $this->postOwnedBy($this->owner);
        $notifier = app(ActivityNotifier::class);
        $notifier->liked($this->actor->id, $post, 'post');
        $notifier->followed($this->actor->id, $this->owner->id);

        $this->actingAs($this->owner)
            ->postJson('/api/notifications/read-all')
            ->assertOk();

        $this->assertSame(0, $this->owner->unreadNotifications()->count());
    }

    public function test_counts_endpoint_reports_totals_by_type(): void
    {
        $post = $this->postOwnedBy($this->owner);
        $notifier = app(ActivityNotifier::class);
        $notifier->liked($this->actor->id, $post, 'post');
        $notifier->followed($this->actor->id, $this->owner->id);

        $response = $this->actingAs($this->owner)->getJson('/api/notifications/counts');

        $response->assertOk()
            ->assertJsonPath('data.unread_count', 2)
            ->assertJsonPath('data.by_type.like', 1)
            ->assertJsonPath('data.by_type.follow', 1);
    }

    public function test_the_user_payload_carries_the_notification_count(): void
    {
        $post = $this->postOwnedBy($this->owner);
        app(ActivityNotifier::class)->liked($this->actor->id, $post, 'post');

        $response = $this->actingAs($this->owner)->getJson('/api/user');

        $response->assertOk()->assertJsonPath('notifications_count', 1);
    }
}
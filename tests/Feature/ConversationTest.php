<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Event;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private User $alice;
    private User $bob;
    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->makeUser('Alice');
        $this->bob = $this->makeUser('Bob');
        $this->stranger = $this->makeUser('Stranger');
    }

    private function makeUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            // Hashed explicitly: the factory's plain value cannot be verified by
            // the `hashed` cast on this PHP/Laravel combination.
            'password' => Hash::make('secret'),
        ]);
    }

    private function eventWithAttendees(User ...$attendees): Event
    {
        $event = Event::factory()->create(['user_id' => $this->alice->id]);
        $event->attendees()->attach(collect($attendees)->map->id->all());
        return $event->fresh();
    }

    private function openPrivate(User $from, User $to): Conversation
    {
        $response = $this->actingAs($from)
            ->postJson('/api/conversations', ['user_id' => $to->id]);
        $response->assertSuccessful();
        return Conversation::find($response->json('data.id'));
    }

    // ---------------------------------------------------------------- private

    public function test_a_private_thread_opens_between_two_users(): void
    {
        $response = $this->actingAs($this->alice)->postJson('/api/conversations', [
            'user_id' => $this->bob->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.type', 'private');
        $this->assertCount(2, $response->json('data.participants'));
    }

    public function test_opening_the_same_thread_twice_returns_the_same_one(): void
    {
        $first = $this->openPrivate($this->alice, $this->bob);
        $second = $this->openPrivate($this->alice, $this->bob);

        // A retried request must not fork the conversation.
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Conversation::count());
    }

    public function test_the_pair_is_unordered_so_both_sides_reuse_one_thread(): void
    {
        $first = $this->openPrivate($this->alice, $this->bob);
        $second = $this->openPrivate($this->bob, $this->alice);

        $this->assertSame($first->id, $second->id);
    }

    public function test_a_private_thread_is_named_after_the_other_person(): void
    {
        $response = $this->actingAs($this->alice)
            ->postJson('/api/conversations', ['user_id' => $this->bob->id]);

        // Bob has no title of his own to show Alice.
        $response->assertJsonPath('data.title', 'Bob');
    }

    public function test_a_user_cannot_open_a_thread_with_themselves(): void
    {
        $this->actingAs($this->alice)
            ->postJson('/api/conversations', ['user_id' => $this->alice->id])
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------ group

    public function test_an_attendee_can_open_the_event_group(): void
    {
        $event = $this->eventWithAttendees($this->alice, $this->bob);

        $response = $this->actingAs($this->alice)
            ->postJson('/api/conversations', ['event_id' => $event->id]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'event')
            ->assertJsonPath('data.event_id', $event->id)
            ->assertJsonPath('data.title', $event->name);
    }

    public function test_one_group_is_created_per_event(): void
    {
        $event = $this->eventWithAttendees($this->alice, $this->bob);

        $first = $this->actingAs($this->alice)
            ->postJson('/api/conversations', ['event_id' => $event->id]);
        $second = $this->actingAs($this->bob)
            ->postJson('/api/conversations', ['event_id' => $event->id]);

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame(1, Conversation::where('type', 'event')->count());
    }

    public function test_someone_who_did_not_join_the_event_cannot_read_its_chat(): void
    {
        $event = $this->eventWithAttendees($this->alice);
        $response = $this->actingAs($this->alice)
            ->postJson('/api/conversations', ['event_id' => $event->id]);

        $this->actingAs($this->stranger)
            ->postJson('/api/conversations', ['event_id' => $event->id])
            ->assertStatus(403);

        $this->actingAs($this->stranger)
            ->getJson('/api/conversations/' . $response->json('data.id'))
            ->assertNotFound();
    }

    public function test_the_organiser_may_open_the_group_without_attending(): void
    {
        // Alice owns the event but is not in its attendee list.
        $event = Event::factory()->create(['user_id' => $this->alice->id]);
        $event->attendees()->attach([$this->bob->id]);

        $this->actingAs($this->alice)
            ->postJson('/api/conversations', ['event_id' => $event->id])
            ->assertCreated();
    }

    public function test_late_attendees_are_pulled_into_the_existing_group(): void
    {
        $event = $this->eventWithAttendees($this->alice);
        $this->actingAs($this->alice)
            ->postJson('/api/conversations', ['event_id' => $event->id])
            ->assertCreated();

        // Bob joins the event only after the thread already exists.
        $event->attendees()->syncWithoutDetaching([$this->bob->id]);

        $response = $this->actingAs($this->alice)
            ->postJson('/api/conversations', ['event_id' => $event->id]);

        $ids = collect($response->json('data.participants'))->pluck('id');
        $this->assertTrue($ids->contains($this->bob->id));
    }

    // --------------------------------------------------------------- messages

    public function test_a_participant_can_send_and_read_messages(): void
    {
        $thread = $this->openPrivate($this->alice, $this->bob);

        $sent = $this->actingAs($this->alice)
            ->postJson("/api/conversations/{$thread->id}/messages", [
                'body' => 'See you at the meetup',
            ]);

        $sent->assertCreated()->assertJsonPath('data.body', 'See you at the meetup');

        $this->actingAs($this->bob)
            ->getJson("/api/conversations/{$thread->id}")
            ->assertOk()
            ->assertJsonPath('data.0.body', 'See you at the meetup');
    }

    public function test_a_message_is_ordered_oldest_first(): void
    {
        $thread = $this->openPrivate($this->alice, $this->bob);

        foreach (['first', 'second', 'third'] as $body) {
            $this->actingAs($this->alice)
                ->postJson("/api/conversations/{$thread->id}/messages", ['body' => $body])
                ->assertCreated();
        }

        $bodies = collect(
            $this->actingAs($this->bob)
                ->getJson("/api/conversations/{$thread->id}")
                ->json('data')
        )->pluck('body')->all();

        $this->assertSame(['first', 'second', 'third'], $bodies);
    }

    public function test_an_empty_message_is_rejected(): void
    {
        $thread = $this->openPrivate($this->alice, $this->bob);

        $this->actingAs($this->alice)
            ->postJson("/api/conversations/{$thread->id}/messages", ['body' => ''])
            ->assertStatus(422);

        $this->assertSame(0, Message::count());
    }

    public function test_a_stranger_cannot_read_or_post_to_a_thread(): void
    {
        $thread = $this->openPrivate($this->alice, $this->bob);
        $this->actingAs($this->alice)
            ->postJson("/api/conversations/{$thread->id}/messages", ['body' => 'private']);

        $this->actingAs($this->stranger)
            ->getJson("/api/conversations/{$thread->id}")
            ->assertNotFound();

        $this->actingAs($this->stranger)
            ->postJson("/api/conversations/{$thread->id}/messages", ['body' => 'sneaky'])
            ->assertNotFound();

        $this->assertSame(1, Message::count());
    }

    public function test_an_unknown_thread_is_not_found(): void
    {
        $this->actingAs($this->alice)
            ->getJson('/api/conversations/999999')
            ->assertNotFound();
    }

    // ------------------------------------------------------------ unread count

    public function test_the_sender_does_not_see_their_own_message_as_unread(): void
    {
        $thread = $this->openPrivate($this->alice, $this->bob);

        $this->actingAs($this->alice)
            ->postJson("/api/conversations/{$thread->id}/messages", ['body' => 'mine']);

        $this->actingAs($this->alice)
            ->getJson('/api/conversations/unread')
            ->assertOk()
            ->assertJsonPath('data.unread', 0);
    }

    public function test_a_recipient_sees_the_message_as_unread_until_they_open_it(): void
    {
        $thread = $this->openPrivate($this->alice, $this->bob);

        $this->actingAs($this->alice)
            ->postJson("/api/conversations/{$thread->id}/messages", ['body' => 'ping']);

        $this->actingAs($this->bob)
            ->getJson('/api/conversations/unread')
            ->assertJsonPath('data.unread', 1);

        $this->actingAs($this->bob)
            ->postJson("/api/conversations/{$thread->id}/read")
            ->assertOk();

        $this->actingAs($this->bob)
            ->getJson('/api/conversations/unread')
            ->assertJsonPath('data.unread', 0);
    }

    public function test_a_new_message_after_reading_shows_up_again(): void
    {
        $thread = $this->openPrivate($this->alice, $this->bob);
        $this->actingAs($this->alice)
            ->postJson("/api/conversations/{$thread->id}/messages", ['body' => 'one']);
        $this->actingAs($this->bob)
            ->postJson("/api/conversations/{$thread->id}/read");

        $this->actingAs($this->alice)
            ->postJson("/api/conversations/{$thread->id}/messages", ['body' => 'two']);

        $this->actingAs($this->bob)
            ->getJson('/api/conversations/unread')
            ->assertJsonPath('data.unread', 1);
    }

    public function test_the_list_carries_the_preview_and_unread_count(): void
    {
        $thread = $this->openPrivate($this->alice, $this->bob);
        $this->actingAs($this->alice)
            ->postJson("/api/conversations/{$thread->id}/messages", ['body' => 'latest words']);

        $response = $this->actingAs($this->bob)->getJson('/api/conversations');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('latest words', $response->json('data.0.last_message.body'));
        $this->assertSame(1, $response->json('data.0.unread_count'));
        $this->assertSame('Alice', $response->json('data.0.title'));
    }

    public function test_the_list_only_contains_the_callers_threads(): void
    {
        $this->openPrivate($this->alice, $this->bob);

        $this->actingAs($this->stranger)
            ->getJson('/api/conversations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_group_thread_and_a_private_thread_coexist(): void
    {
        $event = $this->eventWithAttendees($this->alice, $this->bob);

        $this->openPrivate($this->alice, $this->bob);
        $this->actingAs($this->alice)
            ->postJson('/api/conversations', ['event_id' => $event->id])
            ->assertCreated();

        $this->actingAs($this->alice)
            ->getJson('/api/conversations')
            ->assertJsonCount(2, 'data');
    }
}
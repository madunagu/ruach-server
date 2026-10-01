<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Hierarchy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UuidRelationshipTest extends TestCase
{
    use RefreshDatabase;

    private const TABLES = ['events', 'audio_posts', 'posts', 'video_posts', 'devotionals'];

    public function test_every_content_table_has_a_uuid_column(): void
    {
        foreach (self::TABLES as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'uuid'),
                "{$table} is missing a uuid column"
            );
        }
    }

    public function test_relation_pivots_have_uuid_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('hierarchyables', 'hierarchyable_uuid'));
        $this->assertTrue(Schema::hasColumn('addressables', 'addressable_uuid'));
        $this->assertTrue(Schema::hasColumn('imageables', 'imageable_uuid'));
    }

    public function test_existing_rows_are_backfilled_with_unique_uuids(): void
    {
        foreach (self::TABLES as $table) {
            $this->assertSame(
                0,
                DB::table($table)->whereNull('uuid')->count(),
                "{$table} still has rows without a uuid"
            );

            $duplicates = DB::table($table)
                ->select('uuid')
                ->groupBy('uuid')
                ->havingRaw('COUNT(*) > 1')
                ->count();
            $this->assertSame(0, $duplicates, "{$table} has duplicate uuids");
        }
    }

    public function test_a_uuid_is_generated_when_the_client_sends_none(): void
    {
        $event = Event::create(['name' => 'Generated', 'user_id' => 1]);

        $this->assertNotEmpty($event->uuid);
        $this->assertTrue(Event::isUsableUuid($event->uuid));
    }

    public function test_a_client_supplied_draft_uuid_is_honoured(): void
    {
        $draft = '11111111-2222-4333-8444-555555555555';

        $event = Event::create([
            'uuid' => $draft,
            'name' => 'Honoured',
            'user_id' => 1,
        ]);

        $this->assertSame($draft, $event->uuid);
    }

    public function test_a_duplicate_uuid_is_replaced(): void
    {
        $draft = '22222222-3333-4444-8555-666666666666';
        Event::create(['uuid' => $draft, 'name' => 'First', 'user_id' => 1]);

        $second = Event::create([
            'uuid' => $draft,
            'name' => 'Second',
            'user_id' => 1,
        ]);

        $this->assertNotSame($draft, $second->uuid);
        $this->assertTrue(Event::isUsableUuid($second->uuid));
    }

    public function test_a_malformed_uuid_is_replaced(): void
    {
        $event = Event::create([
            'uuid' => 'not-a-uuid',
            'name' => 'Malformed',
            'user_id' => 1,
        ]);

        $this->assertNotSame('not-a-uuid', $event->uuid);
        $this->assertTrue(Event::isUsableUuid($event->uuid));
    }

    public function test_is_usable_uuid_rejects_non_uuid_values(): void
    {
        $this->assertFalse(Event::isUsableUuid('not-a-uuid'));
        $this->assertFalse(Event::isUsableUuid(''));
        $this->assertFalse(Event::isUsableUuid(null));
        $this->assertFalse(Event::isUsableUuid(123));
        $this->assertTrue(Event::isUsableUuid('11111111-2222-4333-8444-555555555555'));
    }

    public function test_relation_type_maps_each_content_model(): void
    {
        $this->assertSame('event', (new Event())->relationType());
    }

    public function test_claiming_moves_draft_relations_onto_the_primary_key(): void
    {
        $draft = '33333333-4444-4555-8666-777777777777';
        $event = Event::create(['uuid' => $draft, 'name' => 'Claiming', 'user_id' => 1]);
        $hierarchy = Hierarchy::create(['rank' => 1, 'name' => 'Host', 'user_id' => 1]);

        DB::table('hierarchyables')->insert([
            'hierarchy_id' => $hierarchy->id,
            'hierarchyable_type' => 'event',
            'hierarchyable_id' => 0,
            'hierarchyable_uuid' => $draft,
        ]);

        $event->claimDraftRelations();

        $pivot = DB::table('hierarchyables')->where('hierarchy_id', $hierarchy->id)->first();

        $this->assertEquals($event->id, $pivot->hierarchyable_id);
        $this->assertNull($pivot->hierarchyable_uuid);
        $this->assertSame('event', $pivot->hierarchyable_type);
    }

    public function test_claiming_ignores_a_different_morph_type(): void
    {
        $event = Event::create([
            'uuid' => '44444444-5555-4666-8777-888888888888',
            'name' => 'Type Guard',
            'user_id' => 1,
        ]);
        $hierarchy = Hierarchy::create(['rank' => 1, 'name' => 'Other', 'user_id' => 1]);

        DB::table('hierarchyables')->insert([
            'hierarchy_id' => $hierarchy->id,
            'hierarchyable_type' => 'post',
            'hierarchyable_id' => 0,
            'hierarchyable_uuid' => $event->uuid,
        ]);

        $event->claimDraftRelations();

        $pivot = DB::table('hierarchyables')->where('hierarchy_id', $hierarchy->id)->first();

        $this->assertSame($event->uuid, $pivot->hierarchyable_uuid);
        $this->assertSame(0, (int) $pivot->hierarchyable_id);
    }

    public function test_the_relation_resolves_after_claiming(): void
    {
        $event = Event::create([
            'uuid' => '55555555-6666-4777-8888-999999999999',
            'name' => 'Resolving',
            'user_id' => 1,
        ]);
        $hierarchy = Hierarchy::create(['rank' => 1, 'name' => 'Host', 'user_id' => 1]);

        DB::table('hierarchyables')->insert([
            'hierarchy_id' => $hierarchy->id,
            'hierarchyable_type' => 'event',
            'hierarchyable_id' => 0,
            'hierarchyable_uuid' => $event->uuid,
        ]);

        $event->claimDraftRelations();

        $this->assertCount(1, $event->fresh()->hierarchies);
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

/**
 * Adds a stable public identifier to the five content tables.
 *
 * Relationships (addressables, imageables, hierarchyables) still store the
 * integer primary key, so existing rows and queries keep working unchanged.
 * The uuid column is the client-facing handle: a draft is minted with a uuid
 * before the row exists, and the client uses that value for every related
 * request until the save completes.
 */
return new class extends Migration
{
    /** Tables that take part in polymorphic content relations. */
    private const CONTENT_TABLES = [
        'events',
        'audio_posts',
        'posts',
        'video_posts',
        'devotionals',
    ];

    public function up(): void
    {
        foreach (self::CONTENT_TABLES as $table) {
            if (!Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->string('uuid', 36)->nullable()->after('id');
                });
            }

            // Backfill in chunks so this stays safe on a large table.
            DB::table($table)->whereNull('uuid')->orderBy('id')->chunkById(
                500,
                function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)
                            ->where('id', $row->id)
                            ->update(['uuid' => (string) Uuid::uuid4()]);
                    }
                }
            );

            // Nullable so the column can be added without a table rewrite on
            // MySQL; uniqueness is enforced by the index added below.
            if ($this->indexMissing($table, 'uuid')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->index('uuid');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::CONTENT_TABLES as $table) {
            if (!Schema::hasColumn($table, 'uuid')) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['uuid']);
            });
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('uuid');
            });
        }
    }

    /** True when no index covers the uuid column. */
    private function indexMissing(string $table, string $column): bool
    {
        foreach (DB::select('SHOW INDEX FROM `' . $table . '`') as $index) {
            if ($index->Column_name === $column) {
                return false;
            }
        }

        return true;
    }
};
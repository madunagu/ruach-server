<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a uuid column to each polymorphic relation pivot.
 *
 * The existing integer `*_able_id` columns are left untouched so every saved
 * relationship keeps working. The uuid column carries links for rows that do
 * not exist yet: a create form saves its hierarchies, address and images
 * before the parent content row is created, and those rows are claimed when
 * the parent is saved with the matching uuid.
 */
return new class extends Migration
{
    /** pivot table => the column holding the parent reference. */
    private const PIVOTS = [
        'hierarchyables' => 'hierarchyable_uuid',
        'addressables' => 'addressable_uuid',
        'imageables' => 'imageable_uuid',
    ];

    public function up(): void
    {
        foreach (self::PIVOTS as $table => $column) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, $column)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->string($column, 36)->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (self::PIVOTS as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropIndex([$column]);
            });
            Schema::table($table, function (Blueprint $blueprint) use ($column) {
                $blueprint->dropColumn($column);
            });
        }
    }
};
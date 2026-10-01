<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

/**
 * Gives a model a stable public identifier.
 *
 * The id stays the relational key so nothing about the existing schema or
 * queries changes. `uuid` is what clients hold: a draft is assigned one
 * before the row exists, and the server keeps whatever the client sent so the
 * draft and the saved row can be correlated.
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            // Respect a client-supplied uuid (draft correlation) but never
            // accept a blank or duplicated value.
            $uuid = $model->getAttribute('uuid');

            if (empty($uuid) || ! static::isUsableUuid($uuid)) {
                $model->setAttribute('uuid', static::generateUuid());
                return;
            }

            if (static::where('uuid', $uuid)->exists()) {
                $model->setAttribute('uuid', static::generateUuid());
            }
        });
    }

    /** Assigns a fresh uuid when the model is created. */
    public static function generateUuid(): string
    {
        do {
            $candidate = (string) Uuid::uuid4();
        } while (static::where('uuid', $candidate)->exists());

        return $candidate;
    }

    /** True when the value looks like a UUID we are willing to store. */
    public static function isUsableUuid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }

    /** Scope for resolving content by its public identifier. */
    public function scopeWhereUuid($query, string $uuid)
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Claims relations that were created against this model's uuid before the
     * row existed.
     *
     * A create form saves its hierarchies, address and images first, linking
     * them by draft uuid. Once the parent is inserted those pivot rows still
     * point at a uuid, so they are moved onto the real primary key here.
     */
    public function claimDraftRelations(): void
    {
        $uuid = $this->uuid;
        if (empty($uuid)) {
            return;
        }

        // pivot table => [uuid column, type column, id column]
        $pivots = [
            'hierarchyables' => ['hierarchyable_uuid', 'hierarchyable_type', 'hierarchyable_id'],
            'addressables' => ['addressable_uuid', 'addressable_type', 'addressable_id'],
            'imageables' => ['imageable_uuid', 'imageable_type', 'imageable_id'],
        ];

        foreach ($pivots as $table => $columns) {
            [$uuidColumn, $typeColumn, $idColumn] = $columns;
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $uuidColumn)) {
                continue;
            }

            DB::table($table)
                ->where($uuidColumn, $uuid)
                ->where($typeColumn, $this->relationType())
                ->update([
                    $idColumn => $this->getKey(),
                    $uuidColumn => null,
                ]);
        }
    }

    /** Morph alias this model is stored under in the relation pivots. */
    public function relationType(): string
    {
        $map = [
            'App\Models\Event' => 'event',
            'App\Models\AudioPost' => 'audio',
            'App\Models\VideoPost' => 'video',
            'App\Models\Post' => 'post',
            'App\Models\Devotional' => 'devotional',
        ];

        return $map[static::class] ?? strtolower(class_basename($this));
    }
}
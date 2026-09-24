<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Drop-in replacement for nicolaslopezj/searchable (abandoned, broken on
 * Laravel 10+). Provides a `search($query)` query scope driven by the
 * model's `$searchable['columns']` priority map.
 *
 * Relevance: sum of per-column weights for LIKE matches, ordered desc.
 * Falls back gracefully to plain LIKE OR-chains on drivers without
 * full-text indexes (SQLite/MySQL). Add FULLTEXT indexes later for scale.
 */
trait Searchable
{
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        /** @var array<string,int> $columns */
        $columns = $this->searchable['columns'] ?? [];
        if (empty($columns)) {
            return $query;
        }

        $words = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);
        $table = $this->getTable();

        // Relevance expression: SUM(weight) for matched columns.
        $cases = [];
        $bindings = [];
        foreach ($columns as $col => $weight) {
            $weight = (int) $weight;
            // $col is like "audio_posts.name" — qualify with table if bare.
            $qualified = str_contains($col, '.') ? $col : "{$table}.{$col}";
            foreach ($words as $w) {
                $cases[] = "(CASE WHEN {$qualified} LIKE ? THEN {$weight} ELSE 0 END)";
                $bindings[] = "%{$w}%";
            }
        }
        $relevance = implode(' + ', $cases);

        // Constrain to rows matching at least one word in any column,
        // then order by computed relevance.
        $query->where(function (Builder $q) use ($columns, $words) {
            foreach ($columns as $col => $_) {
                $qualified = str_contains($col, '.') ? $col : $this->getTable().".{$col}";
                foreach ($words as $w) {
                    $q->orWhere($qualified, 'LIKE', "%{$w}%");
                }
            }
        });

        // selectRaw without wiping an existing select
        $query->selectRaw("{$table}.*, ({$relevance}) as search_relevance", $bindings)
            ->orderByDesc('search_relevance');

        return $query;
    }
}

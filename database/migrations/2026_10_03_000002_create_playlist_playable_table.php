<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordered membership of a playlist.
 *
 * `rank` carries the order so reordering is a single update per moved row
 * rather than a rebuild of the whole list. The unique index on
 * (playlist_id, playable_id) keeps a playable from appearing twice; the index
 * on (playlist_id, rank) keeps reads ordered.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('playlist_playable')) {
            return;
        }

        Schema::create('playlist_playable', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('playlist_id')->index();
            $table->unsignedBigInteger('playable_id')->index();
            $table->integer('rank')->default(0);

            // One playable appears once per playlist.
            $table->unique(['playlist_id', 'playable_id']);
            $table->index(['playlist_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_playable');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per playable item, independent of whether it has video.
 *
 * A video upload also creates an AudioPost (the same recording), but the two
 * are one thing to the user: one item in a playlist, one entry in history, one
 * set of comments. So a Playable points at both and the video side is optional.
 * That is what makes the player's "video off" toggle a matter of reading the
 * audio source alone, rather than swapping between two separate records.
 *
 * Exactly one of audio_post_id / video_post_id is required, and a video
 * playable carries both.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The original playlists migration predates SoftDeletes, so add the
        // column the model now expects.
        if (Schema::hasTable('playlists') && !Schema::hasColumn('playlists', 'deleted_at')) {
            Schema::table('playlists', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('playables')) {
            return;
        }

        Schema::create('playables', function (Blueprint $table) {
            $table->id();

            // At least one side must be present; enforced by the application.
            $table->unsignedBigInteger('audio_post_id')->nullable()->index();
            $table->unsignedBigInteger('video_post_id')->nullable()->index();

            $table->unsignedBigInteger('user_id')->index();
            $table->timestamps();

            // A video's audio and video must belong to the same recording, so a
            // given row cannot back two playables.
            $table->unique('audio_post_id');
            $table->unique('video_post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playables');
    }
};
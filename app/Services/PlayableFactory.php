<?php

namespace App\Services;

use App\Models\AudioPost;
use App\Models\AudioSrc;
use App\Models\Playable;
use App\Models\VideoPost;
use App\Models\VideoSrc;

/**
 * Creates the playable that backs newly uploaded content.
 *
 * A video is also an audio recording, so an upload produces a VideoPost *and*
 * an AudioPost, both attached to a single Playable. That keeps one recording to
 * one playlist row, one now-playing entry, and one set of comments, and lets
 * the player fall back to audio by simply ignoring the video side.
 */
class PlayableFactory
{
    /** Builds a playable for an audio upload. */
    public function forAudio(AudioPost $audio): Playable
    {
        return Playable::forAudioPost($audio);
    }

    /**
     * Builds a playable for a video upload, creating the backing audio post.
     *
     * The audio post mirrors the video's title, description and owner, and
     * records the file path so its source can be transcoded on demand rather
     * than shipping the full video to an audio-only client.
     */
    public function forVideo(VideoPost $video, string $storedPath): Playable
    {
        $audio = $this->mirrorAudioFor($video, $storedPath);

        return Playable::forVideoPost($video, $audio);
    }

    /**
     * Creates the AudioPost that represents a video's audio track.
     *
     * Returns null when the mirror cannot be written; the video still plays,
     * it just has no audio-only fallback yet.
     */
    private function mirrorAudioFor(VideoPost $video, string $storedPath): ?AudioPost
    {
        try {
            $audio = AudioPost::create([
                'name' => $video->name,
                'description' => $video->description,
                'full_text' => $video->full_text,
                // Same file for now; a transcode job can replace this later.
                'src_url' => $video->src_url,
                'size' => $video->size,
                'length' => $video->length,
                'user_id' => $video->user_id,
                'poster_id' => $video->poster_id,
                'poster_type' => $video->poster_type,
                'lyrics_status' => $video->lyrics_status ?? 'ready',
                'media_status' => 'ready',
            ]);

            // One src row so the player has something to resolve.
            AudioSrc::create([
                'refresh_rate' => 44100,
                'bitrate' => 128,
                'src' => $video->src_url,
                'size' => $video->size ?? 0,
                'length' => $video->length ?? 0,
                'format' => 'mp4',
                'quality' => 'mirror',
                'variant' => 'original',
                'mime' => 'audio/mpeg',
                'status' => 'ready',
                'audio_post_id' => $audio->id,
            ]);

            // Share the video's artwork and relations so the two views match.
            $this->mirrorRelations($video, $audio);

            return $audio;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Copies the video's images, tags and hierarchies onto its audio twin.
     *
     * Without this the audio view would render bare while the video view is
     * fully decorated, which is jarring when the user toggles between them.
     */
    private function mirrorRelations(VideoPost $video, AudioPost $audio): void
    {
        foreach (['images', 'tags', 'hierarchies', 'churches'] as $relation) {
            try {
                $ids = $video->{$relation}()->pluck('id')->all();
                if ($ids !== []) {
                    $audio->{$relation}()->sync($ids);
                }
            } catch (\Throwable $e) {
                // A relation the audio model lacks must not fail the upload.
                report($e);
            }
        }
    }
}
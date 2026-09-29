<?php

namespace App\Jobs;

use App\Models\AudioPost;
use App\Models\Image;
use App\Models\VideoPost;
use App\Services\LyricsService;
use App\Services\MediaVariantService;
use App\Services\TranscriptionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Post-upload processing: lyric extraction + adaptive variants + thumbnails.
 * Dispatched from Audio/VideoPostController::create so uploads stay fast.
 * Safe to retry; every step is idempotent-ish and exception-isolated.
 */
class ProcessMediaVariants implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $type, // 'audio' | 'video'
        public int $postId,
        public string $diskPath,
    ) {}

    public function handle(MediaVariantService $variants, TranscriptionService $transcribe): void
    {
        try {
            if ($this->type === 'audio') {
                $this->processAudio(AudioPost::find($this->postId), $variants, $transcribe);
            } else {
                $this->processVideo(VideoPost::find($this->postId), $variants, $transcribe);
            }
        } catch (\Throwable $e) {
            Log::error('ProcessMediaVariants failed: '.$e->getMessage(), [
                'type' => $this->type, 'postId' => $this->postId,
            ]);
            // Don't poison the queue forever; uploads remain playable.
            $this->fail($e);
        }
    }

    private function processAudio(?AudioPost $post, MediaVariantService $variants, TranscriptionService $transcribe): void
    {
        if (!$post) {
            return;
        }
        $post->update(['media_status' => 'processing']);

        // Lyrics: embedded -> Whisper -> existing full_text.
        if (empty($post->full_text)) {
            $text = $transcribe->transcribeDiskPath($this->diskPath, (int) $post->length);
            $post->update([
                'full_text' => $text ?? $post->full_text,
                'lyrics_status' => $text ? 'ready' : 'missing',
            ]);
        } else {
            $post->update(['lyrics_status' => 'ready']);
        }

        $variants->makeAudioVariants($post->id, $this->diskPath);
        $post->update(['media_status' => 'ready']);
    }

    private function processVideo(?VideoPost $post, MediaVariantService $variants, TranscriptionService $transcribe): void
    {
        if (!$post) {
            return;
        }
        $post->update(['media_status' => 'processing']);

        // Lyrics: embedded -> Whisper -> existing full_text.
        if (empty($post->full_text)) {
            $text = $transcribe->transcribeDiskPath($this->diskPath, (int) $post->length);
            $post->update([
                'full_text' => $text ?? $post->full_text,
                'lyrics_status' => $text ? 'ready' : 'missing',
            ]);
        } else {
            $post->update(['lyrics_status' => 'ready']);
        }

        // Thumbnails at different timestamps — stored as Image records.
        $thumbnailUrls = $variants->makeVideoThumbnails($this->diskPath, (int) $post->length);
        foreach ($thumbnailUrls as $url) {
            $image = Image::create([
                'full' => $url,
                'large' => $url,
                'medium' => $url,
                'small' => $url,
                'user_id' => $post->user_id,
            ]);
            $post->images()->attach($image->id);
        }

        $variants->makeVideoVariants($post->id, $this->diskPath, (int) $post->length);
        $post->update(['media_status' => 'ready']);
    }
}

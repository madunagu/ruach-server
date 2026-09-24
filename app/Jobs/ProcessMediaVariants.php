<?php

namespace App\Jobs;

use App\Models\AudioPost;
use App\Models\VideoPost;
use App\Services\LyricsService;
use App\Services\MediaVariantService;
use App\Services\TranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Post-upload processing: lyric extraction + adaptive variants.
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
                $post = AudioPost::find($this->postId);
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

                $variants->makeAudioVariants($post->id, $this->diskPath, $post->src_url);
                $post->update(['media_status' => 'ready']);
            } else {
                $post = VideoPost::find($this->postId);
                if (!$post) {
                    return;
                }
                $post->update(['media_status' => 'processing']);
                $variants->makeVideoVariants($post->id, $this->diskPath, (int) $post->length);
                $post->update(['media_status' => 'ready', 'lyrics_status' => $post->full_text ? 'ready' : 'missing']);
            }
        } catch (\Throwable $e) {
            Log::error('ProcessMediaVariants failed: '.$e->getMessage(), [
                'type' => $this->type, 'postId' => $this->postId,
            ]);
            // Don't poison the queue forever; uploads remain playable.
            $this->fail($e);
        }
    }
}

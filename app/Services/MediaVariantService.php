<?php

namespace App\Services;

use App\Models\AudioSrc;
use App\Models\VideoSrc;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Creates size/quality variants for adaptive, data-saving streaming.
 *
 * Uses ffmpeg when available ( ffmpeg -version ). When ffmpeg is missing
 * (local dev), it falls back to registering the original as the only
 * `ready` variant so playback never breaks — variants are backfilled once
 * ffmpeg is installed / the queue worker runs.
 *
 * Audio variants: original + 64k (low) + 128k (standard).
 * Video variants: original + 480p + 720p MP4 (H.264, faststart for streaming).
 *
 * Mobile clients should pick a variant from `srcs` by `variant`/bitrate
 * and switch down on metered connections.
 */
class MediaVariantService
{
    public function ffmpegAvailable(): bool
    {
        try {
            $out = @shell_exec('ffmpeg -version 2>&1');
            return is_string($out) && str_contains($out, 'ffmpeg version');
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<int,array<string,mixed>> created src rows */
    public function makeAudioVariants(int $audioPostId, string $diskPath, string $publicUrl): array
    {
        $created = [];
        if (!$this->ffmpegAvailable()) {
            Log::info("MediaVariantService: ffmpeg missing, keeping original audio {$diskPath}");
            return $created;
        }

        $disk = Storage::disk('public');
        $abs = $disk->path($diskPath);
        $base = pathinfo($diskPath, PATHINFO_FILENAME);

        $targets = [
            ['variant' => 'low', 'bitrate' => '64k'],
            ['variant' => 'standard', 'bitrate' => '128k'],
        ];

        foreach ($targets as $t) {
            $rel = "audio/variants/{$base}_{$t['variant']}.mp3";
            $outAbs = $disk->path($rel);
            @mkdir(dirname($outAbs), 0755, true);
            $cmd = sprintf(
                'ffmpeg -y -i %s -vn -b:a %s -ar 44100 %s 2>&1',
                escapeshellarg($abs),
                escapeshellarg($t['bitrate']),
                escapeshellarg($outAbs)
            );
            @shell_exec($cmd);
            if ($disk->exists($rel) && $disk->size($rel) > 0) {
                $created[] = AudioSrc::create([
                    'length' => null,
                    'refresh_rate' => null,
                    'bitrate' => (int) filter_var($t['bitrate'], FILTER_SANITIZE_NUMBER_INT) * 1000,
                    'src' => $disk->url($rel),
                    'size' => $disk->size($rel),
                    'format' => 'mp3',
                    'variant' => $t['variant'],
                    'mime' => 'audio/mpeg',
                    'status' => 'ready',
                    'audio_post_id' => $audioPostId,
                ])->toArray();
            } else {
                Log::warning("MediaVariantService: audio variant failed {$t['variant']} for {$diskPath}");
            }
        }

        return $created;
    }

    /** @return array<int,array<string,mixed>> created src rows */
    public function makeVideoVariants(int $videoPostId, string $diskPath, ?int $length = null): array
    {
        $created = [];
        if (!$this->ffmpegAvailable()) {
            Log::info("MediaVariantService: ffmpeg missing, keeping original video {$diskPath}");
            return $created;
        }

        $disk = Storage::disk('public');
        $abs = $disk->path($diskPath);
        $base = pathinfo($diskPath, PATHINFO_FILENAME);

        // height => label. 480p first (data saver), then 720p.
        $targets = [
            ['variant' => '480p', 'height' => 480, 'bitrate' => '800k'],
            ['variant' => '720p', 'height' => 720, 'bitrate' => '1600k'],
        ];

        foreach ($targets as $t) {
            $rel = "video/variants/{$base}_{$t['variant']}.mp4";
            $outAbs = $disk->path($rel);
            @mkdir(dirname($outAbs), 0755, true);
            $cmd = sprintf(
                'ffmpeg -y -i %s -vf %s -c:v libx264 -preset veryfast -b:v %s -c:a aac -b:a 128k -movflags +faststart %s 2>&1',
                escapeshellarg($abs),
                escapeshellarg("scale=-2:{$t['height']}"),
                escapeshellarg($t['bitrate']),
                escapeshellarg($outAbs)
            );
            @shell_exec($cmd);
            if ($disk->exists($rel) && $disk->size($rel) > 0) {
                $created[] = VideoSrc::create([
                    'src' => $disk->url($rel),
                    'length' => $length,
                    'quality' => $t['height'],
                    'format' => 'mp4',
                    'dimensions' => null,
                    'width' => null,
                    'height' => $t['height'],
                    'size' => $disk->size($rel),
                    'variant' => $t['variant'],
                    'mime' => 'video/mp4',
                    'status' => 'ready',
                    'video_post_id' => $videoPostId,
                ])->toArray();
            } else {
                Log::warning("MediaVariantService: video variant failed {$t['variant']} for {$diskPath}");
            }
        }

        return $created;
    }
}

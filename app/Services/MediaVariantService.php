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
 * Video thumbnails: single JPEG frame grabbed near the start.
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
    /** @return array<int,array<string,mixed>> */
    /** @return array<int,array<string,mixed>> */
    public function makeAudioVariants(int $audioPostId, string $diskPath): array
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
    /** @return array<int,array<string,mixed>> */
    /** @return array<int,array<string,mixed>> */
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

    /**
     * Generate JPEG thumbnails at different timestamps of a video.
     * Returns an array of public URLs for the created thumbnails.
     *
     * @return array<int, string> public URLs of created thumbnails
     */
    public function makeVideoThumbnails(string $diskPath, ?int $length = null): array
    {
        $created = [];
        if (!$this->ffmpegAvailable()) {
            return $created;
        }

        $disk = Storage::disk('public');
        $abs = $disk->path($diskPath);
        $base = pathinfo($diskPath, PATHINFO_FILENAME);

        // Pick 3 timestamps: 10%, 50%, 90% into the video (min 1s apart).
        $timestamps = $this->thumbnailTimestamps($length);

        foreach ($timestamps as $index => $seek) {
            $rel = "video/thumbnails/{$base}_thumb{$index}.jpg";
            $outAbs = $disk->path($rel);
            @mkdir(dirname($outAbs), 0755, true);

            $cmd = sprintf(
                'ffmpeg -y -ss %s -i %s -vframes 1 -q:v 2 -vf scale=480:-2 %s 2>&1',
                escapeshellarg("00:00:{$seek}"),
                escapeshellarg($abs),
                escapeshellarg($outAbs)
            );
            @shell_exec($cmd);

            if ($disk->exists($rel) && $disk->size($rel) > 0) {
                $created[] = $disk->url($rel);
            } else {
                Log::warning("MediaVariantService: video thumbnail failed for {$diskPath} at {$seek}s");
            }
        }

        return $created;
    }

    /**
     * Pick 3 seek points for thumbnails: 10%, 50%, 90% of duration.
     * Falls back to fixed points when duration is unknown.
     *
     * @return array<int, int>
     */
    private function thumbnailTimestamps(?int $length): array
    {
        if ($length === null || $length < 10) {
            return [1, 5, 10];
        }

        return [
            max(1, (int) round($length * 0.1)),
            max(2, (int) round($length * 0.5)),
            max(3, (int) round($length * 0.9)),
        ];
    }

    /**
     * Extract album art (ID3v2 APIC frame) from an audio file.
     * Returns raw image bytes or null when no art is embedded.
     */
    public function extractAlbumArt(string $diskPath): ?string
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($diskPath)) {
                return null;
            }

            $abs = $disk->path($diskPath);
            $fh = @fopen($abs, 'rb');
            if (!$fh) {
                return null;
            }

            try {
                $header = fread($fh, 10);
                if (strlen($header) < 10 || substr($header, 0, 3) !== 'ID3') {
                    return null;
                }

                $size = $this->syncSafeInt(substr($header, 6, 4));
                $size = min($size, 4 * 1024 * 1024); // Cap at 4MB of tag data
                $tag = $size > 0 ? fread($fh, $size) : '';
                if ($tag === '' || $tag === false) {
                    return null;
                }

                $offset = 0;
                $len = strlen($tag);
                while ($offset + 10 <= $len) {
                    $frameId = substr($tag, $offset, 4);
                    $frameSize = unpack('N', substr($tag, $offset + 4, 4))[1] ?? 0;
                    if (!preg_match('/^[A-Z0-9]{4}$/', $frameId) || $frameSize <= 0) {
                        break;
                    }
                    if ($frameId === 'APIC' && $frameSize < $len) {
                        $body = substr($tag, $offset + 10, $frameSize);
                        $image = $this->decodeApicImage($body);
                        if ($image !== null) {
                            return $image;
                        }
                    }
                    $offset += 10 + $frameSize;
                    if ($offset > $len || $frameSize > 2 * 1024 * 1024) {
                        break;
                    }
                }
                return null;
            } finally {
                fclose($fh);
            }
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Decode an ID3v2 APIC frame body into raw image bytes.
     * Format: encoding(1) + mime(null-term) + picture_type(1) + description(null-term) + image_data
     */
    private function decodeApicImage(string $body): ?string
    {
        if (strlen($body) < 12) {
            return null;
        }

        $offset = 1; // skip encoding byte

        // Skip MIME type (null-terminated)
        $nullPos = strpos($body, "\x00", $offset);
        if ($nullPos === false) {
            return null;
        }
        $offset = $nullPos + 1;

        // Skip picture type (1 byte)
        if (strlen($body) <= $offset + 1) {
            return null;
        }
        $offset += 1;

        // Skip description (null-terminated, encoding-aware)
        $enc = ord($body[0]);
        if ($enc === 1 || $enc === 2) {
            // UTF-16: find double null
            $pos = strpos($body, "\x00\x00", $offset);
            $offset = $pos !== false ? $pos + 2 : strlen($body);
        } else {
            $pos = strpos($body, "\x00", $offset);
            $offset = $pos !== false ? $pos + 1 : strlen($body);
        }

        $image = substr($body, $offset);
        if (strlen($image) < 100) {
            return null;
        }

        // Validate it looks like an image (JPEG or PNG magic bytes)
        $isJpeg = substr($image, 0, 3) === "\xFF\xD8\xFF";
        $isPng = substr($image, 0, 4) === "\x89PNG";
        if (!$isJpeg && !$isPng) {
            return null;
        }

        return $image;
    }

    private function syncSafeInt(string $bytes): int
    {
        $b = unpack('C4', $bytes);
        return (($b[1] & 0x7f) << 21) | (($b[2] & 0x7f) << 14) | (($b[3] & 0x7f) << 7) | ($b[4] & 0x7f);
    }
}

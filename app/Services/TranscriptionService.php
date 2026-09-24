<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Speech-to-text hook for getTrackFullText().
 *
 * Priority:
 *  1. Embedded lyrics / sidecar .lrc via LyricsService (free, instant).
 *  2. OpenAI Whisper API when OPENAI_API_KEY is set (uploads < 25MB sent
 *     directly; larger files use the low variant if present).
 *  3. Null — caller keeps existing full_text.
 */
class TranscriptionService
{
    public function __construct(private LyricsService $lyrics) {}

    public function transcribeDiskPath(string $diskPath, ?int $durationSecs = null): ?string
    {
        // 1. Free path first.
        $embedded = $this->lyrics->extractFromDisk($diskPath);
        if ($embedded) {
            return $this->lyrics->normalize($embedded, $durationSecs);
        }

        // 2. Whisper when configured.
        $key = env('OPENAI_API_KEY');
        if (!$key) {
            return null;
        }
        try {
            $abs = Storage::disk('public')->path($diskPath);
            if (!is_file($abs) || filesize($abs) > 25 * 1024 * 1024) {
                Log::info("TranscriptionService: skipping whisper for {$diskPath} (missing/too large)");
                return null;
            }
            $res = Http::timeout(300)->withToken($key)->asMultipart()->post(
                'https://api.openai.com/v1/audio/transcriptions',
                [
                    ['name' => 'file', 'contents' => fopen($abs, 'rb'), 'filename' => basename($abs)],
                    ['name' => 'model', 'contents' => env('WHISPER_MODEL', 'whisper-1')],
                    ['name' => 'response_format', 'contents' => 'verbose_json'],
                ]
            );
            if (!$res->ok()) {
                Log::warning('TranscriptionService: whisper HTTP '.$res->status());
                return null;
            }
            $data = $res->json();
            $text = trim($data['text'] ?? '');
            if ($text === '') {
                return null;
            }
            // Timestamp segments -> LRC so the lyric player can highlight.
            $segments = $data['segments'] ?? [];
            if (!empty($segments)) {
                $lines = [];
                foreach ($segments as $seg) {
                    $t = (int) floor($seg['start'] ?? 0);
                    $lines[] = sprintf('[%02d:%02d.00] %s', intdiv($t, 60), $t % 60, trim($seg['text'] ?? ''));
                }
                return implode("\n", array_filter($lines));
            }
            return $this->lyrics->normalize($text, $durationSecs);
        } catch (\Throwable $e) {
            Log::warning('TranscriptionService failed: '.$e->getMessage());
            return null;
        }
    }
}

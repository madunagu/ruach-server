<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Extracts embedded lyrics / transcripts from uploaded audio.
 *
 * Order of attempts (fast, dependency-free first):
 *  1. ID3 USLT / SYLT frames parsed directly from MP3 bytes (no ext needed).
 *  2. Sidecar `.lrc` file if the uploader provided one.
 *  3. Falls back to null — the Whisper/transcription hook (getTrackFullText)
 *     or manual `full_text` from the request fills the gap.
 *
 * Returns normalized LRC/plain text or null when nothing is embedded.
 */
class LyricsService
{
    public function extractFromDisk(string $diskPath): ?string
    {
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($diskPath)) {
                return null;
            }

            // 1. Sidecar .lrc next to the upload (e.g. audio/full/x.lrc).
            $lrcPath = preg_replace('/\.[a-z0-9]+$/i', '.lrc', $diskPath);
            if ($lrcPath && $disk->exists($lrcPath)) {
                $lrc = trim($disk->get($lrcPath));
                return $lrc !== '' ? $this->normalize($lrc) : null;
            }

            // 2. Embedded ID3 lyrics (MP3 USLT frame). Stream only head+tail
            // to avoid loading huge files fully into memory twice.
            $fullPath = Storage::disk('public')->path($diskPath);
            $uslt = $this->extractId3Uslt($fullPath);
            if ($uslt !== null && trim($uslt) !== '') {
                return $this->normalize($uslt);
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Minimal ID3v2 USLT/TXXX-lyrics scanner. Returns raw text or null. */
    private function extractId3Uslt(string $path): ?string
    {
        $fh = @fopen($path, 'rb');
        if (!$fh) {
            return null;
        }
        try {
            $header = fread($fh, 10);
            if (strlen($header) < 10 || substr($header, 0, 3) !== 'ID3') {
                return null;
            }
            $size = $this->syncSafeInt(substr($header, 6, 4));
            // Cap scan at 1MB of tag data.
            $size = min($size, 1024 * 1024);
            $tag = $size > 0 ? fread($fh, $size) : '';
            if ($tag === '' || $tag === false) {
                return null;
            }
            // Find USLT frames; take the first with decodable text.
            $offset = 0;
            $len = strlen($tag);
            while ($offset + 10 <= $len) {
                $frameId = substr($tag, $offset, 4);
                $frameSize = unpack('N', substr($tag, $offset + 4, 4))[1] ?? 0;
                if (!preg_match('/^[A-Z0-9]{4}$/', $frameId) || $frameSize <= 0) {
                    break;
                }
                if (($frameId === 'USLT' || $frameId === 'SYLT') && $frameSize < $len) {
                    $body = substr($tag, $offset + 10, $frameSize);
                    $text = $this->decodeId3Text($body);
                    if ($text !== null && trim($text) !== '') {
                        return $text;
                    }
                }
                $offset += 10 + $frameSize;
                if ($offset > $len || $frameSize > 512 * 1024) {
                    break;
                }
            }
            return null;
        } finally {
            fclose($fh);
        }
    }

    private function syncSafeInt(string $bytes): int
    {
        $b = unpack('C4', $bytes);
        return (($b[1] & 0x7f) << 21) | (($b[2] & 0x7f) << 14) | (($b[3] & 0x7f) << 7) | ($b[4] & 0x7f);
    }

    private function decodeId3Text(string $body): ?string
    {
        if (strlen($body) < 5) {
            return null;
        }
        $enc = ord($body[0]);
        // USLT: encoding(1) + lang(3) + descriptor(null-term) + lyrics.
        $rest = substr($body, 4);
        // Strip content descriptor up to first null (encoding-aware).
        if ($enc === 1 || $enc === 2) {
            $pos = strpos($rest, "\x00\x00");
            $lyrics = $pos !== false ? substr($rest, $pos + 2) : $rest;
            $from = $enc === 1 ? 'UTF-16' : 'UTF-16BE';
            $conv = @mb_convert_encoding($lyrics, 'UTF-8', $from);
            return $conv !== false && $conv !== '' ? $conv : null;
        }
        $pos = strpos($rest, "\x00");
        $lyrics = $pos !== false ? substr($rest, $pos + 1) : $rest;
        if ($enc === 3) {
            return @mb_convert_encoding($lyrics, 'UTF-8', 'UTF-8') ?: null;
        }
        // Latin-1 default.
        return @mb_convert_encoding($lyrics, 'UTF-8', 'ISO-8859-1') ?: null;
    }

    /**
     * Normalize to LRC when timestamps exist, else plain trimmed text.
     * Also stamps plain-text lines with even spacing when a duration is
     * known so the mobile LyricsViewer can still highlight lines.
     */
    public function normalize(string $raw, ?int $durationSecs = null): string
    {
        $raw = trim(str_replace(["\r\n", "\r"], "\n", $raw));
        if (preg_match('/\[\d{1,2}:\d{2}(?:\.\d{1,3})?\]/', $raw)) {
            return $raw;
        }
        if ($durationSecs === null || $durationSecs <= 0) {
            return $raw;
        }
        $lines = array_values(array_filter(array_map('trim', explode("\n", $raw))));
        if (empty($lines)) {
            return $raw;
        }
        $step = max(1, (int) floor($durationSecs / count($lines)));
        $out = [];
        foreach ($lines as $i => $line) {
            $t = $i * $step;
            $out[] = sprintf('[%02d:%02d.00] %s', intdiv($t, 60), $t % 60, $line);
        }
        return implode("\n", $out);
    }
}

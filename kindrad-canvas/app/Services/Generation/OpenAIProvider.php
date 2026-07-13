<?php

namespace App\Services\Generation;

use App\Contracts\GenerationProvider;
use App\Generation\GenerationResult;
use App\Models\SourceImage;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OpenAIProvider implements GenerationProvider
{
    public function getProviderKey(): string
    {
        return 'openai';
    }

    /**
     * @param  array{width:int, height:int, mime?: string, dpi?: int, safe_area_mm?: float, print_width_mm?: float, print_height_mm?: float}  $constraints
     */
    public function generate(string $prompt, array $constraints, ?SourceImage $sourceImage = null): GenerationResult
    {
        $apiKey = (string) config('generation.openai.api_key');

        if ($apiKey === '') {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $endpoint = (string) config('generation.openai.endpoint');
        $model = (string) config('generation.openai.model');
        $size = (string) config('generation.openai.size');
        $timeout = (int) config('generation.openai.timeout');

        $response = Http::withToken($apiKey)
            ->timeout($timeout)
            ->acceptJson()
            ->asJson()
            ->post($endpoint, [
                'model' => $model,
                'prompt' => $prompt,
                'size' => $size,
                'n' => 1,
                'response_format' => 'b64_json',
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI image generation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('OpenAI image generation failed: '.$response->status());
        }

        $payload = $response->json();
        $binary = $this->extractBinary($payload);
        $mime = $this->extractMime($payload) ?? 'image/png';

        $width = $this->resolveWidth($constraints);
        $height = $this->resolveHeight($constraints);

        $path = (string) config('generation.key_prefix').Str::uuid().'.'.$this->extensionFromMime($mime);

        Storage::disk((string) config('generation.disk'))->put($path, $binary);

        return new GenerationResult(
            path: $path,
            mime: $mime,
            width: $width,
            height: $height,
            binary: $binary,
        );
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function resolveWidth(array $constraints): int
    {
        if (isset($constraints['width']) && (int) $constraints['width'] > 0) {
            return (int) $constraints['width'];
        }

        $mm = (float) ($constraints['print_width_mm'] ?? 0);
        $dpi = (float) ($constraints['dpi'] ?? $constraints['min_dpi'] ?? 0);

        if ($mm > 0 && $dpi > 0) {
            return (int) round($mm * $dpi / 25.4);
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $constraints
     */
    private function resolveHeight(array $constraints): int
    {
        if (isset($constraints['height']) && (int) $constraints['height'] > 0) {
            return (int) $constraints['height'];
        }

        $mm = (float) ($constraints['print_height_mm'] ?? 0);
        $dpi = (float) ($constraints['dpi'] ?? $constraints['min_dpi'] ?? 0);

        if ($mm > 0 && $dpi > 0) {
            return (int) round($mm * $dpi / 25.4);
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractBinary(array $payload): string
    {
        $first = $payload['data'][0] ?? null;

        if (is_array($first) && isset($first['b64_json'])) {
            $decoded = base64_decode((string) $first['b64_json'], true);

            if ($decoded === false) {
                throw new \RuntimeException('Failed to decode OpenAI b64_json response.');
            }

            return $decoded;
        }

        if (is_array($first) && isset($first['url'])) {
            try {
                $imageResponse = Http::timeout(60)->get((string) $first['url']);
            } catch (RequestException $e) {
                throw new \RuntimeException('Failed to download OpenAI image: '.$e->getMessage(), 0, $e);
            }

            if (! $imageResponse->successful()) {
                throw new \RuntimeException('OpenAI image download failed: '.$imageResponse->status());
            }

            return $imageResponse->body();
        }

        throw new \RuntimeException('OpenAI response contained neither b64_json nor url.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractMime(array $payload): ?string
    {
        $first = $payload['data'][0] ?? null;

        if (is_array($first) && isset($first['mime_type'])) {
            return (string) $first['mime_type'];
        }

        return null;
    }

    private function extensionFromMime(string $mime): string
    {
        return match (true) {
            str_contains($mime, 'jpeg') => 'jpg',
            str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'webp') => 'webp',
            str_contains($mime, 'gif') => 'gif',
            default => 'png',
        };
    }
}

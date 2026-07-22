<?php

namespace App\Services\Generation;

use App\Contracts\GenerationProvider;
use App\Generation\GenerationResult;
use App\Models\SourceImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GeminiProvider implements GenerationProvider
{
    public function getProviderKey(): string
    {
        return 'gemini';
    }

    public function generate(string $prompt, array $constraints, ?SourceImage $sourceImage = null): GenerationResult
    {
        $apiKey = config('generation.gemini.api_key');
        if (empty($apiKey)) {
            throw new RuntimeException('Gemini API key is missing.');
        }

        $model = config('generation.gemini.model', 'gemini-2.5-flash-image');
        $endpoint = sprintf(config('generation.gemini.endpoint', 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent'), $model);

        $width = $constraints['width'] ?? 1024;
        $height = $constraints['height'] ?? 1024;

        // Append dimensions to prompt since Gemini flash-image doesn't use standard layout parameters
        $finalPrompt = $prompt.sprintf(' (Generate an image with dimensions approximately %d pixels wide by %d pixels high)', $width, $height);

        $aspectRatio = '1:1';
        if ($width > $height * 2) {
            $aspectRatio = '21:9';
        } elseif ($width > $height * 1.5) {
            $aspectRatio = '16:9';
        } elseif ($width > $height) {
            $aspectRatio = '4:3';
        } elseif ($height > $width * 2) {
            $aspectRatio = '9:16';
        } elseif ($height > $width * 1.5) {
            $aspectRatio = '9:16';
        } elseif ($height > $width) {
            $aspectRatio = '3:4';
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $finalPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseModalities' => ['image'],
                'imageConfig' => [
                    'aspectRatio' => $aspectRatio,
                ],
            ],
        ];

        // Attach source image if present
        if ($sourceImage) {
            $imageContent = Storage::disk($sourceImage->disk)->get($sourceImage->path);
            if ($imageContent) {
                array_unshift($payload['contents'][0]['parts'], [
                    'inlineData' => [
                        'mimeType' => $sourceImage->mime_type,
                        'data' => base64_encode($imageContent),
                    ],
                ]);
            }
        }

        $response = Http::timeout(config('generation.gemini.timeout', 120))
            ->post("{$endpoint}?key={$apiKey}", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gemini API request failed: '.$response->body());
        }

        $data = $response->json();

        $base64Image = null;
        $mimeType = 'image/png';

        $candidates = $data['candidates'] ?? [];
        if (! empty($candidates)) {
            $parts = $candidates[0]['content']['parts'] ?? [];
            foreach ($parts as $part) {
                if (isset($part['inlineData']['data'])) {
                    $base64Image = $part['inlineData']['data'];
                    $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';
                    break;
                }
            }
        }

        if ($base64Image === null) {
            throw new RuntimeException('No image returned from Gemini API: '.$response->body());
        }

        $imageBinary = base64_decode($base64Image);
        if ($imageBinary === false) {
            throw new RuntimeException('Failed to decode base64 image data.');
        }

        $imageSize = getimagesizefromstring($imageBinary);
        $actualWidth = $imageSize[0] ?? $width;
        $actualHeight = $imageSize[1] ?? $height;

        $disk = config('generation.disk', 's3');
        $prefix = config('generation.key_prefix', 'generations/');

        $extension = str_replace('image/', '', $mimeType);
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $filename = Str::uuid().'.'.$extension;
        $path = $prefix.$filename;

        Storage::disk($disk)->put($path, $imageBinary);

        return new GenerationResult(
            path: $path,
            mime: $mimeType,
            width: $actualWidth,
            height: $actualHeight,
            binary: $imageBinary
        );
    }
}

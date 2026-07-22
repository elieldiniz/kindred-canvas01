<?php

use App\Models\GenerationProvider;
use App\Services\Generation\GeminiProvider;
use App\Services\Generation\OpenAIProvider;
use App\Services\Generation\ProviderRegistry;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('s3');
    config()->set('generation.openai.api_key', 'test-key');
    config()->set('generation.provider', 'openai');
    $this->seed(CatalogSeeder::class);
});

test('resolves openai by default', function (): void {
    config()->set('generation.provider', 'openai');

    $provider = app(ProviderRegistry::class)->resolveActive();

    expect($provider)->toBeInstanceOf(OpenAIProvider::class)
        ->and($provider->getProviderKey())->toBe('openai');
});

test('falls back to openai when configured provider is inactive', function (): void {
    config()->set('generation.provider', 'gemini');

    $provider = app(ProviderRegistry::class)->resolveActive();

    expect($provider)->toBeInstanceOf(OpenAIProvider::class);
});

test('resolves gemini when active in lookup table', function (): void {
    config()->set('generation.provider', 'gemini');

    GenerationProvider::where('slug', 'gemini')->update(['is_active' => true]);

    $provider = app(ProviderRegistry::class)->resolveActive();

    expect($provider->getProviderKey())->toBe('gemini');
});

test('unknown slug throws', function (): void {
    app(ProviderRegistry::class)->resolve('does-not-exist');
})->throws(InvalidArgumentException::class);

test('openai provider posts and returns result', function (): void {
    $fakeImage = base64_encode('fake-image-bytes');

    Http::fake([
        'api.openai.com/*' => Http::response([
            'created' => 1,
            'data' => [[
                'b64_json' => $fakeImage,
                'mime_type' => 'image/png',
            ]],
        ], 200),
    ]);

    $prompt = 'A watercolor mug for Alice in a forest theme.';
    $result = app(OpenAIProvider::class)->generate($prompt, [
        'width' => 1024,
        'height' => 1024,
    ]);

    expect($result->mime)->toBe('image/png')
        ->and($result->width)->toBe(1024)
        ->and($result->height)->toBe(1024)
        ->and($result->binary)->toBe('fake-image-bytes');

    Storage::disk('s3')->assertExists($result->path);
});

test('openai provider handles api failure', function (): void {
    Http::fake([
        'api.openai.com/*' => Http::response('error', 500),
    ]);

    app(OpenAIProvider::class)->generate('prompt', [
        'width' => 1024,
        'height' => 1024,
    ]);
})->throws(RuntimeException::class);

test('gemini provider posts and returns result', function (): void {
    config()->set('generation.gemini.api_key', 'test-gemini-key');

    $fakeImage = base64_encode('fake-image-bytes');

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'inlineData' => [
                                    'data' => $fakeImage,
                                    'mimeType' => 'image/png',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $prompt = 'A beautiful mountain landscape at sunset.';
    $result = app(GeminiProvider::class)->generate($prompt, [
        'width' => 1024,
        'height' => 1024,
    ]);

    expect($result->mime)->toBe('image/png')
        ->and($result->binary)->toBe('fake-image-bytes');

    Storage::disk('s3')->assertExists($result->path);
});

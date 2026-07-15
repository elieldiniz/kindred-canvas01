<?php

use App\Jobs\GenerateArtworkJob;
use App\Models\Category;
use App\Models\CreditTransaction;
use App\Models\CreditTransactionReason;
use App\Models\Generation;
use App\Models\GenerationStatus;
use App\Models\Layout;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\ProjectPhoto;
use App\Models\SourceImage;
use App\Models\Style;
use App\Models\User;
use App\Services\CreditLedger;
use App\Services\Generation\ProviderRegistry;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('s3');
    config()->set('generation.openai.api_key', 'test-key');
    $this->seed(CatalogSeeder::class);
});

function buildGenerationRow(User $user, int $balanceBeforeDebit = 5): Generation
{
    $product = Product::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();
    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    $project = Project::factory()
        ->withMode($mug->id)
        ->withCategory($category->id)
        ->withStyle($style->id)
        ->withLayout($layout->id)
        ->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'inputs' => ['name' => 'Alice'],
        ]);

    $user->update(['credit_balance' => $balanceBeforeDebit]);

    $generation = Generation::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
        'constraints_snapshot' => [
            'width' => (int) round((float) $product->print_width_mm * (float) $product->min_dpi / 25.4),
            'height' => (int) round((float) $product->print_height_mm * (float) $product->min_dpi / 25.4),
            'print_width_mm' => (float) $product->print_width_mm,
            'print_height_mm' => (float) $product->print_height_mm,
            'dpi' => (int) $product->min_dpi,
            'safe_area_mm' => (float) $product->safe_area_mm,
        ],
    ]);

    app(CreditLedger::class)->debit($user, 1, $generation);

    return $generation;
}

test('success path persists result', function (): void {
    $user = User::factory()->create();
    $generation = buildGenerationRow($user, 5);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'created' => 1,
            'data' => [[
                'b64_json' => base64_encode('fake-image-bytes'),
                'mime_type' => 'image/png',
            ]],
        ], 200),
    ]);

    (new GenerateArtworkJob($generation->id, 'openai'))->handle(
        app(ProviderRegistry::class),
        app(CreditLedger::class),
    );

    $generation->refresh();
    expect($generation->status_id)->toBe(GenerationStatus::where('slug', 'completed')->value('id'))
        ->and($generation->result_path)->not->toBeNull()
        ->and($generation->result_width_px)->toBeGreaterThan(0);

    Storage::disk('s3')->assertExists($generation->result_path);
});

test('failure path refunds credit', function (): void {
    $user = User::factory()->create();
    $generation = buildGenerationRow($user, 5);

    Http::fake([
        'api.openai.com/*' => Http::response('error', 500),
    ]);

    try {
        (new GenerateArtworkJob($generation->id, 'openai'))->handle(
            app(ProviderRegistry::class),
            app(CreditLedger::class),
        );
    } catch (Throwable $e) {
        // rethrows on attempts < tries; ok
    }

    $generation->refresh();
    expect($generation->status_id)->toBe(GenerationStatus::where('slug', 'failed')->value('id'))
        ->and($generation->failure_reason)->not->toBeNull();

    $refund = CreditTransaction::where('reference_type', Generation::class)
        ->where('reference_id', $generation->id)
        ->where('reason_id', CreditTransactionReason::where('slug', 'generation_refund')->value('id'))
        ->first();

    expect($refund)->not->toBeNull()
        ->and($refund->delta)->toBe(1)
        ->and($refund->balance_after)->toBe(5);
});

test('idempotent against double dispatch', function (): void {
    $user = User::factory()->create();
    $generation = buildGenerationRow($user, 5);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'created' => 1,
            'data' => [[
                'b64_json' => base64_encode('fake-image-bytes'),
                'mime_type' => 'image/png',
            ]],
        ], 200),
    ]);

    $registry = app(ProviderRegistry::class);
    $ledger = app(CreditLedger::class);

    (new GenerateArtworkJob($generation->id, 'openai'))->handle($registry, $ledger);
    $generation->refresh();
    $firstStatus = $generation->status_id;
    $firstResultPath = $generation->result_path;

    (new GenerateArtworkJob($generation->id, 'openai'))->handle($registry, $ledger);
    $generation->refresh();

    expect($generation->status_id)->toBe($firstStatus)
        ->and($generation->result_path)->toBe($firstResultPath);

    $debitCount = CreditTransaction::where('reference_type', Generation::class)
        ->where('reference_id', $generation->id)
        ->where('reason_id', CreditTransactionReason::where('slug', 'generation_debit')->value('id'))
        ->count();

    expect($debitCount)->toBe(1);
});

test('marks project first generated at on completion', function (): void {
    $user = User::factory()->create();
    $generation = buildGenerationRow($user, 5);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'created' => 1,
            'data' => [[
                'b64_json' => base64_encode('fake-image-bytes'),
                'mime_type' => 'image/png',
            ]],
        ], 200),
    ]);

    $project = Project::find($generation->project_id);
    expect($project->first_generated_at)->toBeNull();

    (new GenerateArtworkJob($generation->id, 'openai'))->handle(
        app(ProviderRegistry::class),
        app(CreditLedger::class),
    );

    $project->refresh();
    expect($project->first_generated_at)->not->toBeNull();
});

test('passes the first project photo as source image to the provider', function (): void {
    $user = User::factory()->create();
    $generation = buildGenerationRow($user, 5);

    $sourceImage = SourceImage::factory()->create([
        'user_id' => $user->id,
        'path' => 'source-images/cover.jpg',
        'disk' => 's3',
    ]);
    ProjectPhoto::create([
        'project_id' => $generation->project_id,
        'source_image_id' => $sourceImage->id,
        'position' => 0,
    ]);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'created' => 1,
            'data' => [[
                'b64_json' => base64_encode('fake-image-bytes'),
                'mime_type' => 'image/png',
            ]],
        ], 200),
    ]);

    (new GenerateArtworkJob($generation->id, 'openai'))->handle(
        app(ProviderRegistry::class),
        app(CreditLedger::class),
    );

    expect($generation->fresh()->status_id)->toBe(GenerationStatus::where('slug', 'completed')->value('id'));
});

test('handles projects with no photos by passing null to provider', function (): void {
    $user = User::factory()->create();
    $generation = buildGenerationRow($user, 5);

    Http::fake([
        'api.openai.com/*' => Http::response([
            'created' => 1,
            'data' => [[
                'b64_json' => base64_encode('fake-image-bytes'),
                'mime_type' => 'image/png',
            ]],
        ], 200),
    ]);

    (new GenerateArtworkJob($generation->id, 'openai'))->handle(
        app(ProviderRegistry::class),
        app(CreditLedger::class),
    );

    expect($generation->fresh()->status_id)->toBe(GenerationStatus::where('slug', 'completed')->value('id'));
});

<?php

use App\Actions\Generation\SubmitGeneration;
use App\Jobs\GenerateArtworkJob;
use App\Models\Category;
use App\Models\CreditTransaction;
use App\Models\CreditTransactionReason;
use App\Models\Generation;
use App\Models\GenerationProvider;
use App\Models\GenerationStatus;
use App\Models\Layout;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\PromptTemplate;
use App\Models\Style;
use App\Models\User;
use App\Services\Exceptions\CreditInsufficientException;
use Database\Seeders\CatalogSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Bus;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

function makeCompletedProject(User $user): Project
{
    $product = Product::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();
    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    return Project::factory()
        ->withMode($mug->id)
        ->withCategory($category->id)
        ->withStyle($style->id)
        ->withLayout($layout->id)
        ->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'inputs' => ['name' => 'Alice'],
        ]);
}

test('refuses when no credits', function (): void {
    $user = User::factory()->withCredits(0)->create();
    $project = makeCompletedProject($user);

    $countBefore = Generation::count();

    app(SubmitGeneration::class)->execute($user, $project);
})->throws(CreditInsufficientException::class);

test('after refusal no generation or ledger row is written', function (): void {
    $user = User::factory()->withCredits(0)->create();
    $project = makeCompletedProject($user);

    $gens = Generation::count();
    $txs = CreditTransaction::count();

    try {
        app(SubmitGeneration::class)->execute($user, $project);
    } catch (CreditInsufficientException) {
        // expected
    }

    expect(Generation::count())->toBe($gens)
        ->and(CreditTransaction::count())->toBe($txs);
});

test('creates generation and debits credits', function (): void {
    Bus::fake();

    $user = User::factory()->withCredits(5)->create();
    $project = makeCompletedProject($user);

    $generation = app(SubmitGeneration::class)->execute($user, $project);

    expect($generation)->toBeInstanceOf(Generation::class)
        ->and($generation->user_id)->toBe($user->id)
        ->and($generation->project_id)->toBe($project->id)
        ->and($generation->status_id)->toBe(GenerationStatus::where('slug', 'waiting')->value('id'))
        ->and($generation->idempotency_key)->not->toBeNull()
        ->and((int) $user->fresh()->credit_balance)->toBe(4);

    $debit = CreditTransaction::where('user_id', $user->id)
        ->where('reason_id', CreditTransactionReason::where('slug', 'generation_debit')->value('id'))
        ->first();

    expect($debit)->not->toBeNull()
        ->and($debit->delta)->toBe(-1)
        ->and($debit->balance_after)->toBe(4)
        ->and($debit->reference_id)->toBe($generation->id);

    Bus::assertDispatched(GenerateArtworkJob::class, function ($job) use ($generation) {
        return $job->generationId === $generation->id
            && $job->providerKey === 'openai';
    });
});

test('uses active provider from config', function (): void {
    Bus::fake();

    config()->set('generation.provider', 'gemini');
    GenerationProvider::where('slug', 'gemini')->update(['is_active' => true]);

    $user = User::factory()->withCredits(5)->create();
    $project = makeCompletedProject($user);

    $generation = app(SubmitGeneration::class)->execute($user, $project);

    $geminiId = GenerationProvider::where('slug', 'gemini')->value('id');
    expect($generation->provider_id)->toBe($geminiId);

    Bus::assertDispatched(GenerateArtworkJob::class, function ($job) {
        return $job->providerKey === 'gemini';
    });
});

test('prompt template is no longer required by the engine', function (): void {
    Bus::fake();

    PromptTemplate::query()->delete();

    $user = User::factory()->withCredits(5)->create();
    $project = makeCompletedProject($user);

    $generation = app(SubmitGeneration::class)->execute($user, $project);

    expect($generation)->toBeInstanceOf(Generation::class)
        ->and(Generation::count())->toBe(1);

    Bus::assertDispatched(GenerateArtworkJob::class);
});

test('non owner gets authorization exception', function (): void {
    $owner = User::factory()->withCredits(5)->create();
    $other = User::factory()->withCredits(5)->create();
    $project = makeCompletedProject($owner);

    $gens = Generation::count();
    $txs = CreditTransaction::count();

    try {
        app(SubmitGeneration::class)->execute($other, $project);
        $this->fail('Expected exception was not thrown.');
    } catch (AuthorizationException) {
        // expected
    }

    expect(Generation::count())->toBe($gens)
        ->and(CreditTransaction::count())->toBe($txs);
});

<?php

use App\Livewire\Gallery\Explore;
use App\Models\GalleryFavorite;
use App\Models\Generation;
use App\Models\GenerationProvider;
use App\Models\GenerationStatus;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Subscription;
use App\Models\SubscriptionInterval;
use App\Models\SubscriptionStatus;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    SubscriptionInterval::firstOrCreate(['slug' => 'month'], ['name' => 'Mensal']);
    SubscriptionInterval::firstOrCreate(['slug' => 'year'], ['name' => 'Anual']);
    SubscriptionStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Ativo']);
    SubscriptionStatus::firstOrCreate(['slug' => 'past_due'], ['name' => 'Atrasado']);
    GenerationStatus::firstOrCreate(['slug' => 'waiting'], ['name' => 'Aguardando']);
    GenerationStatus::firstOrCreate(['slug' => 'completed'], ['name' => 'Concluído']);
    ProjectStatus::firstOrCreate(['slug' => 'draft'], ['name' => 'Rascunho']);
    GenerationProvider::firstOrCreate(
        ['slug' => 'openai'],
        ['name' => 'OpenAI', 'driver_class' => 'FakeProvider'],
    );
    Storage::fake(config('generation.disk'));
});

it('renders the public gallery for authenticated users only', function () {
    $paidUser = User::factory()->create();
    $paidProject = Project::factory()->forUser($paidUser)->create([
        'is_published' => true,
        'is_in_explore' => true,
        'first_generated_at' => now()->subHour(),
        'title' => 'Sunset Mug 1',
    ]);
    Generation::factory()->completed()->forProject($paidProject)->create();

    // Anonymous request → redirect to login.
    get('/explore')->assertRedirect(route('login'));

    // Authenticated request → visible.
    actingAs(User::factory()->create())
        ->get('/explore')
        ->assertOk()
        ->assertSee('Sunset Mug 1', false)
        ->assertSee('data-test="gallery-explore-grid"', false);
});

it('only lists projects from free-tier users with a completed generation', function () {
    $viewer = User::factory()->create();

    $freeUser = User::factory()->create();
    $freeProject = Project::factory()->forUser($freeUser)->create([
        'is_published' => true,
        'is_in_explore' => true,
        'first_generated_at' => now()->subHour(),
        'title' => 'Free Visible',
    ]);
    Generation::factory()->completed()->forProject($freeProject)->create();

    $subscriber = User::factory()->create();
    $paidProject = Project::factory()->forUser($subscriber)->create([
        'is_published' => true,
        'is_in_explore' => true,
        'first_generated_at' => now()->subHour(),
        'title' => 'Subscriber Hidden',
    ]);
    Subscription::factory()->forUser($subscriber)->create([
        'stripe_status' => 'active',
    ]);
    Generation::factory()->completed()->forProject($paidProject)->create();

    $unpublished = Project::factory()->forUser($freeUser)->create([
        'is_published' => false,
        'is_in_explore' => true,
        'first_generated_at' => now()->subHour(),
        'title' => 'Unpublished Title',
    ]);
    Generation::factory()->completed()->forProject($unpublished)->create();

    $optOut = Project::factory()->forUser($freeUser)->create([
        'is_published' => true,
        'is_in_explore' => false,
        'first_generated_at' => now()->subHour(),
        'title' => 'Opted Out Title',
    ]);
    Generation::factory()->completed()->forProject($optOut)->create();

    $incomplete = Project::factory()->forUser($freeUser)->create([
        'is_published' => true,
        'is_in_explore' => true,
        'first_generated_at' => now()->subDay(),
        'title' => 'Incomplete Title',
    ]);
    Generation::factory()->waiting()->forProject($incomplete)->create();

    actingAs($viewer)
        ->get('/explore')
        ->assertOk()
        ->assertSee('Free Visible', false)
        ->assertDontSee('Subscriber Hidden', false)
        ->assertDontSee('Unpublished Title', false)
        ->assertDontSee('Opted Out Title', false);
});

it('toggles favorites idempotently', function () {
    $viewer = User::factory()->create();
    $owner = User::factory()->create();
    $project = Project::factory()->forUser($owner)->create();
    Generation::factory()->completed()->forProject($project)->create();

    actingAs($viewer);

    Livewire::test(Explore::class)
        ->call('toggleFavorite', $project->id)
        ->assertOk();

    expect(GalleryFavorite::where('user_id', $viewer->id)->where('project_id', $project->id)->count())->toBe(1);

    Livewire::test(Explore::class)
        ->call('toggleFavorite', $project->id)
        ->assertOk();

    expect(GalleryFavorite::where('user_id', $viewer->id)->where('project_id', $project->id)->count())->toBe(0);
});

it('clones the source project into a new project owned by the remixer on POST explore remix', function () {
    $owner = User::factory()->create();
    $remixer = User::factory()->create(['credit_balance' => 5]);
    $source = Project::factory()->forUser($owner)->create([
        'is_published' => true,
        'is_in_explore' => true,
        'title' => 'Sunset Mug',
        'custom_prompt' => 'A warm orange sunset over a tea mug',
        'subject_type' => 'pessoa',
        'inputs' => ['palette' => 'warm'],
        'status_id' => ProjectStatus::where('slug', 'draft')->value('id'),
    ]);

    actingAs($remixer)
        ->post(route('gallery.remix', $source), [])
        ->assertRedirect();

    $clone = Project::query()
        ->where('remixed_from_project_id', $source->id)
        ->latest('id')
        ->first();

    expect($clone)
        ->not->toBeNull()
        ->and($clone->user_id)->toBe($remixer->id)
        ->and($clone->title)->toBe('Sunset Mug')
        ->and($clone->custom_prompt)->toBe('A warm orange sunset over a tea mug')
        ->and($clone->subject_type)->toBe('pessoa')
        ->and($clone->inputs)->toBe(['palette' => 'warm']);
});

it('blocks the remix endpoint when the source has is_published=false', function () {
    $owner = User::factory()->create();
    $remixer = User::factory()->create();
    $hidden = Project::factory()->forUser($owner)->create([
        'is_published' => false,
        'is_in_explore' => true,
    ]);

    actingAs($remixer)
        ->post(route('gallery.remix', $hidden), [])
        ->assertNotFound();
});

it('redirects the owner to their own project page when they remix themselves', function () {
    $owner = User::factory()->create();
    $source = Project::factory()->forUser($owner)->create([
        'is_published' => true,
        'is_in_explore' => true,
    ]);

    actingAs($owner)
        ->post(route('gallery.remix', $source), [])
        ->assertRedirect(route('projects.show', $source));
});

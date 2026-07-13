<?php

use App\Livewire\Projects\Show;
use App\Models\Generation;
use App\Models\GenerationStatus;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
    Storage::fake('s3');
});

function showProject(User $user, array $attributes = []): Project
{
    return Project::factory()->create(array_merge(['user_id' => $user->id], $attributes));
}

function showGeneration(Project $project, string $status, array $attributes = []): Generation
{
    $factory = Generation::factory()->state([
        'project_id' => $project->id,
        'user_id' => $project->user_id,
        'status_id' => GenerationStatus::where('slug', $status)->value('id'),
    ]);

    if ($status === 'completed') {
        $factory = $factory->completed();
    } elseif ($status === 'processing') {
        $factory = $factory->processing();
    } elseif ($status === 'failed') {
        $factory = $factory->failed($attributes['failure_reason'] ?? 'Provider unavailable');
    }

    return $factory->create($attributes);
}

test('displays latest completed inline', function (): void {
    $user = User::factory()->create();
    $project = showProject($user);
    showGeneration($project, 'waiting');
    showGeneration($project, 'processing');
    $completed = showGeneration($project, 'completed', ['result_path' => 'generations/finished.png']);

    $this->actingAs($user);

    $component = Livewire::test(Show::class, ['project' => $project]);

    $component->assertSee('Completed')
        ->assertSeeHtml('src="'.Storage::disk('s3')->url($completed->result_path).'"');

    expect($component->instance()->previewUrl())->not->toBeNull();
});

test('displays processing state when latest is processing', function (): void {
    $user = User::factory()->create();
    $project = showProject($user);
    showGeneration($project, 'processing');

    $this->actingAs($user);

    Livewire::test(Show::class, ['project' => $project])
        ->assertSee('AI Generating...')
        ->assertSee('Progress is being monitored automatically every 2 seconds.');
});

test('history orders newest first', function (): void {
    $user = User::factory()->create();
    $project = showProject($user);
    $oldest = showGeneration($project, 'waiting');
    $middle = showGeneration($project, 'processing');
    $newest = showGeneration($project, 'completed');

    $this->actingAs($user);

    Livewire::test(Show::class, ['project' => $project])
        ->assertSeeInOrder([
            "Generation #{$newest->id}",
            "Generation #{$middle->id}",
            "Generation #{$oldest->id}",
        ]);
});

test('status pills render correctly', function (): void {
    $user = User::factory()->create();
    $project = showProject($user);

    foreach (['waiting', 'processing', 'completed', 'failed'] as $status) {
        showGeneration($project, $status);
    }

    $this->actingAs($user);

    Livewire::test(Show::class, ['project' => $project])
        ->assertSee('Waiting')
        ->assertSee('Processing')
        ->assertSee('Completed')
        ->assertSee('Failed');
});

test('clicking history row swaps preview', function (): void {
    $user = User::factory()->create();
    $project = showProject($user);
    $first = showGeneration($project, 'completed');
    showGeneration($project, 'completed');

    $this->actingAs($user);

    $component = Livewire::test(Show::class, ['project' => $project])
        ->call('selectGeneration', $first->id)
        ->assertSet('selectedGenerationId', $first->id);

    expect($component->instance()->currentPreview()?->is($first))->toBeTrue();
});

test('authorizes view', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $project = showProject($owner);

    $this->actingAs($other);

    Livewire::test(Show::class, ['project' => $project])->assertForbidden();
});

test('delete soft deletes project and redirects', function (): void {
    $user = User::factory()->create();
    $project = showProject($user);

    $this->actingAs($user);

    Livewire::test(Show::class, ['project' => $project])
        ->call('delete')
        ->assertRedirect(route('dashboard'));

    expect($project->fresh()->trashed())->toBeTrue();
    $this->get(route('dashboard'))->assertSee('Project scheduled for deletion in 30 days.');
});

test('non owner cannot delete', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $project = showProject($owner);

    $this->actingAs($other);

    Livewire::test(Show::class, ['project' => $project])->assertForbidden();
});

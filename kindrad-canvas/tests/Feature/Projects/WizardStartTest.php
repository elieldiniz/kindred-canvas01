<?php

use App\Livewire\Projects\Wizard;
use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\ProjectStatus;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('clicking new project creates a draft row', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    expect(Project::where('user_id', $user->id)->count())->toBe(0);

    actingAs($user);
    $this->get(route('projects.new'))->assertSuccessful();

    $projects = Project::where('user_id', $user->id)->get();
    expect($projects)->toHaveCount(1);

    $project = $projects->first();
    expect($project->status?->slug)->toBe('draft');
    expect($project->product?->slug)->toBe('mug');
});

test('draft row belongs to current user', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSet('modeId', null)
        ->assertSet('projectId', fn ($value): bool => is_int($value) && $value > 0);

    $project = Project::where('user_id', $user->id)->first();

    expect($project)->not->toBeNull();
    expect($project->user_id)->toBe($user->id);
});

test('guest is redirected to login', function (): void {
    $this->seed(CatalogSeeder::class);

    $response = $this->get(route('projects.new'));

    $response->assertRedirect(route('login'));
});

test('next without mode does not advance and surfaces error', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSet('modeId', null)
        ->call('next')
        ->assertSet('step', 1)
        ->assertHasErrors(['modeId']);
});

test('back returns to previous step preserving state', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();

    Livewire::test(Wizard::class)
        ->assertSet('step', 1)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $category->id)
        ->assertSet('step', 3)
        ->call('back')
        ->assertSet('step', 2)
        ->call('back')
        ->assertSet('step', 1);

    $projectId = Project::where('user_id', $user->id)->value('id');
    expect($projectId)->not->toBeNull();
});

test('go to step validates range', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $component = Livewire::test(Wizard::class)
        ->call('goToStep', 99)
        ->assertSet('step', 1)
        ->call('goToStep', 0)
        ->assertSet('step', 1);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();

    $component
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->call('goToStep', 3)
        ->assertSet('step', 3);
});

test('wizard resumes at review after inputs were persisted', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class);

    $project = Project::where('user_id', $user->id)->firstOrFail();

    $project->update([
        'inputs' => [
            'name' => 'Alice',
            'phrase' => 'Happy birthday',
            'theme' => 'Flowers',
            'dedicatoria' => 'With love',
        ],
    ]);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertSet('projectId', $project->id)
        ->assertSet('inputs.name', 'Alice')
        ->assertSet('step', 7);
});

test('wizard mount publishes expected properties to project on db', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class);

    $project = Project::where('user_id', $user->id)->firstOrFail();

    expect($project->user_id)->toBe($user->id);
    expect($project->status_id)->toBe(ProjectStatus::where('slug', 'draft')->value('id'));
    expect($project->product_id)->toBe(Product::where('slug', 'mug')->value('id'));
    expect($project->inputs)->toBe([]);
    expect($project->mode_id)->toBeNull();
});

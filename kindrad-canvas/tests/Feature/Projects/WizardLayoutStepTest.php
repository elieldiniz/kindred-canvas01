<?php

use App\Livewire\Projects\Wizard;
use App\Models\Category;
use App\Models\Layout as LayoutModel;
use App\Models\LayoutStatus;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('layout filtered by style pivot', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();
    $centered = LayoutModel::where('slug', 'centered')->firstOrFail();

    $watercolor->layouts()->detach($centered->id);

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->call('selectLayout', $centered->id)
        ->assertHasErrors(['layoutId']);

    $project = Project::where('user_id', $user->id)->firstOrFail();
    expect($project->layout_id)->toBeNull();
});

test('inactive layouts are excluded', function (): void {
    $this->seed(CatalogSeeder::class);

    LayoutModel::where('slug', 'centered')->update([
        'status_id' => LayoutStatus::where('slug', 'inactive')->firstOrFail()->id,
    ]);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id);

    expect($wizard->html())->not->toContain('Centered');
});

test('empty state when no layouts', function (): void {
    $this->seed(CatalogSeeder::class);

    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();
    $watercolor->layouts()->detach();

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id);

    expect($wizard->html())->toContain('No layouts available')
        ->toContain('Edit style');
});

test('selecting layout persists and advances', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();
    $centered = LayoutModel::where('slug', 'centered')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->call('selectLayout', $centered->id)
        ->assertSet('step', 5)
        ->assertSet('layoutId', $centered->id)
        ->assertHasNoErrors();

    $project = Project::where('user_id', $user->id)->firstOrFail();

    expect($project->layout_id)->toBe($centered->id);
});

test('non owner cannot set layout', function (): void {
    $this->seed(CatalogSeeder::class);

    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    actingAs($owner);

    Livewire::test(Wizard::class)
        ->call('selectMode', ProjectMode::where('slug', 'mug')->firstOrFail()->id);

    $project = Project::where('user_id', $owner->id)->firstOrFail();
    $centered = LayoutModel::where('slug', 'centered')->firstOrFail();

    actingAs($intruder);

    Livewire::test(Wizard::class, ['projectId' => $project->id])
        ->call('selectLayout', $centered->id);

    expect(Project::find($project->id)?->layout_id)->toBeNull();
});

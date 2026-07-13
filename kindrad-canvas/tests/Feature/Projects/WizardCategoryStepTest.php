<?php

use App\Livewire\Projects\Wizard;
use App\Livewire\Projects\Wizard\Steps\Category as CategoryStep;
use App\Models\Category;
use App\Models\CategoryStatus;
use App\Models\Layout;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('category lists active mug categories', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', ProjectMode::where('slug', 'mug')->firstOrFail()->id)
        ->assertSet('step', 2);

    expect($wizard->html())->toContain('Birthday')
        ->toContain('Wedding')
        ->toContain('Pets')
        ->toContain('Family')
        ->toContain('Couples')
        ->toContain('Kids');
});

test('inactive categories are excluded', function (): void {
    $this->seed(CatalogSeeder::class);

    Category::where('slug', 'birthday')->update([
        'status_id' => CategoryStatus::where('slug', 'inactive')->firstOrFail()->id,
    ]);

    $user = User::factory()->create();

    actingAs($user);

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', ProjectMode::where('slug', 'mug')->firstOrFail()->id);

    expect($wizard->html())->not->toContain('Birthday')
        ->toContain('Wedding');
});

test('selected tile renders selection glow', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', ProjectMode::where('slug', 'mug')->firstOrFail()->id)
        ->call('selectCategory', Category::where('slug', 'birthday')->firstOrFail()->id)
        ->call('goToStep', 2)
        ->assertSet('step', 2);

    expect($wizard->html())->toContain('selection-glow')
        ->toContain('active-selection')
        ->toContain('wizard-category-tile-selected');
});

test('empty state when no categories', function (): void {
    $this->seed(CatalogSeeder::class);

    Category::query()->update([
        'status_id' => CategoryStatus::where('slug', 'inactive')->firstOrFail()->id,
    ]);

    $user = User::factory()->create();

    actingAs($user);

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', ProjectMode::where('slug', 'mug')->firstOrFail()->id);

    expect($wizard->html())->toContain('No categories available');
});

test('selecting category persists and advances', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $birthday = Category::where('slug', 'birthday')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', ProjectMode::where('slug', 'mug')->firstOrFail()->id)
        ->call('selectCategory', $birthday->id)
        ->assertSet('step', 3)
        ->assertSet('categoryId', $birthday->id)
        ->assertHasNoErrors();

    $project = Project::where('user_id', $user->id)->firstOrFail();

    expect($project->category_id)->toBe($birthday->id);
});

test('reselecting a category resets style and layout', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $wedding = Category::where('slug', 'wedding')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $style->id)
        ->call('selectLayout', $layout->id)
        ->call('selectCategory', $wedding->id)
        ->assertSet('step', 3)
        ->assertSet('categoryId', $wedding->id)
        ->assertSet('styleId', null)
        ->assertSet('layoutId', null)
        ->assertHasNoErrors();

    $project = Project::where('user_id', $user->id)->firstOrFail();

    expect($project->category_id)->toBe($wedding->id);
    expect($project->style_id)->toBeNull();
    expect($project->layout_id)->toBeNull();
});

test('non owner cannot set category', function (): void {
    $this->seed(CatalogSeeder::class);

    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    actingAs($owner);

    Livewire::test(Wizard::class)
        ->call('selectMode', ProjectMode::where('slug', 'mug')->firstOrFail()->id);

    $project = Project::where('user_id', $owner->id)->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();

    actingAs($intruder);

    Livewire::test(Wizard::class, ['projectId' => $project->id])
        ->call('selectCategory', $birthday->id);

    expect(Project::find($project->id)?->category_id)->toBeNull();
});

test('child component selectCategory dispatches event', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $birthday = Category::where('slug', 'birthday')->firstOrFail();

    Livewire::test(CategoryStep::class, ['projectId' => 1, 'categoryId' => null])
        ->assertSet('categoryId', null)
        ->call('selectCategory', $birthday->id)
        ->assertDispatched('category-selected')
        ->assertHasNoErrors();
});

test('selectCategory on child rejects invalid id', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(CategoryStep::class, ['projectId' => 1, 'categoryId' => null])
        ->call('selectCategory', 999_999)
        ->assertHasErrors(['categoryId']);
});

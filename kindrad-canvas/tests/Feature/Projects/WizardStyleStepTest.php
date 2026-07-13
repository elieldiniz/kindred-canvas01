<?php

use App\Livewire\Projects\Wizard;
use App\Models\Category;
use App\Models\Layout;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\Style as StyleModel;
use App\Models\StyleStatus;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('style filtered by category pivot', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $wedding = Category::where('slug', 'wedding')->firstOrFail();
    $watercolor = StyleModel::where('slug', 'watercolor')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->assertSet('step', 3);

    $birthdayStyles = $birthday->styles()->pluck('styles.id')->all();
    expect($birthdayStyles)->toContain($watercolor->id);

    $wedding->styles()->detach($watercolor->id);

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $wedding->id)
        ->assertSet('step', 3);

    $weddingStyles = $wedding->styles()->pluck('styles.id')->all();
    expect($weddingStyles)->not->toContain($watercolor->id);
});

test('inactive styles are excluded', function (): void {
    $this->seed(CatalogSeeder::class);

    StyleModel::where('slug', 'watercolor')->update([
        'status_id' => StyleStatus::where('slug', 'inactive')->firstOrFail()->id,
    ]);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id);

    expect($wizard->html())->not->toContain('Watercolor');
});

test('empty state when no styles for category', function (): void {
    $this->seed(CatalogSeeder::class);

    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $birthday->styles()->detach();

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id);

    expect($wizard->html())->toContain('No styles available for this category')
        ->toContain('Browse other categories');
});

test('selecting style persists and advances', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = StyleModel::where('slug', 'watercolor')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->assertSet('step', 4)
        ->assertSet('styleId', $watercolor->id)
        ->assertHasNoErrors();

    $project = Project::where('user_id', $user->id)->firstOrFail();

    expect($project->style_id)->toBe($watercolor->id);
});

test('selecting style resets layout', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = StyleModel::where('slug', 'watercolor')->firstOrFail();
    $cartoon = StyleModel::where('slug', 'cartoon')->firstOrFail();
    $centered = Layout::where('slug', 'centered')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->call('selectLayout', $centered->id)
        ->call('selectStyle', $cartoon->id)
        ->assertSet('styleId', $cartoon->id)
        ->assertSet('layoutId', null)
        ->assertHasNoErrors();

    $project = Project::where('user_id', $user->id)->firstOrFail();

    expect($project->style_id)->toBe($cartoon->id);
    expect($project->layout_id)->toBeNull();
});

test('style not in pivot is blocked', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $cartoon = StyleModel::where('slug', 'cartoon')->firstOrFail();

    $birthday->styles()->detach($cartoon->id);

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $cartoon->id)
        ->assertHasErrors(['styleId']);

    $project = Project::where('user_id', $user->id)->firstOrFail();
    expect($project->style_id)->toBeNull();
});

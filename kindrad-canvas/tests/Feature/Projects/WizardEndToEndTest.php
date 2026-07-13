<?php

use App\Livewire\Projects\Wizard;
use App\Models\Category;
use App\Models\Layout;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('full wizard completes without errors', function (): void {
    $user = User::factory()->withCredits(5)->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();

    $wizard = Livewire::test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSet('modeId', null)
        ->assertSet('sourceImageId', null);

    $projectId = (int) $wizard->get('projectId');
    expect($projectId)->toBeGreaterThan(0);

    $wizard
        ->call('selectMode', $mug->id)
        ->assertSet('step', 2)
        ->assertSet('modeId', $mug->id)
        ->call('selectCategory', $category->id)
        ->assertSet('step', 3)
        ->assertSet('categoryId', $category->id)
        ->call('selectStyle', $style->id)
        ->assertSet('step', 4)
        ->assertSet('styleId', $style->id)
        ->call('selectLayout', $layout->id)
        ->assertSet('step', 5)
        ->assertSet('layoutId', $layout->id)
        ->call('next')
        ->assertSet('step', 6)
        ->assertHasNoErrors()
        ->set('inputs', [
            'name' => 'Alice',
            'phrase' => 'hi',
            'theme' => null,
            'dedicatoria' => null,
        ])
        ->call('next')
        ->assertSet('step', 7)
        ->assertHasNoErrors();

    expect($wizard->get('sourceImageId'))->toBeNull();

    $project = Project::find($projectId);

    expect($project)->not->toBeNull()
        ->and($project->mode_id)->toBe($mug->id)
        ->and($project->category_id)->toBe($category->id)
        ->and($project->style_id)->toBe($style->id)
        ->and($project->layout_id)->toBe($layout->id)
        ->and($project->source_image_id)->toBeNull()
        ->and($project->inputs)->toBe([
            'name' => 'Alice',
            'phrase' => 'hi',
            'theme' => null,
            'dedicatoria' => null,
        ]);

    $html = html_entity_decode($wizard->html(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    expect($html)
        ->toContain('data-test="wizard-review-edit-mode"')
        ->toContain('data-test="wizard-review-edit-category"')
        ->toContain('data-test="wizard-review-edit-style"')
        ->toContain('data-test="wizard-review-edit-layout"')
        ->toContain('data-test="wizard-review-edit-source-image"')
        ->toContain('data-test="wizard-review-edit-inputs"')
        ->toContain('data-test="wizard-review-generate"');
});

test('final project state matches all selections', function (): void {
    $user = User::factory()->withCredits(5)->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();
    $style = Style::where('slug', 'watercolor')->firstOrFail();
    $layout = Layout::where('slug', 'centered')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $category->id)
        ->call('selectStyle', $style->id)
        ->call('selectLayout', $layout->id)
        ->call('next')
        ->set('inputs', [
            'name' => 'Alice',
            'phrase' => 'hi',
            'theme' => null,
            'dedicatoria' => null,
        ])
        ->call('next')
        ->assertSet('step', 7);

    $project = Project::where('user_id', $user->id)->firstOrFail();

    expect($project->mode_id)->toBe($mug->id)
        ->and($project->category_id)->toBe($category->id)
        ->and($project->style_id)->toBe($style->id)
        ->and($project->layout_id)->toBe($layout->id)
        ->and($project->source_image_id)->toBeNull();

    expect($project->fresh()->inputs)->toBe([
        'name' => 'Alice',
        'phrase' => 'hi',
        'theme' => null,
        'dedicatoria' => null,
    ]);

    expect($project->mode_id)->toBe($mug->id);

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'user_id' => $user->id,
        'mode_id' => $mug->id,
        'category_id' => $category->id,
        'style_id' => $style->id,
        'layout_id' => $layout->id,
        'source_image_id' => null,
    ]);
});

test('guest cannot reach projects new', function (): void {
    $this->get(route('projects.new'))->assertRedirect(route('login'));
});

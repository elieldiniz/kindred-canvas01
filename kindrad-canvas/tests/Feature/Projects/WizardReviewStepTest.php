<?php

use App\Jobs\GenerateArtworkJob;
use App\Livewire\Projects\Wizard;
use App\Livewire\Projects\Wizard\Steps\Review as ReviewStep;
use App\Models\Category;
use App\Models\Layout;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\SourceImage;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Storage::fake('s3');
    $this->seed(CatalogSeeder::class);
});

function reviewWizard(User $user): array
{
    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();
    $centered = Layout::where('slug', 'centered')->firstOrFail();

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->call('selectLayout', $centered->id)
        ->set('inputs', [
            'name' => 'Alice',
            'phrase' => null,
            'theme' => null,
            'dedicatoria' => null,
        ])
        ->call('next')
        ->assertSet('step', 6)
        ->call('next')
        ->assertSet('step', 7);

    $project = Project::where('user_id', $user->id)->firstOrFail()->refresh();

    return ['wizard' => $wizard, 'project' => $project];
}

test('review renders six section rows with edit buttons', function (): void {
    $user = User::factory()->create();

    ['project' => $project] = reviewWizard($user);

    $project->update(['inputs' => [
        'name' => 'Alice',
        'phrase' => 'Hello world',
        'theme' => 'Floral',
        'dedicatoria' => 'For mom',
    ]]);

    Livewire::test(ReviewStep::class, [
        'projectId' => $project->id,
        'modeId' => $project->mode_id,
        'categoryId' => $project->category_id,
        'styleId' => $project->style_id,
        'layoutId' => $project->layout_id,
        'sourceImageId' => $project->source_image_id,
        'inputs' => $project->inputs ?? [],
    ])
        ->assertSeeHtml('data-test="wizard-review-mode"')
        ->assertSeeHtml('data-test="wizard-review-category"')
        ->assertSeeHtml('data-test="wizard-review-style"')
        ->assertSeeHtml('data-test="wizard-review-layout"')
        ->assertSeeHtml('data-test="wizard-review-source-image"')
        ->assertSeeHtml('data-test="wizard-review-inputs"')
        ->assertSeeHtml('data-test="wizard-review-edit-mode"')
        ->assertSeeHtml('data-test="wizard-review-edit-category"')
        ->assertSeeHtml('data-test="wizard-review-edit-style"')
        ->assertSeeHtml('data-test="wizard-review-edit-layout"')
        ->assertSeeHtml('data-test="wizard-review-edit-source-image"')
        ->assertSeeHtml('data-test="wizard-review-edit-inputs"');
});

test('review renders inputs section with truncated values', function (): void {
    $user = User::factory()->create();

    ['project' => $project] = reviewWizard($user);

    $project->update(['inputs' => [
        'name' => 'Alice',
        'phrase' => 'a very long phrase that exceeds the typical visual limit for inline display',
        'theme' => 'Dark Floral',
        'dedicatoria' => 'For my dear mother, with love.',
    ]]);

    Livewire::test(ReviewStep::class, [
        'projectId' => $project->id,
        'modeId' => $project->mode_id,
        'categoryId' => $project->category_id,
        'styleId' => $project->style_id,
        'layoutId' => $project->layout_id,
        'sourceImageId' => $project->source_image_id,
        'inputs' => $project->inputs ?? [],
    ])
        ->assertSee('Alice')
        ->assertSee('a very long phrase that exceeds the typical visual limit for inline display')
        ->assertSee('Dark Floral')
        ->assertSee('For my dear mother, with love.');
});

test('review renders source image thumbnail when set', function (): void {
    $user = User::factory()->create();

    ['project' => $project] = reviewWizard($user);

    $image = SourceImage::create([
        'user_id' => $user->id,
        'disk' => 's3',
        'path' => 'source-images/'.$user->id.'/photo.jpg',
        'original_filename' => 'photo.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1024,
    ]);

    $project->update(['source_image_id' => $image->id]);

    Storage::disk('s3')->put($image->path, 'fake-image-bytes');

    Livewire::test(ReviewStep::class, [
        'projectId' => $project->id,
        'modeId' => $project->mode_id,
        'categoryId' => $project->category_id,
        'styleId' => $project->style_id,
        'layoutId' => $project->layout_id,
        'sourceImageId' => $image->id,
        'inputs' => $project->inputs ?? [],
    ])
        ->assertSeeHtml('data-test="wizard-review-source-image-thumb"')
        ->assertSeeHtml('data-test="wizard-review-source-image-name"')
        ->assertSee('photo.jpg');
});

test('review renders skipped label when no source image', function (): void {
    $user = User::factory()->create();

    ['project' => $project] = reviewWizard($user);

    expect($project->source_image_id)->toBeNull();

    Livewire::test(ReviewStep::class, [
        'projectId' => $project->id,
        'modeId' => $project->mode_id,
        'categoryId' => $project->category_id,
        'styleId' => $project->style_id,
        'layoutId' => $project->layout_id,
        'sourceImageId' => null,
        'inputs' => $project->inputs ?? [],
    ])
        ->assertSeeHtml('data-test="wizard-review-source-image-skipped"')
        ->assertSee('Skipped (no image)');
});

test('generate button is disabled when no credits', function (): void {
    $user = User::factory()->withCredits(0)->create();

    ['project' => $project] = reviewWizard($user);

    $component = Livewire::test(ReviewStep::class, [
        'projectId' => $project->id,
        'modeId' => $project->mode_id,
        'categoryId' => $project->category_id,
        'styleId' => $project->style_id,
        'layoutId' => $project->layout_id,
        'sourceImageId' => $project->source_image_id,
        'inputs' => $project->inputs ?? [],
    ]);

    $html = $component->html();
    $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    expect($decoded)->toContain('data-test="wizard-review-generate"')
        ->and($decoded)->toContain('disabled')
        ->and($decoded)->toContain("title=\"You're out of credits\"")
        ->and($decoded)->toContain("You're out of credits");
});

test('generate button is enabled with credits', function (): void {
    $user = User::factory()->withCredits(5)->create();

    ['project' => $project] = reviewWizard($user);

    Livewire::test(ReviewStep::class, [
        'projectId' => $project->id,
        'modeId' => $project->mode_id,
        'categoryId' => $project->category_id,
        'styleId' => $project->style_id,
        'layoutId' => $project->layout_id,
        'sourceImageId' => $project->source_image_id,
        'inputs' => $project->inputs ?? [],
    ])
        ->assertSeeHtml('data-test="wizard-review-generate"')
        ->assertDontSeeHtml('data-test="wizard-review-generate" disabled', true);
});

test('submit debits one credit and dispatches generation job', function (): void {
    Bus::fake();

    $user = User::factory()->withCredits(5)->create();

    ['wizard' => $wizard, 'project' => $project] = reviewWizard($user);

    $balanceBefore = (int) $user->fresh()->credit_balance;

    $wizard
        ->call('submit')
        ->assertRedirect(route('projects.show', ['project' => $project->id]));

    expect((int) $user->fresh()->credit_balance)->toBe($balanceBefore - 1);

    Bus::assertDispatched(GenerateArtworkJob::class);
});

test('submit action blocks when credit balance zero', function (): void {
    $user = User::factory()->withCredits(0)->create();

    actingAs($user);

    $wizard = Livewire::test(Wizard::class, ['id' => null]);

    Project::where('user_id', $user->id)->firstOrFail();

    $wizard
        ->call('goToStep', 7)
        ->assertHasErrors(['wizard']);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();
    $centered = Layout::where('slug', 'centered')->firstOrFail();

    $wizard
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->call('selectLayout', $centered->id)
        ->set('inputs', ['name' => 'Alice'])
        ->call('next')
        ->call('next')
        ->assertSet('step', 7)
        ->call('submit')
        ->assertHasErrors(['generate'])
        ->assertRedirect(route('dashboard'));
});

test('edit button navigates to step preserving state', function (): void {
    $user = User::factory()->create();

    ['wizard' => $wizard] = reviewWizard($user);

    $wizard
        ->dispatch('go-to-step', step: 2)
        ->assertSet('step', 2);

    $project = Project::where('user_id', $user->id)->firstOrFail();
    expect($project->mode_id)->not->toBeNull();
    expect($project->category_id)->not->toBeNull();
    expect($project->style_id)->not->toBeNull();
    expect($project->layout_id)->not->toBeNull();
    expect($project->inputs)->not->toBe([]);
});

test('go to step seven requires layout', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();

    $wizard = Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->assertSet('step', 4);

    $wizard
        ->call('goToStep', 7)
        ->assertSet('step', 4)
        ->assertHasErrors(['wizard']);
});

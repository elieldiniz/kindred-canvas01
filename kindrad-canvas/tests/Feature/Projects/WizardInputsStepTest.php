<?php

use App\Livewire\Projects\Wizard;
use App\Livewire\Projects\Wizard\Steps\Inputs as InputsStep;
use App\Models\Category;
use App\Models\Layout;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function step6Wizard(User $user): array
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
        ->call('next')
        ->assertSet('step', 6);

    $project = Project::where('user_id', $user->id)->firstOrFail();

    return ['wizard' => $wizard, 'project' => $project->refresh()];
}

test('step 6 renders four inputs', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    ['project' => $project] = step6Wizard($user);

    Livewire::test(InputsStep::class, ['projectId' => $project->id, 'inputs' => []])
        ->assertSee('Name')
        ->assertSee('Phrase')
        ->assertSee('Theme')
        ->assertSee('Dedicatoria');
});

test('step 6 renders maxlength counters', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    ['project' => $project] = step6Wizard($user);

    Livewire::test(InputsStep::class, ['projectId' => $project->id, 'inputs' => []])
        ->assertSeeHtml('maxlength="80"')
        ->assertSeeHtml('maxlength="240"')
        ->assertSeeHtml('maxlength="120"')
        ->assertSeeHtml('maxlength="500"')
        ->assertSee('0/80')
        ->assertSee('0/240')
        ->assertSee('0/120')
        ->assertSee('0/500')
        ->set('name', 'Alice')
        ->assertSee('5/80');
});

test('update input persists to state', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    ['wizard' => $wizard] = step6Wizard($user);

    $wizard->call('updateInput', 'name', 'Alice')
        ->assertSet('inputs.name', 'Alice');
});

test('unknown keys are ignored', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    ['wizard' => $wizard] = step6Wizard($user);

    $wizard->set('inputs', ['name' => 'Alice'])
        ->call('updateInput', 'evil', 'x');

    expect($wizard->get('inputs'))->toBe(['name' => 'Alice'])
        ->and($wizard->get('inputs'))->not->toHaveKey('evil');
});

test('empty name blocks continue', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    ['wizard' => $wizard, 'project' => $project] = step6Wizard($user);

    $wizard
        ->set('inputs', [
            'name' => '',
            'phrase' => 'A short phrase',
            'theme' => 'Floral',
            'dedicatoria' => 'For mom',
        ])
        ->call('next')
        ->assertSet('step', 6)
        ->assertHasErrors(['inputs.name']);

    $project->refresh();
    expect($project->inputs)->toBe([]);
});

test('oversized phrase blocks continue', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    ['wizard' => $wizard, 'project' => $project] = step6Wizard($user);

    $wizard
        ->set('inputs', [
            'name' => 'Alice',
            'phrase' => str_repeat('a', 241),
            'theme' => null,
            'dedicatoria' => null,
        ])
        ->call('next')
        ->assertSet('step', 6)
        ->assertHasErrors(['inputs.phrase']);

    $project->refresh();
    expect($project->inputs)->toBe([]);
});

test('valid inputs persist as json', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    ['wizard' => $wizard, 'project' => $project] = step6Wizard($user);

    $wizard
        ->set('inputs', [
            'name' => 'Alice',
            'phrase' => 'Hello world',
            'theme' => 'Floral',
            'dedicatoria' => 'For mom',
        ])
        ->call('next')
        ->assertSet('step', 7)
        ->assertHasNoErrors();

    $project->refresh();
    expect($project->inputs)->toBe([
        'name' => 'Alice',
        'phrase' => 'Hello world',
        'theme' => 'Floral',
        'dedicatoria' => 'For mom',
    ]);
});

test('invalid input does not mutate earlier steps', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    ['wizard' => $wizard, 'project' => $project] = step6Wizard($user);

    $modeId = $project->mode_id;
    $categoryId = $project->category_id;
    $styleId = $project->style_id;
    $layoutId = $project->layout_id;

    $wizard
        ->set('inputs', [
            'name' => '',
            'phrase' => str_repeat('p', 500),
            'theme' => null,
            'dedicatoria' => null,
        ])
        ->call('next')
        ->assertSet('step', 6)
        ->assertHasErrors(['inputs.name']);

    $project->refresh();
    expect($project->mode_id)->toBe($modeId);
    expect($project->category_id)->toBe($categoryId);
    expect($project->style_id)->toBe($styleId);
    expect($project->layout_id)->toBe($layoutId);
    expect($project->inputs)->toBe([]);
});

test('non owner cannot update inputs', function (): void {
    $this->seed(CatalogSeeder::class);

    $owner = User::factory()->create();
    ['project' => $project] = step6Wizard($owner);

    $intruder = User::factory()->create();
    actingAs($intruder);

    Livewire::test(Wizard::class, ['projectId' => $project->id])
        ->call('updateInput', 'name', 'Mallory');

    $project->refresh();
    expect($project->inputs)->toBe([]);
});

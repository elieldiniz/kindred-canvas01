<?php

use App\Livewire\Projects\Wizard;
use App\Models\Category;
use App\Models\Layout;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\SourceImage;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

function readyProject(User $user): Project
{
    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();
    $centered = Layout::where('slug', 'centered')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->call('selectLayout', $centered->id)
        ->assertSet('step', 5);

    return Project::where('user_id', $user->id)->firstOrFail();
}
test('reauthorize on mount blocks non owner', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $project = readyProject($owner);

    actingAs($intruder);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertStatus(403);

    expect(Project::find($project->id)?->mode_id)->not->toBeNull();
});

test('admin can view other users draft', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $project = readyProject($owner);

    actingAs($admin);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertSet('projectId', $project->id)
        ->assertSet('step', 5)
        ->assertHasNoErrors();
});

test('hydrate rejects non owner via subsequent call', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $project = readyProject($owner);

    actingAs($intruder);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertStatus(403);

    expect(Project::find($project->id)?->mode_id)->toBe(
        ProjectMode::where('slug', 'mug')->firstOrFail()->id,
    );
});

test('all wizard actions reject non owners via mount authz', function (): void {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $project = readyProject($owner);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();
    $centered = Layout::where('slug', 'centered')->firstOrFail();

    actingAs($intruder);

    $calls = ['selectMode', 'selectCategory', 'selectStyle', 'selectLayout', 'saveSourceImage', 'removeSourceImage', 'updateInput', 'next', 'back', 'goToStep', 'submit', 'exit'];

    foreach ($calls as $method) {
        Livewire::test(Wizard::class, ['id' => $project->id])
            ->assertStatus(403);
    }

    $project->refresh();
    expect($project->mode_id)->toBe($mug->id);
    expect($project->category_id)->toBe($birthday->id);
    expect($project->style_id)->toBe($watercolor->id);
    expect($project->layout_id)->toBe($centered->id);
    expect($project->source_image_id)->toBeNull();
    expect($project->inputs)->toBe([]);
});

test('admin can execute all wizard actions', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $project = readyProject($owner);

    $free = ProjectMode::where('slug', 'free')->firstOrFail();
    $wedding = Category::where('slug', 'wedding')->firstOrFail();
    $cartoon = Style::where('slug', 'cartoon')->firstOrFail();
    $borderWrap = Layout::where('slug', 'border_wrap')->firstOrFail();

    $image = SourceImage::create([
        'user_id' => $owner->id,
        'disk' => 's3',
        'path' => 'source-images/1/test.jpg',
        'original_filename' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'size_bytes' => 1024,
    ]);

    actingAs($admin);

    $wizard = Livewire::test(Wizard::class, ['id' => $project->id]);

    $wizard->call('selectMode', $free->id);
    $wizard->call('selectCategory', $wedding->id);
    $wizard->call('selectStyle', $cartoon->id);
    $wizard->call('selectLayout', $borderWrap->id);
    $wizard->call('updateInput', 'name', 'Admin was here');
    $wizard->set('inputs', ['name' => 'Admin was here', 'phrase' => null, 'theme' => null, 'dedicatoria' => null]);
    $wizard->call('next');
    $wizard->call('next');
    $wizard->call('back');
    $wizard->call('goToStep', 5);
    $wizard->call('saveSourceImage', $image->id);
    $wizard->call('removeSourceImage');

    $project->refresh();
    expect($project->mode_id)->toBe($free->id);
    expect($project->category_id)->toBe($wedding->id);
    expect($project->style_id)->toBe($cartoon->id);
    expect($project->layout_id)->toBe($borderWrap->id);
    expect($project->source_image_id)->toBeNull();
    expect($project->inputs)->toMatchArray(['name' => 'Admin was here']);

    $wizard->call('exit')->assertRedirect(route('dashboard'));
});

test('soft deleted project returns 404 on mount', function (): void {
    $owner = User::factory()->create();
    $project = readyProject($owner);

    $project->delete();

    actingAs($owner);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertStatus(404);
});

test('soft deleted project returns 404 on action', function (): void {
    $owner = User::factory()->create();
    $project = readyProject($owner);

    $project->delete();

    actingAs($owner);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertStatus(404);
});

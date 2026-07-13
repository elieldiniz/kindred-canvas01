<?php

use App\Livewire\Projects\Wizard;
use App\Livewire\Projects\Wizard\Steps\SourceImage as SourceImageStep;
use App\Models\Category;
use App\Models\Layout;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\SourceImage;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    Storage::fake('s3');
    $this->seed(CatalogSeeder::class);
});

function wizardReadyProject(User $user): Project
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

test('accepts valid image and creates source image', function (): void {
    $user = User::factory()->create();
    $project = wizardReadyProject($user);

    expect(SourceImage::count())->toBe(0);
    expect($project->source_image_id)->toBeNull();

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600)->size(2048);

    Livewire::test(SourceImageStep::class, ['projectId' => $project->id, 'sourceImageId' => null])
        ->set('photo', $file)
        ->assertHasNoErrors()
        ->assertSet('sourceImageId', fn ($v): bool => is_int($v) && $v > 0);

    expect(SourceImage::count())->toBe(1);

    $image = SourceImage::firstOrFail();
    expect($image->user_id)->toBe($user->id);
    expect($image->disk)->toBe('s3');
    expect($image->original_filename)->toBe('photo.jpg');
    expect($image->mime_type)->toBe('image/jpeg');
    expect($image->size_bytes)->toBeGreaterThan(0);

    Storage::disk('s3')->assertExists($image->path);

    $project->refresh();
    expect($project->source_image_id)->toBe($image->id);
});

test('rejects oversized file with validation error', function (): void {
    $user = User::factory()->create();
    $project = wizardReadyProject($user);

    expect(SourceImage::count())->toBe(0);

    $file = UploadedFile::fake()->create('big.jpg', 12 * 1024, 'image/jpeg');

    Livewire::test(SourceImageStep::class, ['projectId' => $project->id, 'sourceImageId' => null])
        ->set('photo', $file)
        ->assertHasErrors(['photo']);

    expect(SourceImage::count())->toBe(0);

    $project->refresh();
    expect($project->source_image_id)->toBeNull();
});

test('rejects invalid mime type', function (): void {
    $user = User::factory()->create();
    $project = wizardReadyProject($user);

    $file = UploadedFile::fake()->create('meme.gif', 1024, 'image/gif');

    Livewire::test(SourceImageStep::class, ['projectId' => $project->id, 'sourceImageId' => null])
        ->set('photo', $file)
        ->assertHasErrors(['photo']);

    expect(SourceImage::count())->toBe(0);

    $project->refresh();
    expect($project->source_image_id)->toBeNull();
});

test('non owner cannot upload image', function (): void {
    $owner = User::factory()->create();
    $project = wizardReadyProject($owner);

    $intruder = User::factory()->create();

    actingAs($intruder);

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600)->size(2048);

    Livewire::test(SourceImageStep::class, ['projectId' => $project->id, 'sourceImageId' => null])
        ->set('photo', $file)
        ->assertForbidden();

    expect(SourceImage::count())->toBe(0);

    $project->refresh();
    expect($project->source_image_id)->toBeNull();
});

test('replace creates new source image row', function (): void {
    $user = User::factory()->create();
    $project = wizardReadyProject($user);

    $first = UploadedFile::fake()->image('first.jpg', 800, 600)->size(2048);

    $component = Livewire::test(SourceImageStep::class, ['projectId' => $project->id, 'sourceImageId' => null])
        ->set('photo', $first);

    expect(SourceImage::count())->toBe(1);

    $firstImage = SourceImage::firstOrFail();
    $project->refresh();
    expect($project->source_image_id)->toBe($firstImage->id);

    $component->call('replace')
        ->assertSet('photo', null);

    $second = UploadedFile::fake()->image('second.jpg', 800, 600)->size(2048);
    $component->set('photo', $second);

    expect(SourceImage::count())->toBe(2);

    $project->refresh();
    expect(SourceImage::pluck('id')->all())->toContain($firstImage->id);

    $secondImage = SourceImage::where('id', '!=', $firstImage->id)->firstOrFail();
    expect($project->source_image_id)->toBe($secondImage->id);
    Storage::disk('s3')->assertExists($firstImage->path);
    Storage::disk('s3')->assertExists($secondImage->path);
});

test('remove clears source image id', function (): void {
    $user = User::factory()->create();
    $project = wizardReadyProject($user);

    $component = Livewire::test(SourceImageStep::class, ['projectId' => $project->id, 'sourceImageId' => null])
        ->set('photo', UploadedFile::fake()->image('photo.jpg', 800, 600)->size(2048));

    expect(SourceImage::count())->toBe(1);
    $imageId = SourceImage::firstOrFail()->id;

    $project->refresh();
    expect($project->source_image_id)->toBe($imageId);

    $component->call('remove');

    $project->refresh();
    expect($project->source_image_id)->toBeNull();
    expect(SourceImage::count())->toBe(1);
});

test('skip advances without upload', function (): void {
    $user = User::factory()->create();
    $project = wizardReadyProject($user);

    expect(SourceImage::count())->toBe(0);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertSet('step', 5)
        ->call('next')
        ->assertSet('step', 6)
        ->assertHasNoErrors();

    $project->refresh();
    expect($project->source_image_id)->toBeNull();
    expect(SourceImage::count())->toBe(0);
});

test('back from step 6 preserves source image', function (): void {
    $user = User::factory()->create();
    $project = wizardReadyProject($user);

    $component = Livewire::test(SourceImageStep::class, ['projectId' => $project->id, 'sourceImageId' => null])
        ->set('photo', UploadedFile::fake()->image('photo.jpg', 800, 600)->size(2048));

    $imageId = SourceImage::firstOrFail()->id;
    $project->refresh();
    expect($project->source_image_id)->toBe($imageId);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertSet('step', 5)
        ->call('next')
        ->assertSet('step', 6)
        ->call('back')
        ->assertSet('step', 5)
        ->assertSet('sourceImageId', $imageId);

    $project->refresh();
    expect($project->source_image_id)->toBe($imageId);
});

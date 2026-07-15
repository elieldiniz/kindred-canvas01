<?php

use App\Models\Generation;
use App\Models\Project;
use App\Models\ProjectPhoto;
use App\Models\SourceImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
    Storage::fake('s3');
    Carbon::setTestNow('2026-07-13 12:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function deletedProject(int $daysAgo, array $attributes = []): Project
{
    $project = Project::factory()->create($attributes);
    $project->delete();
    $project->forceFill(['deleted_at' => now()->subDays($daysAgo)])->saveQuietly();

    return $project;
}

test('purges only older than 30 days', function (): void {
    $old = deletedProject(31);
    $recent = deletedProject(5);

    $this->artisan('projects:purge-deleted')
        ->expectsOutput('Purged 1 projects.')
        ->assertSuccessful();

    $this->assertModelMissing($old);
    expect(Project::withTrashed()->find($recent->id))->not->toBeNull();
});

test('removes s3 files for project photos and generations', function (): void {
    $user = User::factory()->create();
    $sourceImage = SourceImage::factory()->create([
        'user_id' => $user->id,
        'path' => 'source-images/source.jpg',
    ]);
    $project = deletedProject(31, ['user_id' => $user->id]);
    ProjectPhoto::create([
        'project_id' => $project->id,
        'source_image_id' => $sourceImage->id,
        'position' => 0,
    ]);

    $generation = Generation::factory()->completed()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
        'result_path' => 'generations/result.png',
    ]);
    Storage::disk('s3')->put($sourceImage->path, 'source');
    Storage::disk('s3')->put($generation->result_path, 'result');

    $this->artisan('projects:purge-deleted')->assertSuccessful();

    Storage::disk('s3')->assertMissing($sourceImage->path);
    Storage::disk('s3')->assertMissing($generation->result_path);
    $this->assertModelMissing($sourceImage);
    $this->assertModelMissing($generation);
    $this->assertDatabaseMissing('project_photos', ['project_id' => $project->id]);
});

test('removes multiple project photos in order', function (): void {
    $user = User::factory()->create();
    $img1 = SourceImage::factory()->create(['user_id' => $user->id, 'path' => 'source-images/img1.jpg']);
    $img2 = SourceImage::factory()->create(['user_id' => $user->id, 'path' => 'source-images/img2.jpg']);
    $project = deletedProject(31, ['user_id' => $user->id]);

    ProjectPhoto::create(['project_id' => $project->id, 'source_image_id' => $img1->id, 'position' => 0]);
    ProjectPhoto::create(['project_id' => $project->id, 'source_image_id' => $img2->id, 'position' => 1]);

    Storage::disk('s3')->put($img1->path, 'one');
    Storage::disk('s3')->put($img2->path, 'two');

    $this->artisan('projects:purge-deleted')->assertSuccessful();

    Storage::disk('s3')->assertMissing($img1->path);
    Storage::disk('s3')->assertMissing($img2->path);
    $this->assertModelMissing($img1);
    $this->assertModelMissing($img2);
});

test('handles projects with no photos gracefully', function (): void {
    deletedProject(31);

    $this->artisan('projects:purge-deleted')
        ->expectsOutput('Purged 1 projects.')
        ->assertSuccessful();
});

test('idempotent re runs', function (): void {
    deletedProject(31);

    $this->artisan('projects:purge-deleted')
        ->expectsOutput('Purged 1 projects.')
        ->assertSuccessful();

    $this->artisan('projects:purge-deleted')
        ->expectsOutput('Purged 0 projects.')
        ->assertSuccessful();
});

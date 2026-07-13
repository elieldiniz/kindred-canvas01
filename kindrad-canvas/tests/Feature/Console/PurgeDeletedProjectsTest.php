<?php

use App\Models\Generation;
use App\Models\Project;
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

test('removes s3 files', function (): void {
    $user = User::factory()->create();
    $sourceImage = SourceImage::factory()->create([
        'user_id' => $user->id,
        'path' => 'source-images/source.jpg',
    ]);
    $project = deletedProject(31, [
        'user_id' => $user->id,
        'source_image_id' => $sourceImage->id,
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

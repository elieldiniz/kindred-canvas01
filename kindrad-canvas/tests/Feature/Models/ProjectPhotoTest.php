<?php

use App\Models\Project;
use App\Models\ProjectPhoto;
use App\Models\SourceImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Database\QueryException;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('project photo belongs to project and source image', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $source = SourceImage::factory()->for($user)->create();

    $photo = ProjectPhoto::create([
        'project_id' => $project->id,
        'source_image_id' => $source->id,
        'position' => 0,
    ]);

    expect($photo->project->id)->toBe($project->id);
    expect($photo->sourceImage->id)->toBe($source->id);
});

test('ordered scope returns photos by position', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $source1 = SourceImage::factory()->for($user)->create();
    $source2 = SourceImage::factory()->for($user)->create();

    ProjectPhoto::create(['project_id' => $project->id, 'source_image_id' => $source2->id, 'position' => 1]);
    ProjectPhoto::create(['project_id' => $project->id, 'source_image_id' => $source1->id, 'position' => 0]);

    $photos = $project->photos()->get();
    expect($photos->first()->position)->toBe(0);
    expect($photos->last()->position)->toBe(1);
});

test('project photos relationship returns ordered photos with source images', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $source = SourceImage::factory()->for($user)->create();

    $project->photos()->create(['source_image_id' => $source->id, 'position' => 0]);

    expect($project->photos()->count())->toBe(1);
    expect($project->photos()->first()->sourceImage->id)->toBe($source->id);
});

test('unique constraint on (project_id, source_image_id) prevents duplicates', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $source = SourceImage::factory()->for($user)->create();

    $project->photos()->create(['source_image_id' => $source->id, 'position' => 0]);

    expect(fn () => $project->photos()->create(['source_image_id' => $source->id, 'position' => 1]))
        ->toThrow(QueryException::class);
});

test('unique constraint on (project_id, position) prevents same position', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $source1 = SourceImage::factory()->for($user)->create();
    $source2 = SourceImage::factory()->for($user)->create();

    $project->photos()->create(['source_image_id' => $source1->id, 'position' => 0]);

    expect(fn () => $project->photos()->create(['source_image_id' => $source2->id, 'position' => 0]))
        ->toThrow(QueryException::class);
});

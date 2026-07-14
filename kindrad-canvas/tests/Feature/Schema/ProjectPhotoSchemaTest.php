<?php

use App\Models\Project;
use App\Models\SourceImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('project_photos table has expected columns', function (): void {
    $columns = Schema::getColumns('project_photos');

    expect(collect($columns)->pluck('name')->all())
        ->toContain('id', 'project_id', 'source_image_id', 'position', 'created_at', 'updated_at');
});

test('project_photos has unique constraint on (project_id, source_image_id)', function (): void {
    $indexes = collect(Schema::getIndexes('project_photos'))
        ->first(fn (array $i): bool => $i['columns'] === ['project_id', 'source_image_id']);

    expect($indexes)->not->toBeNull();
    expect($indexes['unique'])->toBeTrue();
});

test('project_photos has unique constraint on (project_id, position)', function (): void {
    $indexes = collect(Schema::getIndexes('project_photos'))
        ->first(fn (array $i): bool => $i['columns'] === ['project_id', 'position']);

    expect($indexes)->not->toBeNull();
    expect($indexes['unique'])->toBeTrue();
});

test('project_photos cascades on project delete', function (): void {
    $project = Project::factory()->create();
    $source = SourceImage::factory()->for($project->user)->create();
    $project->photos()->create(['source_image_id' => $source->id, 'position' => 0]);

    $photoId = $project->photos()->first()->id;
    expect(DB::table('project_photos')->where('id', $photoId)->exists())->toBeTrue();

    $project->forceDelete();

    expect(DB::table('project_photos')->where('id', $photoId)->exists())->toBeFalse();
});

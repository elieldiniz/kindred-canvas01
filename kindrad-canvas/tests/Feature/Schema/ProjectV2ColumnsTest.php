<?php

use App\Models\Pose;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('projects table has subject_type column', function (): void {
    $columns = collect(Schema::getColumns('projects'))->pluck('name');

    expect($columns)->toContain('subject_type');
});

test('projects table has custom_prompt column', function (): void {
    $columns = collect(Schema::getColumns('projects'))->pluck('name');

    expect($columns)->toContain('custom_prompt');
});

test('projects table has pose_id column with FK to poses', function (): void {
    $columns = collect(Schema::getColumns('projects'))->pluck('name');
    expect($columns)->toContain('pose_id');

    $fk = collect(Schema::getForeignKeys('projects'))
        ->firstWhere('columns', ['pose_id']);

    expect($fk)->not->toBeNull();
    expect($fk['foreign_table'])->toBe('poses');
    expect(strtolower($fk['on_delete']))->toBe('set null');
});

test('pose_id FK set null when pose is deleted', function (): void {
    $user = User::factory()->create();
    $pose = Pose::factory()->create();
    $project = Project::factory()->for($user)->create(['pose_id' => $pose->id]);

    expect($project->fresh()->pose_id)->toBe($pose->id);

    $pose->delete();

    expect($project->fresh()->pose_id)->toBeNull();
});

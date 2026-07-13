<?php

use App\Models\Project;
use App\Models\User;

test('owner can view their project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    expect($user->can('view', $project))->toBeTrue();
});

test('non-owner cannot view another users project', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    expect($other->can('view', $project))->toBeFalse();
});

test('admin can view any project', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->for($owner)->create();

    expect($admin->can('view', $project))->toBeTrue();
});

test('owner can update their project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    expect($user->can('update', $project))->toBeTrue();
});

test('non-owner cannot update another users project', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    expect($other->can('update', $project))->toBeFalse();
});

test('admin can update any project', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->for($owner)->create();

    expect($admin->can('update', $project))->toBeTrue();
});

test('owner can delete their project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    expect($user->can('delete', $project))->toBeTrue();
});

test('non-owner cannot delete another users project', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($owner)->create();

    expect($other->can('delete', $project))->toBeFalse();
});

test('admin can delete any project', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $project = Project::factory()->for($owner)->create();

    expect($admin->can('delete', $project))->toBeTrue();
});

test('any authenticated user can create a project', function (): void {
    $user = User::factory()->create();

    expect($user->can('create', Project::class))->toBeTrue();
});

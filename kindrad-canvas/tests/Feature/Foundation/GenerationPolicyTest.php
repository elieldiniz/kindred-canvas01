<?php

use App\Models\Generation;
use App\Models\User;
use Database\Seeders\CatalogSeeder;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('owner can view their generation', function (): void {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->create();

    expect($user->can('view', $generation))->toBeTrue();
});

test('non-owner cannot view another users generation', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $generation = Generation::factory()->for($owner)->create();

    expect($other->can('view', $generation))->toBeFalse();
});

test('admin can view any generation', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $generation = Generation::factory()->for($owner)->create();

    expect($admin->can('view', $generation))->toBeTrue();
});

test('owner can download their generation', function (): void {
    $user = User::factory()->create();
    $generation = Generation::factory()->for($user)->create();

    expect($user->can('download', $generation))->toBeTrue();
});

test('non-owner cannot download another users generation', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $generation = Generation::factory()->for($owner)->create();

    expect($other->can('download', $generation))->toBeFalse();
});

test('admin can download any generation', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $generation = Generation::factory()->for($owner)->create();

    expect($admin->can('download', $generation))->toBeTrue();
});

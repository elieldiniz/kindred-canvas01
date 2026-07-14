<?php

use App\Models\Pose;
use App\Models\PoseStatus;
use Database\Seeders\CatalogSeeder;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('pose has expected fillable attributes', function (): void {
    $pose = Pose::factory()->create();

    expect($pose)->toBeInstanceOf(Pose::class);
});

test('pose belongs to a status', function (): void {
    $pose = Pose::query()->firstOrFail();

    expect($pose->status)->toBeInstanceOf(PoseStatus::class);
});

test('active scope returns only active poses', function (): void {
    PoseStatus::where('slug', 'inactive')->update(['name' => 'Inactive']);
    $inactive = Pose::query()->first();
    $inactive->update(['status_id' => PoseStatus::where('slug', 'inactive')->value('id')]);

    expect(Pose::active()->count())->toBe(7);
    expect(Pose::count())->toBe(8);
});

test('seeder creates expected pose slugs', function (): void {
    $slugs = Pose::pluck('slug')->all();

    expect($slugs)->toContain(
        'abracados', 'beijo', 'sentados', 'caminhando',
        'natal', 'praia', 'sofa', 'flores',
    );
});

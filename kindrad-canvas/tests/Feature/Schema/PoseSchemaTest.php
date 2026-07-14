<?php

use Illuminate\Support\Facades\Schema;

test('pose_statuses table has expected columns', function (): void {
    $columns = Schema::getColumns('pose_statuses');

    expect(collect($columns)->pluck('name')->all())
        ->toContain('id', 'name', 'slug', 'created_at', 'updated_at');
});

test('pose_statuses seeded with active and inactive by migration', function (): void {
    $slugs = DB::table('pose_statuses')->pluck('slug')->all();

    expect($slugs)->toContain('active', 'inactive');
});

test('poses table has expected columns', function (): void {
    $columns = Schema::getColumns('poses');

    expect(collect($columns)->pluck('name')->all())
        ->toContain('id', 'slug', 'name', 'thumbnail_path', 'status_id', 'sort_order', 'created_at', 'updated_at');
});

test('poses status_id has FK to pose_statuses with restrict on delete', function (): void {
    $indexes = collect(Schema::getForeignKeys('poses'))
        ->firstWhere('columns', ['status_id']);

    expect($indexes)->not->toBeNull();
    expect($indexes['foreign_table'])->toBe('pose_statuses');
    expect(strtolower($indexes['on_delete']))->toBe('restrict');
});

test('poses slug is unique', function (): void {
    $indexes = collect(Schema::getIndexes('poses'))
        ->firstWhere('columns', ['slug']);

    expect($indexes)->not->toBeNull();
    expect($indexes['unique'])->toBeTrue();
});

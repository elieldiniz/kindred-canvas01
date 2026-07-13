<?php

use App\Models\Project;
use App\Models\User;
use Database\Seeders\CatalogSeeder;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('soft delete hides from dashboard', function (): void {
    $owner = User::factory()->create();
    $active = Project::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Visible canvas',
    ]);
    $deleted = Project::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Deleted canvas',
    ]);
    $deleted->delete();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Recent Projects')
        ->assertSee($active->title)
        ->assertDontSee($deleted->title);
});

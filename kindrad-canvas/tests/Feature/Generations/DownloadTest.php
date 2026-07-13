<?php

use App\Models\Generation;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
    Storage::fake('s3');
});

function downloadableGeneration(User $owner, array $attributes = []): Generation
{
    $project = Project::factory()->create(['user_id' => $owner->id]);

    return Generation::factory()->completed()->create(array_merge([
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'result_path' => 'generations/download.png',
        'result_mime_type' => 'image/png',
    ], $attributes));
}

test('owner can download', function (): void {
    $owner = User::factory()->create();
    $generation = downloadableGeneration($owner);
    Storage::disk('s3')->put($generation->result_path, 'fake-image-bytes');

    $response = $this->actingAs($owner)->get(route('generations.download', $generation));

    $response->assertSuccessful()
        ->assertHeader('content-type', 'image/png');

    expect($response->streamedContent())->toBe('fake-image-bytes');
});

test('non owner gets 403', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $generation = downloadableGeneration($owner);
    Storage::disk('s3')->put($generation->result_path, 'fake-image-bytes');

    $this->actingAs($other)
        ->get(route('generations.download', $generation))
        ->assertForbidden();
});

test('admin can download any generation', function (): void {
    $owner = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $generation = downloadableGeneration($owner);
    Storage::disk('s3')->put($generation->result_path, 'fake-image-bytes');

    $this->actingAs($admin)
        ->get(route('generations.download', $generation))
        ->assertSuccessful();
});

test('missing file renders graceful view', function (): void {
    $owner = User::factory()->create();
    $generation = downloadableGeneration($owner);

    $this->actingAs($owner)
        ->get(route('generations.download', $generation))
        ->assertNotFound()
        ->assertSee('File unavailable');
});

test('failed or processing generation returns 404', function (string $status): void {
    $owner = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $owner->id]);
    $factory = Generation::factory();
    $factory = $status === 'failed' ? $factory->failed('No result') : $factory->processing();
    $generation = $factory->create([
        'project_id' => $project->id,
        'user_id' => $owner->id,
        'result_path' => "generations/{$status}.png",
    ]);
    Storage::disk('s3')->put($generation->result_path, 'fake-image-bytes');

    $this->actingAs($owner)
        ->get(route('generations.download', $generation))
        ->assertNotFound();
})->with(['failed', 'processing']);

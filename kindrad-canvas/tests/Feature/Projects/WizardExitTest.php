<?php

use App\Livewire\Projects\Wizard;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('exit modal preserves draft row', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $component = Livewire::test(Wizard::class);

    $projectId = Project::where('user_id', $user->id)->value('id');
    expect($projectId)->not->toBeNull();

    $component->call('exit')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect(Project::find($projectId))->not->toBeNull();
    expect(Project::withTrashed()->find($projectId)?->deleted_at)->toBeNull();
});

test('exit cancel keeps user on wizard', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $component = Livewire::test(Wizard::class);

    $component->assertSee(__('Exit wizard?'));
    $component->assertSee(__('Cancel'));
    $component->assertSee(__('Your draft will be saved.'));
});

test('exit redirects to dashboard route', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class)
        ->call('exit')
        ->assertRedirect(route('dashboard'));
});

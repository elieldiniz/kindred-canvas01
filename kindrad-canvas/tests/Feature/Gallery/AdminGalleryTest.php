<?php

use App\Livewire\Admin\Gallery\Index as AdminGallery;
use App\Models\AuditLog;
use App\Models\Generation;
use App\Models\GenerationStatus;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\SubscriptionInterval;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    SubscriptionInterval::firstOrCreate(['slug' => 'month'], ['name' => 'Mensal']);
    ProjectStatus::firstOrCreate(['slug' => 'draft'], ['name' => 'Rascunho']);
    GenerationStatus::firstOrCreate(['slug' => 'waiting'], ['name' => 'Aguardando']);
    GenerationStatus::firstOrCreate(['slug' => 'completed'], ['name' => 'Concluído']);
});

it('redirects guests and rejects non-admins on the admin gallery', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    get('/admin/gallery')->assertRedirect(route('login'));

    actingAs(User::factory()->create())
        ->get('/admin/gallery')
        ->assertForbidden();

    actingAs($admin)
        ->get('/admin/gallery')
        ->assertOk()
        ->assertSee('data-test="admin-gallery-index"', false);
});

it('lists only projects that have a completed latest generation', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $owner = User::factory()->create();
    $withArt = Project::factory()->forUser($owner)->create(['title' => 'Has Artwork']);
    $incomplete = Project::factory()->forUser($owner)->create(['title' => 'No Artwork Yet']);

    Generation::factory()->completed()->forProject($withArt)->create();
    Generation::factory()->waiting()->forProject($incomplete)->create();

    actingAs($admin)
        ->get('/admin/gallery')
        ->assertOk()
        ->assertSee('Has Artwork', false)
        ->assertDontSee('No Artwork Yet', false);
});

it('toggles is_published and writes audit log entries', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $owner = User::factory()->create();
    $project = Project::factory()->forUser($owner)->create([
        'is_published' => true,
        'first_generated_at' => now()->subHour(),
    ]);
    Generation::factory()->completed()->forProject($project)->create();

    actingAs($admin);

    Livewire::test(AdminGallery::class)
        ->call('togglePublished', $project->id)
        ->assertHasNoErrors();

    $project->refresh();
    expect($project->is_published)->toBeFalse();

    Livewire::test(AdminGallery::class)
        ->call('togglePublished', $project->id)
        ->assertHasNoErrors();

    $project->refresh();
    expect($project->is_published)->toBeTrue();

    $log = AuditLog::query()
        ->where('actor_user_id', $admin->id)
        ->where('target_id', $project->id)
        ->whereJsonContains('payload->after', false)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->action?->slug)->toBe('unpublish_project');
});

it('hides unpublished projects from the public explore feed but keeps them visible to admin', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->forUser($owner)->create([
        'is_published' => false,
        'is_in_explore' => true,
        'first_generated_at' => now()->subHour(),
    ]);
    Generation::factory()->completed()->forProject($project)->create();

    actingAs(User::factory()->create())
        ->get('/explore')
        ->assertOk()
        ->assertDontSee($project->title ?: 'Untitled', false);

    actingAs(User::factory()->create(['is_admin' => true]))
        ->get('/admin/gallery')
        ->assertOk()
        ->assertSee('data-test="admin-gallery-hidden-overlay"', false);
});

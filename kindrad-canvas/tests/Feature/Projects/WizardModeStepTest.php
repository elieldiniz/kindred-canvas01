<?php

use App\Livewire\Projects\Wizard;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('step 1 renders only free and mug modes', function (): void {
    $this->seed(CatalogSeeder::class);

    ProjectMode::factory()->create(['slug' => 'tshirt', 'name' => 'Tshirt']);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSee('Free')
        ->assertSee('Mug')
        ->assertDontSee('Tshirt');
});

test('selecting mug persists mode id and advances to step 2', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    Livewire::test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSet('modeId', null)
        ->call('selectMode', $mug->id)
        ->assertSet('step', 2)
        ->assertSet('modeId', $mug->id)
        ->assertHasNoErrors();

    $project = Project::where('user_id', $user->id)->firstOrFail();

    expect($project->mode_id)->toBe($mug->id);
    expect(ProjectMode::find($project->mode_id)?->slug)->toBe('mug');
});

test('selecting free persists mode id and advances to step 2', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $free = ProjectMode::where('slug', 'free')->firstOrFail();

    Livewire::test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSet('modeId', null)
        ->call('selectMode', $free->id)
        ->assertSet('step', 2)
        ->assertSet('modeId', $free->id)
        ->assertHasNoErrors();

    $project = Project::where('user_id', $user->id)->firstOrFail();

    expect($project->mode_id)->toBe($free->id);
    expect(ProjectMode::find($project->mode_id)?->slug)->toBe('free');
});

test('selectMode is blocked when no mode is set and the rules require one', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSet('modeId', null)
        ->call('selectMode', 999_999)
        ->assertSet('step', 1)
        ->assertSet('modeId', null)
        ->assertHasErrors(['modeId']);
});

test('non owner cannot set mode via selectMode action', function (): void {
    $this->seed(CatalogSeeder::class);

    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    actingAs($owner);

    $ownerWizard = Livewire::test(Wizard::class);
    $projectA = Project::where('user_id', $owner->id)->firstOrFail();
    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    actingAs($intruder);

    Livewire::test(Wizard::class, ['projectId' => $projectA->id])
        ->call('selectMode', $mug->id);

    expect(Project::find($projectA->id)?->mode_id)->toBeNull();
});

test('selection persists on reload and auto advances step', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $wizard = Livewire::test(Wizard::class);
    $project = Project::where('user_id', $user->id)->firstOrFail();
    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    $project->update(['mode_id' => $mug->id]);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertSet('projectId', $project->id)
        ->assertSet('modeId', $mug->id)
        ->assertSet('step', 2);
});

test('mode is read only after first generation and selectMode is a silent no op', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $free = ProjectMode::where('slug', 'free')->firstOrFail();

    $wizard = Livewire::test(Wizard::class);
    $project = Project::where('user_id', $user->id)->firstOrFail();

    $project->update([
        'mode_id' => $mug->id,
        'first_generated_at' => now(),
    ]);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertSet('modeId', $mug->id)
        ->assertSet('step', 2)
        ->call('selectMode', $free->id)
        ->assertSet('modeId', $mug->id)
        ->assertSet('step', 2)
        ->assertHasNoErrors();

    $project->refresh();

    expect($project->mode_id)->toBe($mug->id);
    expect(ProjectMode::find($project->mode_id)?->slug)->toBe('mug');
});

test('mode step view renders selection glow class on the active tile', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $wizard = Livewire::test(Wizard::class);
    $project = Project::where('user_id', $user->id)->firstOrFail();
    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();

    $project->update(['mode_id' => $mug->id]);

    Livewire::test(Wizard::class, ['id' => $project->id])
        ->assertSet('modeId', $mug->id)
        ->call('goToStep', 1)
        ->assertSet('step', 1)
        ->assertSeeHtml('active-selection')
        ->assertSeeHtml('selection-glow')
        ->assertSeeHtml('wizard-mode-tile-selected');
});

test('mode step view excludes modes whose slug is not free or mug', function (): void {
    $this->seed(CatalogSeeder::class);

    ProjectMode::factory()->create(['slug' => 'tshirt', 'name' => 'Tshirt']);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class)
        ->assertSee('Free')
        ->assertSee('Mug')
        ->assertDontSee('Tshirt');
});

<?php

use App\Livewire\Projects\Wizard;
use App\Models\Category;
use App\Models\ProjectMode;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('wizard render shows topbar without sidebar', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class)
        ->assertSee('Kindred Canvas')
        ->assertSee('wizard-exit-button', false)
        ->assertDontSee('<aside', false);
});

test('wizard render shows progress bar', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class)
        ->assertSee('STEP 01 OF 07')
        ->assertSee('Mode');
});

test('wizard render shows the exit modal label', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Wizard::class)
        ->assertSee('Exit wizard?')
        ->assertSee('Your draft will be saved.')
        ->assertSee('Cancel')
        ->assertSee('Exit');
});

test('wizard back button is disabled on first step and clickable after step 2', function (): void {
    $this->seed(CatalogSeeder::class);

    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $category = Category::where('slug', 'birthday')->firstOrFail();

    Livewire::test(Wizard::class)
        ->assertSet('step', 1)
        ->assertSee('data-test="wizard-back-button"', false)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $category->id)
        ->assertSet('step', 3)
        ->assertSee('wire:click="back"', false);
});

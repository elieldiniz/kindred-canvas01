<?php

use App\Livewire\Projects\Wizard;
use App\Models\Category;
use App\Models\Layout;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('picker query count is under threshold per step', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();
    $centered = Layout::where('slug', 'centered')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->call('selectLayout', $centered->id);

    $project = Project::where('user_id', $user->id)->firstOrFail();

    foreach ([2, 3, 4] as $step) {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $wizard = Livewire::test(Wizard::class, ['id' => $project->id])
            ->call('goToStep', $step)
            ->assertSet('step', $step);

        $queries = count(DB::getQueryLog());
        expect($queries)->toBeLessThanOrEqual(5, "step {$step} used {$queries} queries");
    }
});

test('picker render time best effort under 300ms per step', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    $mug = ProjectMode::where('slug', 'mug')->firstOrFail();
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();
    $centered = Layout::where('slug', 'centered')->firstOrFail();

    Livewire::test(Wizard::class)
        ->call('selectMode', $mug->id)
        ->call('selectCategory', $birthday->id)
        ->call('selectStyle', $watercolor->id)
        ->call('selectLayout', $centered->id);

    $project = Project::where('user_id', $user->id)->firstOrFail();

    if (env('CI')) {
        $this->markTestSkipped('render time assertion skipped in CI');
    }

    foreach ([2, 3, 4] as $step) {
        $start = microtime(true);

        Livewire::test(Wizard::class, ['id' => $project->id])
            ->call('goToStep', $step);

        $durationMs = (microtime(true) - $start) * 1000;
        expect($durationMs)->toBeLessThan(300.0, "step {$step} took {$durationMs}ms");
    }
})->skip(fn () => env('CI'), 'render time is too flaky in CI');

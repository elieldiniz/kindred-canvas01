<?php

use App\Livewire\Projects\Wizard;
use App\Livewire\Projects\Wizard\Steps\Category as CategoryStep;
use App\Livewire\Projects\Wizard\Steps\Layout as LayoutStep;
use App\Livewire\Projects\Wizard\Steps\Style as StyleStep;
use App\Models\Category;
use App\Models\Layout;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('picker query count is under threshold per step', function (): void {
    $birthday = Category::where('slug', 'birthday')->firstOrFail();
    $watercolor = Style::where('slug', 'watercolor')->firstOrFail();

    $pickers = [
        2 => [CategoryStep::class, []],
        3 => [StyleStep::class, ['categoryId' => $birthday->id]],
        4 => [LayoutStep::class, ['styleId' => $watercolor->id]],
    ];

    foreach ($pickers as $step => [$component, $parameters]) {
        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test($component, $parameters)->assertSuccessful();

        $queries = count(DB::getQueryLog());
        expect($queries)->toBeLessThanOrEqual(4, "step {$step} used {$queries} queries");
    }
});

test('picker indexes support catalog filters', function (): void {
    expect(indexColumns('categories'))->toContain(['product_id', 'status_id', 'sort_order']);
    expect(indexColumns('category_styles'))->toContain(['category_id', 'style_id']);
    expect(indexColumns('style_layouts'))->toContain(['style_id', 'layout_id']);
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

/**
 * @return array<int, array<int, string>>
 */
function indexColumns(string $table): array
{
    return collect(Schema::getIndexes($table))
        ->map(fn (array $index): array => $index['columns'])
        ->values()
        ->all();
}

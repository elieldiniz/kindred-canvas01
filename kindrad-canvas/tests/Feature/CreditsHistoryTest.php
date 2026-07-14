<?php

use App\Livewire\Credits\Index as CreditsIndex;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

test('guests are redirected to login from the credits history page', function (): void {
    get(route('credits.index'))->assertRedirect(route('login'));
});

test('authenticated users can visit the credits history page', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('credits.index'))
        ->assertSuccessful()
        ->assertSee('data-test="credits-history-page"', false);
});

test('credits history lists user transactions newest first', function (): void {
    $user = User::factory()->create(['credit_balance' => 5]);
    $other = User::factory()->create(['credit_balance' => 5]);

    $oldest = CreditTransaction::factory()->create([
        'user_id' => $user->id,
        'delta' => 5,
        'balance_after' => 5,
        'created_at' => now()->subDays(2),
    ]);
    $newest = CreditTransaction::factory()->create([
        'user_id' => $user->id,
        'delta' => -1,
        'balance_after' => 4,
        'created_at' => now()->subDay(),
    ]);
    $middle = CreditTransaction::factory()->create([
        'user_id' => $user->id,
        'delta' => -1,
        'balance_after' => 3,
        'created_at' => now(),
    ]);

    CreditTransaction::factory()->create([
        'user_id' => $other->id,
        'delta' => 5,
        'balance_after' => 5,
    ]);

    Livewire::actingAs($user)
        ->test(CreditsIndex::class)
        ->assertSeeInOrder([
            $middle->created_at->format('M j, Y · H:i'),
            $newest->created_at->format('M j, Y · H:i'),
            $oldest->created_at->format('M j, Y · H:i'),
        ])
        ->assertDontSee($other->name);
});

test('admin grant notes are visible to the user on the history page', function (): void {
    $user = User::factory()->create(['credit_balance' => 0]);

    CreditTransaction::factory()
        ->adminGrant(10, 'Compensation for outage 2026-06-15')
        ->create([
            'user_id' => $user->id,
            'balance_after' => 10,
        ]);

    actingAs($user)
        ->get(route('credits.index'))
        ->assertSuccessful()
        ->assertSee('Compensation for outage 2026-06-15')
        ->assertSee('Admin Grant');
});

test('reference column links to the related project', function (): void {
    $user = User::factory()->create(['credit_balance' => 5]);
    $project = Project::factory()->for($user)->create(['title' => 'My Mug']);
    $generation = Generation::factory()->for($user)->for($project)->create();

    CreditTransaction::factory()
        ->debit()
        ->create([
            'user_id' => $user->id,
            'delta' => -1,
            'balance_after' => 4,
            'reference_type' => Generation::class,
            'reference_id' => $generation->id,
        ]);

    actingAs($user)
        ->get(route('credits.index'))
        ->assertSuccessful()
        ->assertSee('Generation #'.$generation->id)
        ->assertSee('href="'.route('projects.show', $project).'"', false);
});

test('credits history page shows empty state when no transactions', function (): void {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('credits.index'))
        ->assertSuccessful()
        ->assertSee('data-test="credits-empty-state"', false)
        ->assertSee('No credit activity yet');
});

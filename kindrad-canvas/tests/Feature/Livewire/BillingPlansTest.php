<?php

use App\Livewire\Billing\Plans;
use App\Models\SubscriptionInterval;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    SubscriptionInterval::firstOrCreate(['slug' => 'month'], ['name' => 'Mensal']);
    SubscriptionInterval::firstOrCreate(['slug' => 'year'], ['name' => 'Anual']);
});

it('renders the billing plans page with monthly and yearly grids and the toggle', function () {
    SubscriptionPlan::factory()->monthly()->create(['slug' => 'monthly-basic', 'name' => 'Monthly Basic']);
    SubscriptionPlan::factory()->yearly()->create(['slug' => 'yearly-basic', 'name' => 'Yearly Basic']);

    $user = User::factory()->create();
    actingAs($user);

    Livewire::test(Plans::class)
        ->assertSee('Monthly Basic')
        ->assertSee('Yearly Basic')
        ->assertSee('data-test="billing-plans-toggle"', false)
        ->assertSee('data-test="billing-plans-toggle-month"', false)
        ->assertSee('data-test="billing-plans-toggle-year"', false)
        ->assertSee('data-test="billing-plans-grid-month"', false)
        ->assertSee('data-test="billing-plans-grid-year"', false);
});

it('caps each interval at three plans on the billing plans page', function () {
    for ($i = 1; $i <= 4; $i++) {
        SubscriptionPlan::factory()->monthly()->create([
            'slug' => "m{$i}",
            'name' => "Monthly {$i}",
            'sort_order' => $i * 10,
        ]);
    }
    for ($i = 1; $i <= 4; $i++) {
        SubscriptionPlan::factory()->yearly()->create([
            'slug' => "y{$i}",
            'name' => "Yearly {$i}",
            'sort_order' => $i * 10,
        ]);
    }

    $user = User::factory()->create();
    actingAs($user);

    $component = Livewire::test(Plans::class);

    foreach (['Monthly 1', 'Monthly 2', 'Monthly 3'] as $name) {
        $component->assertSee($name);
    }
    $component->assertDontSee('Monthly 4');

    foreach (['Yearly 1', 'Yearly 2', 'Yearly 3'] as $name) {
        $component->assertSee($name);
    }
    $component->assertDontSee('Yearly 4');
});

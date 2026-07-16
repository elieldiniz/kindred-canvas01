<?php

use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Factories\SubscriptionIntervalFactory;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->month = (new SubscriptionIntervalFactory)->resolveInterval('month');
    $this->year = (new SubscriptionIntervalFactory)->resolveInterval('year');
});

it('renders the plans section between how-it-works and footer on the welcome page', function () {
    SubscriptionPlan::factory()->monthly()->create(['sort_order' => 10, 'slug' => 'basic']);

    $response = get('/')->assertSuccessful();

    $html = $response->getContent();
    $howItWorksPos = strpos($html, 'id="how-it-works"');
    $plansPos = strpos($html, 'id="welcome-plans"');
    $footerPos = strpos($html, '<footer');

    expect($howItWorksPos)->not->toBeFalse()
        ->and($plansPos)->not->toBeFalse()
        ->and($footerPos)->not->toBeFalse()
        ->and($howItWorksPos)->toBeLessThan($plansPos)
        ->and($plansPos)->toBeLessThan($footerPos);
});

it('uses the SFC welcome.plans livewire component on the welcome page', function () {
    SubscriptionPlan::factory()->monthly()->create();

    get('/')
        ->assertSuccessful()
        ->assertSeeLivewire('welcome.plans');
});

it('renders only active plans in sort order and caps at three per interval', function () {
    SubscriptionPlan::factory()->monthly()->inactive()->create(['slug' => 'inactive', 'sort_order' => 5, 'name' => 'Inactive Plan']);
    SubscriptionPlan::factory()->monthly()->create(['slug' => 'm1', 'sort_order' => 10, 'name' => 'Monthly One']);
    SubscriptionPlan::factory()->monthly()->create(['slug' => 'm2', 'sort_order' => 20, 'name' => 'Monthly Two']);
    SubscriptionPlan::factory()->monthly()->create(['slug' => 'm3', 'sort_order' => 30, 'name' => 'Monthly Three']);
    SubscriptionPlan::factory()->monthly()->create(['slug' => 'm4', 'sort_order' => 40, 'name' => 'Monthly Four Overflow']);
    SubscriptionPlan::factory()->yearly()->create(['slug' => 'y1', 'sort_order' => 10, 'name' => 'Yearly One']);
    SubscriptionPlan::factory()->yearly()->create(['slug' => 'y2', 'sort_order' => 20, 'name' => 'Yearly Two']);
    SubscriptionPlan::factory()->yearly()->create(['slug' => 'y3', 'sort_order' => 30, 'name' => 'Yearly Three']);
    SubscriptionPlan::factory()->yearly()->create(['slug' => 'y4', 'sort_order' => 40, 'name' => 'Yearly Four Overflow']);

    $html = get('/')->assertSuccessful()->getContent();

    expect($html)
        ->toContain('Monthly One')
        ->toContain('Monthly Two')
        ->toContain('Monthly Three')
        ->not->toContain('Monthly Four Overflow')
        ->not->toContain('Inactive Plan')
        ->toContain('Yearly One')
        ->toContain('Yearly Two')
        ->toContain('Yearly Three')
        ->not->toContain('Yearly Four Overflow');
});

it('shows register cta for guests and billing cta for authenticated users', function () {
    $plan = SubscriptionPlan::factory()->monthly()->create(['slug' => 'basic', 'name' => 'Basic']);

    $htmlGuest = get('/')->assertSuccessful()->getContent();

    expect($htmlGuest)
        ->toContain('href="'.route('register').'"')
        ->not->toContain('href="'.route('billing.index').'"')
        ->toContain('Sign up and subscribe');

    $user = User::factory()->create();
    actingAs($user);

    $htmlAuth = get('/')->assertSuccessful()->getContent();

    expect($htmlAuth)
        ->toContain('href="'.route('billing.index').'"')
        ->not->toContain('href="'.route('register').'"')
        ->toContain('Subscribe to Basic');
});

it('uses existing welcome design tokens in the plans section', function () {
    SubscriptionPlan::factory()->monthly()->create();

    $html = get('/')->assertSuccessful()->getContent();

    $sectionMarker = 'id="welcome-plans"';
    $footerMarker = '<footer';
    $sectionStart = strpos($html, $sectionMarker);
    $footerStart = strpos($html, $footerMarker, $sectionStart);
    $section = substr($html, (int) $sectionStart, (int) ($footerStart - $sectionStart)) ?: '';

    expect($section)
        ->toContain('font-serif')
        ->toContain('text-on-surface')
        ->toContain('border-white/10')
        ->and($html)
        ->toContain('gradient-generate');
});

it('renders monthly and yearly grids with the interval toggle', function () {
    SubscriptionPlan::factory()->monthly()->create(['slug' => 'monthly-basic']);
    SubscriptionPlan::factory()->yearly()->create(['slug' => 'yearly-basic']);

    $html = get('/')->assertSuccessful()->getContent();

    expect($html)
        ->toContain('data-test="welcome-plans-toggle"')
        ->toContain('data-test="welcome-plans-toggle-month"')
        ->toContain('data-test="welcome-plans-toggle-year"')
        ->toContain('data-test="welcome-plans-grid-month"')
        ->toContain('data-test="welcome-plans-grid-year"')
        ->toContain('x-data="{ interval: \'month\' }"');
});

it('caps each interval at three plans independently', function () {
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
    SubscriptionPlan::factory()->monthly()->inactive()->create(['slug' => 'inactive', 'sort_order' => 5, 'name' => 'Inactive One']);

    $html = get('/')->assertSuccessful()->getContent();

    foreach (['Monthly 1', 'Monthly 2', 'Monthly 3'] as $name) {
        expect($html)->toContain($name);
    }
    expect($html)->not->toContain('Monthly 4');

    foreach (['Yearly 1', 'Yearly 2', 'Yearly 3'] as $name) {
        expect($html)->toContain($name);
    }
    expect($html)->not->toContain('Yearly 4');

    expect($html)->not->toContain('Inactive One');
});

it('renders an empty state per interval when no plans are configured', function () {
    $html = get('/')->assertSuccessful()->getContent();

    expect($html)
        ->toContain('data-test="welcome-plans-empty-month"')
        ->toContain('data-test="welcome-plans-empty-year"')
        ->toContain('inventory_2');
});

it('does not introduce an n+1 query when rendering plans', function () {
    SubscriptionPlan::factory()->monthly()->create(['slug' => 'one']);
    SubscriptionPlan::factory()->monthly()->create(['slug' => 'two']);
    SubscriptionPlan::factory()->monthly()->create(['slug' => 'three']);
    SubscriptionPlan::factory()->yearly()->create(['slug' => 'y-one']);

    DB::flushQueryLog();
    DB::enableQueryLog();
    get('/')->assertSuccessful();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $intervalQueries = collect($queries)->filter(fn ($q) => str_contains($q['query'], 'subscription_intervals'));

    expect($intervalQueries->count())->toBeLessThanOrEqual(2);
});

it('creates a factory plan and renders it on the welcome page', function () {
    SubscriptionPlan::factory()->monthly()->create([
        'slug' => 'factory-plan',
        'name' => 'Factory Plan',
    ]);

    get('/')
        ->assertSuccessful()
        ->assertSee('Factory Plan')
        ->assertSee('data-test="welcome-plan-card-factory-plan"', false);
});

<?php

use App\Actions\Generation\SubmitGeneration;
use App\Billing\StripeWebhookDispatcher;
use App\Exceptions\BillingAccessDeniedException;
use App\Livewire\Admin\Subscriptions\Index;
use App\Livewire\Billing\Index as BillingIndex;
use App\Mail\PaymentFailedMail;
use App\Models\PaymentFailure;
use App\Models\Project;
use App\Models\Subscription;
use App\Models\SubscriptionInterval;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionStatus;
use App\Models\User;
use App\Notifications\PaymentFailedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    SubscriptionInterval::firstOrCreate(['slug' => 'month'], ['name' => 'Mensal']);
    SubscriptionInterval::firstOrCreate(['slug' => 'year'], ['name' => 'Anual']);

    SubscriptionStatus::firstOrCreate(['slug' => 'past_due'], ['name' => 'Atrasado']);
    SubscriptionStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Ativo']);
    SubscriptionStatus::firstOrCreate(['slug' => 'canceled'], ['name' => 'Cancelado']);
});

it('marks subscription past_due and creates database notification for failed invoice', function () {
    Mail::fake();
    Notification::fake();

    $plan = SubscriptionPlan::factory()->monthly()->create();
    $user = User::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $dispatcher = app(StripeWebhookDispatcher::class);
    $dispatcher->dispatch([
        'id' => 'evt_test_123',
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'id' => 'in_test_123',
                'subscription' => $subscription->stripe_id,
                'created' => time(),
                'last_payment_error' => ['code' => 'card_declined', 'message' => 'Your card was declined.'],
            ],
        ],
    ]);

    $subscription->refresh();
    expect($subscription->stripe_status)->toBe('past_due')
        ->and($subscription->status?->slug)->toBe('past_due');

    expect(PaymentFailure::query()->where('subscription_id', $subscription->id)->count())->toBe(1);

    Notification::assertSentTo($user, PaymentFailedNotification::class);
});

it('treats payment_action_required as dunning and queues mail when mailer is not log', function () {
    config(['mail.default' => 'smtp']);
    Mail::fake();
    Notification::fake();

    $plan = SubscriptionPlan::factory()->monthly()->create();
    $user = User::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $dispatcher = app(StripeWebhookDispatcher::class);
    $dispatcher->dispatch([
        'id' => 'evt_test_456',
        'type' => 'invoice.payment_action_required',
        'data' => [
            'object' => [
                'id' => 'in_test_456',
                'subscription' => $subscription->stripe_id,
                'created' => time(),
                'last_payment_error' => ['code' => 'authentication_required'],
            ],
        ],
    ]);

    $subscription->refresh();
    expect($subscription->stripe_status)->toBe('past_due');
    expect(PaymentFailure::query()->where('event_type', 'invoice.payment_action_required')->count())->toBeGreaterThanOrEqual(1);

    Mail::assertQueued(PaymentFailedMail::class, 1);
    Notification::assertSentTo($user, PaymentFailedNotification::class);
});

it('does not queue mail when mail driver is log (development)', function () {
    config(['mail.default' => 'log']);
    Mail::fake();
    Notification::fake();

    $plan = SubscriptionPlan::factory()->monthly()->create();
    $user = User::factory()->create();
    $subscription = Subscription::factory()
        ->forUser($user)
        ->forPlan($plan)
        ->active()
        ->create();

    $dispatcher = app(StripeWebhookDispatcher::class);
    $dispatcher->dispatch([
        'id' => 'evt_test_789',
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'id' => 'in_test_789',
                'subscription' => $subscription->stripe_id,
                'created' => time(),
                'last_payment_error' => ['code' => 'card_declined'],
            ],
        ],
    ]);

    Mail::assertNothingQueued();
    Notification::assertSentTo($user, PaymentFailedNotification::class);
});

it('renders persistent dunning banner with billing CTA on dashboard', function () {
    $user = User::factory()->create();
    Subscription::factory()->forUser($user)->pastDue()->create([
        'stripe_id' => 'sub_test_dash',
    ]);

    actingAs($user);

    get('/dashboard')
        ->assertSuccessful()
        ->assertSee('data-test="dashboard-dunning-banner"', false)
        ->assertSee('data-test="dashboard-dunning-banner-cta"', false)
        ->assertSee(__('Atualizar método de pagamento'), false);
});

it('renders the dunning banner on the billing page using ends_at', function () {
    $user = User::factory()->create();
    Subscription::factory()->forUser($user)->pastDue()->create([
        'stripe_id' => 'sub_test_billing',
        'ends_at' => now()->addDays(5),
    ]);

    actingAs($user);

    Livewire::test(BillingIndex::class)
        ->assertSee('data-test="billing-dunning-banner"', false)
        ->assertSee('data-test="billing-dunning-banner-cta"', false);
});

it('blocks generation after grace expires and allows generation within grace', function () {
    $user = User::factory()->create();
    SubscriptionInterval::firstOrCreate(['slug' => 'month'], ['name' => 'Mensal']);

    $subscriptionWithinGrace = Subscription::factory()->forUser($user)->pastDue()->create([
        'stripe_id' => 'sub_within_grace',
        'current_period_end' => now()->subDay(),
        'ends_at' => null,
    ]);

    $userWithinGrace = User::factory()->create();
    $subWithin = Subscription::factory()->forUser($userWithinGrace)->pastDue()->create([
        'stripe_id' => 'sub_within_grace_user',
        'current_period_end' => now()->subDay(),
        'ends_at' => null,
    ]);
    expect($subWithin->isPastDueAndExpired(7))->toBeFalse();

    $userExpired = User::factory()->create();
    Subscription::factory()->forUser($userExpired)->pastDue()->create([
        'stripe_id' => 'sub_expired',
        'current_period_end' => now()->subDays(30),
        'ends_at' => null,
    ]);
    $expiredSub = Subscription::where('stripe_id', 'sub_expired')->first();
    expect($expiredSub->isPastDueAndExpired(7))->toBeTrue();
});

it('records payment failure and lists it in admin subscriptions', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $subscription = Subscription::factory()->forUser($user)->create([
        'stripe_id' => 'sub_admin_test',
    ]);

    PaymentFailure::factory()->forSubscription($subscription)->create();

    actingAs($admin);

    Livewire::test(Index::class)
        ->assertSee('Falhas de pagamento recentes')
        ->assertSee('data-test="admin-payment-failures-section"', false);
});

it('rejects generation submission for user with past_due beyond grace', function () {
    SubscriptionStatus::firstOrCreate(['slug' => 'past_due'], ['name' => 'Atrasado']);

    $user = User::factory()->create();
    Subscription::factory()->forUser($user)->pastDue()->create([
        'stripe_id' => 'sub_block_test',
        'current_period_end' => now()->subDays(30),
        'ends_at' => null,
    ]);

    $user->update(['credit_balance' => 10]);

    $project = Project::factory()->create(['user_id' => $user->id]);

    expect(fn () => app(SubmitGeneration::class)->execute($user, $project))
        ->toThrow(BillingAccessDeniedException::class);
});

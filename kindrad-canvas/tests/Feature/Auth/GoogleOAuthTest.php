<?php

use App\Models\CreditTransaction;
use App\Models\OAuthAccount;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->seed(CatalogSeeder::class);
});

function fakeGoogleUser(array $overrides = []): SocialiteUser
{
    $user = new SocialiteUser;
    $user->id = $overrides['id'] ?? 'google-12345';
    $user->nickname = $overrides['nickname'] ?? 'ada';
    $user->name = $overrides['name'] ?? 'Ada Lovelace';
    $user->email = $overrides['email'] ?? 'ada@example.test';
    $user->avatar = $overrides['avatar'] ?? 'https://example.test/avatar.png';
    $user->token = 'access-token-abc';
    $user->refreshToken = 'refresh-token-xyz';
    $user->expiresIn = 3600;

    return $user;
}

test('GET auth/google redirects to Google OAuth', function (): void {
    Socialite::fake('google', fakeGoogleUser());

    $response = get(route('auth.oauth.redirect', ['provider' => 'google']));

    $response->assertRedirect();
});

test('callback with new Google user creates account and grants credits', function (): void {
    Socialite::fake('google', fakeGoogleUser());

    expect(User::query()->where('email', 'ada@example.test')->exists())->toBeFalse();

    $response = get(route('auth.oauth.callback', ['provider' => 'google']));

    $response->assertRedirect(route('dashboard'));

    $user = User::query()->where('email', 'ada@example.test')->firstOrFail();

    expect($user->email_verified_at)->not->toBeNull();
    expect($user->credit_balance)->toBe(5);

    $this->assertAuthenticatedAs($user);

    $account = OAuthAccount::query()->where('user_id', $user->id)->firstOrFail();

    expect($account->provider)->toBe('google');
    expect($account->provider_user_id)->toBe('google-12345');
    expect($account->access_token)->toBe('access-token-abc');
    expect($account->refresh_token)->toBe('refresh-token-xyz');
    expect($account->token_expires_at)->not->toBeNull();
});

test('callback with existing email links OAuth account and does not double grant credits', function (): void {
    $existing = User::factory()->create([
        'email' => 'ada@example.test',
        'credit_balance' => 5,
        'email_verified_at' => now(),
    ]);

    CreditTransaction::factory()->create([
        'user_id' => $existing->id,
        'delta' => 5,
        'balance_after' => 5,
    ]);

    Socialite::fake('google', fakeGoogleUser());

    $response = get(route('auth.oauth.callback', ['provider' => 'google']));

    $response->assertRedirect(route('dashboard'));

    expect(User::query()->where('email', 'ada@example.test')->count())->toBe(1);

    $existing->refresh();

    expect($existing->credit_balance)->toBe(5);
    expect(OAuthAccount::query()->where('user_id', $existing->id)->count())->toBe(1);
});

test('callback with provider that already linked logs in without creating duplicate', function (): void {
    Socialite::fake('google', fakeGoogleUser());

    get(route('auth.oauth.callback', ['provider' => 'google']));

    $user = User::query()->where('email', 'ada@example.test')->firstOrFail();

    auth()->logout();

    Socialite::fake('google', fakeGoogleUser());

    get(route('auth.oauth.callback', ['provider' => 'google']));

    expect(User::query()->where('email', 'ada@example.test')->count())->toBe(1);
    expect(OAuthAccount::query()->where('provider_user_id', 'google-12345')->count())->toBe(1);

    $user->refresh();
    expect($user->credit_balance)->toBe(5);
});

test('callback with invalid provider returns 404', function (): void {
    get(route('auth.oauth.redirect', ['provider' => 'facebook']))
        ->assertNotFound();

    get(route('auth.oauth.callback', ['provider' => 'facebook']))
        ->assertNotFound();
});

test('callback with broken Socialite response redirects to login with error', function (): void {
    Socialite::driver('google');

    $response = get(route('auth.oauth.callback', ['provider' => 'google']));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors();
});

test('login page shows Continue with Google button', function (): void {
    get(route('login'))
        ->assertSuccessful()
        ->assertSee('Continue with Google')
        ->assertSee('data-test="oauth-continue-google"', false)
        ->assertSee('href="'.route('auth.oauth.redirect', ['provider' => 'google']).'"', false);
});

test('register page shows Continue with Google button', function (): void {
    get(route('register'))
        ->assertSuccessful()
        ->assertSee('Continue with Google')
        ->assertSee('data-test="oauth-continue-google"', false)
        ->assertSee('href="'.route('auth.oauth.redirect', ['provider' => 'google']).'"', false);
});

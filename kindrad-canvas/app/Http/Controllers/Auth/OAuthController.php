<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\OAuthAccountManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class OAuthController extends Controller
{
    public function __construct(private readonly OAuthAccountManager $manager) {}

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['google'], true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, HttpRequest $request): RedirectResponse
    {
        abort_unless(in_array($provider, ['google'], true), 404);

        try {
            $socialite = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            Log::warning('OAuth callback failed', [
                'provider' => $provider,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => __('We could not complete the sign-in with :provider. Please try again.', ['provider' => ucfirst($provider)]),
            ]);
        }

        $user = $this->manager->resolveUser($provider, $socialite);

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}

<?php

namespace App\Services\Auth;

use App\Models\OAuthAccount;
use App\Models\User;
use App\Services\CreditLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class OAuthAccountManager
{
    public function __construct(private readonly CreditLedger $ledger) {}

    /**
     * Resolve a User for the given Socialite user. Either links to an existing
     * user (by matching oauth_accounts.provider_user_id or by email), or creates
     * a new user with a random password and grants them signup credits.
     */
    public function resolveUser(string $provider, SocialiteUser $socialite): User
    {
        $providerId = (string) $socialite->getId();
        $email = $socialite->getEmail();

        return DB::transaction(function () use ($provider, $providerId, $email, $socialite): User {
            $existingAccount = OAuthAccount::query()
                ->where('provider', $provider)
                ->where('provider_user_id', $providerId)
                ->first();

            if ($existingAccount !== null) {
                $this->syncAccount($existingAccount, $socialite);

                return $existingAccount->user;
            }

            $user = null;

            if ($email !== null && $email !== '') {
                $user = User::query()->where('email', $email)->first();
            }

            if ($user === null) {
                $user = $this->createUserFromSocialite($socialite);
                $this->ledger->signupGrant($user);
            }

            $this->createAccount($user, $provider, $providerId, $socialite);

            return $user;
        });
    }

    public function upsertAccount(User $user, string $provider, SocialiteUser $socialite): OAuthAccount
    {
        return DB::transaction(function () use ($user, $provider, $socialite): OAuthAccount {
            $account = OAuthAccount::query()
                ->where('user_id', $user->id)
                ->where('provider', $provider)
                ->where('provider_user_id', (string) $socialite->getId())
                ->first();

            if ($account === null) {
                $account = $this->createAccount($user, $provider, (string) $socialite->getId(), $socialite);
            } else {
                $this->syncAccount($account, $socialite);
            }

            return $account;
        });
    }

    private function createUserFromSocialite(SocialiteUser $socialite): User
    {
        $email = $socialite->getEmail();

        return User::create([
            'name' => $socialite->getName() ?: $socialite->getNickname() ?: 'User',
            'email' => $email ?? 'oauth-'.Str::random(8).'@example.test',
            'password' => Str::random(48),
            'email_verified_at' => now(),
            'credit_balance' => 0,
        ]);
    }

    private function createAccount(User $user, string $provider, string $providerId, SocialiteUser $socialite): OAuthAccount
    {
        return tap(OAuthAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $providerId,
            'nickname' => $socialite->getNickname(),
            'name' => $socialite->getName(),
            'email' => $socialite->getEmail(),
            'avatar' => $socialite->getAvatar(),
        ]), function (OAuthAccount $account) use ($socialite): void {
            $this->syncAccount($account, $socialite);
        });
    }

    private function syncAccount(OAuthAccount $account, SocialiteUser $socialite): void
    {
        $account->fill([
            'nickname' => $socialite->getNickname(),
            'name' => $socialite->getName(),
            'email' => $socialite->getEmail(),
            'avatar' => $socialite->getAvatar(),
        ]);

        $token = $socialite->token;

        if ($token !== '' && $token !== '0') {
            $account->access_token = $token;
        }

        if (property_exists($socialite, 'refreshToken') && $socialite->refreshToken !== null && $socialite->refreshToken !== '') {
            $account->refresh_token = $socialite->refreshToken;
        }

        if (property_exists($socialite, 'expiresIn') && $socialite->expiresIn !== null) {
            $account->token_expires_at = now()->addSeconds((int) $socialite->expiresIn);
        }

        $account->save();
    }
}

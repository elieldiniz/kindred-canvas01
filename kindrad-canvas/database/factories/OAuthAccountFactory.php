<?php

namespace Database\Factories;

use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OAuthAccount>
 */
class OAuthAccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'google',
            'provider_user_id' => (string) Str::uuid(),
            'nickname' => $this->faker->userName(),
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'avatar' => $this->faker->imageUrl(),
            'access_token' => Str::random(40),
            'refresh_token' => Str::random(40),
            'token_expires_at' => now()->addHour(),
        ];
    }

    public function google(): static
    {
        return $this->state(fn (): array => ['provider' => 'google']);
    }
}

<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function view(User $actor, Subscription $subscription): bool
    {
        return $actor->id === $subscription->user_id || $actor->is_admin === true;
    }

    public function manage(User $actor, Subscription $subscription): bool
    {
        return $this->view($actor, $subscription);
    }
}

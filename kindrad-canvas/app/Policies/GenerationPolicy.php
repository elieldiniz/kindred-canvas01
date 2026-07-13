<?php

namespace App\Policies;

use App\Models\Generation;
use App\Models\User;

class GenerationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Generation $generation): bool
    {
        return $user->id === $generation->user_id || $user->is_admin;
    }

    public function download(User $user, Generation $generation): bool
    {
        return $user->id === $generation->user_id || $user->is_admin;
    }
}

<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->is_admin;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->is_admin;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->is_admin;
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->is_admin;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->id === $project->user_id || $user->is_admin;
    }
}

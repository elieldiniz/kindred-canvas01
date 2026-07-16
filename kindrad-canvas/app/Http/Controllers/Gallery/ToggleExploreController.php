<?php

namespace App\Http\Controllers\Gallery;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class ToggleExploreController extends Controller
{
    public function __invoke(Project $project): RedirectResponse
    {
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        $project->is_in_explore = ! $project->is_in_explore;
        $project->save();

        return back();
    }
}

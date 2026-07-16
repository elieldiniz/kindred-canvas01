<?php

namespace App\Livewire\Gallery;

use App\Exceptions\BillingAccessDeniedException;
use App\Models\Project;
use App\Models\User;
use App\Services\Exceptions\CreditInsufficientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * Remix endpoint: POST /explore/{project}/remix
 *
 * Clones the source project's prompt/style/subject/inputs into a fresh
 * Project owned by the authenticated user, sets remixed_from_project_id, and
 * redirects to the wizard (`/projects/new?id=NEW_ID`) where the user picks
 * photos, mode, and runs the generation.
 */
class RemixController extends Controller
{
    public function __invoke(int $project): RedirectResponse
    {
        $authId = Auth::id();

        if ($authId === null) {
            abort(401);
        }

        $source = Project::query()->findOrFail($project);

        if (! $source->is_published || ! $source->is_in_explore) {
            abort(404);
        }

        if ($source->user_id === $authId) {
            return redirect()->route('projects.show', $source);
        }

        $newProject = Project::create([
            'user_id' => $authId,
            'product_id' => $source->product_id,
            'category_id' => $source->category_id,
            'style_id' => $source->style_id,
            'layout_id' => $source->layout_id,
            'mode_id' => $source->mode_id,
            'status_id' => $source->status_id,
            'subject_type' => $source->subject_type,
            'custom_prompt' => $source->custom_prompt,
            'inputs' => $source->inputs,
            'pose_id' => $source->pose_id,
            'title' => $source->title,
            'remixed_from_project_id' => $source->id,
        ]);

        // Authorization checks happen during the wizard's actual generate action.
        // We just ensure the target user is not suspended + still has credits
        // — fail fast with a helpful flash instead of abandoning the user later.
        $user = User::find($authId);

        if ($user !== null) {
            if ($user->is_suspended) {
                $newProject->forceDelete();

                throw BillingAccessDeniedException::forUser($user);
            }

            if (($user->credit_balance ?? 0) < 1 && $user->isFreeTier()) {
                $newProject->forceDelete();
                throw CreditInsufficientException::for((int) ($user->credit_balance ?? 0), 1);
            }
        }

        return redirect()->route('projects.new', ['id' => $newProject->id]);
    }
}

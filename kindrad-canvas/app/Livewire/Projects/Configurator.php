<?php

namespace App\Livewire\Projects;

use App\Actions\Generation\SubmitGeneration;
use App\Models\Category;
use App\Models\Pose;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\ProjectStatus;
use App\Models\ScenePreset;
use App\Models\Style;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Configurator extends Component
{
    #[Url]
    public ?int $id = null;

    public ?int $projectId = null;

    public ?int $productId = null;

    public ?int $categoryId = null;

    public ?int $styleId = null;

    public ?int $poseId = null;

    public ?int $scenePresetId = null;

    public ?string $subjectType = null;

    public string $customPrompt = '';

    public bool $isReadOnly = false;

    public function mount(?int $id = null): void
    {
        if ($id !== null) {
            $this->projectId = $id;
            $project = $this->authorizeOrAbort();
            $this->rehydrateFromProject($project);

            return;
        }

        $user = Auth::user();

        if ($user === null) {
            abort(401);
        }

        $product = Product::where('slug', 'mug')->first();

        $project = Project::create([
            'user_id' => $user->id,
            'product_id' => $product?->id,
            'mode_id' => ProjectMode::where('slug', 'mug')->value('id'),
            'status_id' => ProjectStatus::where('slug', 'draft')->value('id'),
        ]);

        $this->projectId = $project->id;
    }

    #[On('product-selected')]
    public function selectProduct(int $productId): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $product = Product::query()
            ->whereKey($productId)
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->first();

        if ($product === null) {
            return;
        }

        $project->product_id = $product->id;
        // Default mode to match product slug if mode exists
        $modeId = ProjectMode::where('slug', $product->slug)->value('id');
        $project->mode_id = $modeId;
        $project->category_id = null;
        $project->style_id = null;
        $project->save();

        $this->productId = $product->id;
        $this->categoryId = null;
        $this->styleId = null;
    }

    #[On('subject-type-selected')]
    public function selectSubjectType(string $type): void
    {
        $project = $this->authorizeUpdateOrAbort();

        if (! in_array($type, Project::SUBJECT_TYPES, true)) {
            return;
        }

        $project->subject_type = $type;
        $project->pose_id = null;
        $project->save();

        $this->subjectType = $type;
        $this->poseId = null;
    }

    #[On('category-selected')]
    public function selectCategory(int $categoryId): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $category = Category::query()
            ->whereKey($categoryId)
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->first();

        if ($category === null) {
            return;
        }

        $project->category_id = $category->id;
        $project->style_id = null;
        $project->scene_preset_id = ScenePreset::query()
            ->where('category_id', $category->id)
            ->where('is_default', true)
            ->value('id');
        $project->save();

        $this->categoryId = $category->id;
        $this->styleId = null;
        $this->scenePresetId = $project->scene_preset_id;
    }

    #[On('style-selected')]
    public function selectStyle(int $styleId): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $style = Style::query()
            ->whereKey($styleId)
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->first();

        if ($style === null) {
            return;
        }

        if ($this->categoryId !== null) {
            $exists = Category::find($this->categoryId)?->styles()->whereKey($styleId)->exists();
            if ($exists !== true) {
                return;
            }
        }

        $project->style_id = $style->id;

        $firstLayout = $style->layouts()->whereHas('status', fn ($q) => $q->where('slug', 'active'))->first();
        if ($firstLayout !== null) {
            $project->layout_id = $firstLayout->id;
        }

        $project->save();

        $this->styleId = $style->id;
    }

    #[On('pose-selected')]
    public function selectPose(int $poseId): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $pose = Pose::query()
            ->whereKey($poseId)
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->first();

        if ($pose === null) {
            return;
        }

        $project->pose_id = $pose->id;
        $project->save();

        $this->poseId = $pose->id;
    }

    #[On('scene-selected')]
    public function selectScenePreset(int $scenePresetId): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $preset = ScenePreset::query()->whereKey($scenePresetId)->first();

        if ($preset === null) {
            return;
        }

        $project->scene_preset_id = $preset->id;
        $project->save();

        $this->scenePresetId = $preset->id;
    }

    #[On('custom-prompt-updated')]
    public function updateCustomPrompt(string $value): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $value = mb_substr($value, 0, 500);

        $project->custom_prompt = $value === '' ? null : $value;
        $project->save();

        $this->customPrompt = $value;
    }

    #[On('photos-updated')]
    public function refreshPhotos(): void
    {
        // Simply listening to the event triggers a re-render,
        // which re-evaluates the $this->canGenerate computed property.
    }

    public function updatedProductId(): void
    {
        $this->categoryId = null;
        $this->styleId = null;
    }

    public function updatedCategoryId(): void
    {
        $this->styleId = null;
    }

    public function updatedSubjectType(): void
    {
        $this->poseId = null;
    }

    public function needsPose(): bool
    {
        return in_array($this->subjectType, ['casal', 'familia'], true);
    }

    public function slotCount(): int
    {
        return in_array($this->subjectType, ['casal', 'familia'], true) ? 2 : 1;
    }

    #[Computed]
    public function creditBalance(): int
    {
        $user = Auth::user();

        return $user !== null ? (int) $user->credit_balance : 0;
    }

    #[Computed]
    public function canGenerate(): bool
    {
        if ($this->projectId === null) {
            return false;
        }

        $project = Project::find($this->projectId);
        if ($project === null) {
            return false;
        }

        if ($project->first_generated_at !== null) {
            return false;
        }

        if ($this->productId === null
            || $this->categoryId === null
            || $this->styleId === null
            || $this->subjectType === null
        ) {
            return false;
        }

        if ($this->needsPose() && $this->poseId === null) {
            return false;
        }

        if ($project->photos()->count() < $this->slotCount()) {
            return false;
        }

        $user = Auth::user();
        if ($user === null || (int) $user->credit_balance <= 0) {
            return false;
        }

        return true;
    }

    public function generate(): RedirectResponse|Redirector
    {
        if (! $this->canGenerate) {
            abort(403);
        }

        $project = Project::findOrFail($this->projectId);
        $user = Auth::user();

        if ($user === null) {
            abort(401);
        }

        $this->authorize('update', $project);

        if ($project->layout_id === null && $project->style_id !== null) {
            $firstLayout = $project->style->layouts()->whereHas('status', fn ($q) => $q->where('slug', 'active'))->first();
            if ($firstLayout !== null) {
                $project->layout_id = $firstLayout->id;
                $project->save();
            }
        }

        app(SubmitGeneration::class)->execute($user, $project);

        return redirect()->route('projects.show', $project);
    }

    public function render()
    {
        return view('livewire.projects.configurator');
    }

    private function authorizeOrAbort(): Project
    {
        if ($this->projectId === null) {
            abort(404);
        }

        $project = Project::find($this->projectId);

        if ($project === null || $project->trashed()) {
            abort(404);
        }

        $this->authorize('view', $project);

        return $project;
    }

    private function authorizeUpdateOrAbort(): Project
    {
        $project = $this->authorizeOrAbort();
        $this->authorize('update', $project);

        if ($project->first_generated_at !== null) {
            abort(409, 'Project already has a generation; only custom_prompt is editable.');
        }

        return $project;
    }

    private function rehydrateFromProject(Project $project): void
    {
        $this->productId = $project->product_id;
        $this->categoryId = $project->category_id;
        $this->styleId = $project->style_id;
        $this->poseId = $project->pose_id;
        $this->scenePresetId = $project->scene_preset_id;
        $this->subjectType = $project->subject_type;
        $this->customPrompt = (string) ($project->custom_prompt ?? '');
        $this->isReadOnly = $project->first_generated_at !== null;
    }
}

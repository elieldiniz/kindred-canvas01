<?php

namespace App\Livewire\Projects;

use App\Actions\Generation\SubmitGeneration;
use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMode;
use App\Models\ProjectStatus;
use App\Models\Style;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Wizard extends Component
{
    public int $step = 1;

    public ?int $projectId = null;

    public ?int $modeId = null;

    public ?int $categoryId = null;

    public ?int $styleId = null;

    public ?int $layoutId = null;

    public ?int $sourceImageId = null;

    public array $inputs = [];

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'modeId' => ['required', 'integer', 'exists:project_modes,id'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'styleId' => ['required', 'integer', 'exists:styles,id'],
            'layoutId' => ['required', 'integer', 'exists:layouts,id'],
            'inputs.name' => ['required', 'string', 'max:80'],
            'inputs.phrase' => ['nullable', 'string', 'max:240'],
            'inputs.theme' => ['nullable', 'string', 'max:120'],
            'inputs.dedicatoria' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function title(): string
    {
        return __('New Project');
    }

    public function mount(?int $id = null): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(401);
        }

        if ($id !== null) {
            $this->projectId = $id;
            $project = $this->authorizeOrAbort();
            $this->hydrateFromProject($project);

            return;
        }

        $project = Project::create([
            'user_id' => $user->id,
            'product_id' => Product::where('slug', 'mug')->value('id'),
            'status_id' => ProjectStatus::where('slug', 'draft')->value('id'),
            'inputs' => [],
        ]);

        $this->hydrateFromProject($project);
    }

    public function hydrate(): void
    {
        if ($this->projectId !== null) {
            $this->authorizeOrAbort();
        }
    }

    public function updatedProjectId(): void
    {
        if ($this->projectId === null) {
            return;
        }

        $project = $this->authorizeOrAbort();
        $this->hydrateFromProject($project);
    }

    #[On('mode-selected')]
    public function selectMode(int $modeId): void
    {
        $mode = ProjectMode::whereIn('slug', ['free', 'mug'])->find($modeId);

        if ($mode === null) {
            $this->addError('modeId', __('Please select a mode.'));

            return;
        }

        $project = $this->authorizeUpdateOrAbort();

        if ($project->isModeLocked()) {
            return;
        }

        $project->mode_id = $mode->id;
        $project->save();

        $this->modeId = $mode->id;
        $this->step = 2;
        $this->resetErrorBag();
    }

    #[On('category-selected')]
    public function selectCategory(int $categoryId): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $exists = Category::whereKey($categoryId)
            ->whereHas('product', fn ($q) => $q->where('slug', 'mug'))
            ->whereHas('status', fn ($q) => $q->where('slug', 'active'))
            ->exists();

        if (! $exists) {
            $this->addError('categoryId', __('Invalid category.'));

            return;
        }

        $project->category_id = $categoryId;
        $project->style_id = null;
        $project->layout_id = null;
        $project->save();

        $this->categoryId = $categoryId;
        $this->styleId = null;
        $this->layoutId = null;
        $this->step = 3;
        $this->resetErrorBag();
    }

    #[On('style-selected')]
    public function selectStyle(int $styleId): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $categoryId = $this->categoryId ?? $project->category_id;

        if ($categoryId === null) {
            $this->addError('styleId', __('Choose a category before picking a style.'));

            return;
        }

        $exists = Category::find($categoryId)?->styles()->whereKey($styleId)->exists();

        if (! $exists) {
            $this->addError('styleId', __('Style is not associated with this category.'));

            return;
        }

        $project->style_id = $styleId;
        $project->layout_id = null;
        $project->save();

        $this->styleId = $styleId;
        $this->layoutId = null;
        $this->step = 4;
        $this->resetErrorBag();
    }

    #[On('layout-selected')]
    public function selectLayout(int $layoutId): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $styleId = $this->styleId ?? $project->style_id;

        if ($styleId === null) {
            $this->addError('layoutId', __('Choose a style before picking a layout.'));

            return;
        }

        $exists = Style::find($styleId)?->layouts()->whereKey($layoutId)->exists();

        if (! $exists) {
            $this->addError('layoutId', __('Layout is not associated with this style.'));

            return;
        }

        $project->layout_id = $layoutId;
        $project->save();

        $this->layoutId = $layoutId;
        $this->step = 5;
        $this->resetErrorBag();
    }

    #[On('go-to-step')]
    public function receiveGoToStep(int $step): void
    {
        $this->goToStep($step);
    }

    #[On('source-image-uploaded')]
    public function saveSourceImage(int $sourceImageId): void
    {
        $project = $this->authorizeUpdateOrAbort();

        $project->source_image_id = $sourceImageId;
        $project->save();

        $this->sourceImageId = $sourceImageId;
        $this->resetErrorBag();
    }

    #[On('source-image-removed')]
    public function removeSourceImage(): void
    {
        if ($this->projectId === null) {
            return;
        }

        $project = $this->authorizeUpdateOrAbort();

        $project->source_image_id = null;
        $project->save();

        $this->sourceImageId = null;
        $this->resetErrorBag();
    }

    #[On('inputs-updated')]
    public function updateInput(string $key, string $value): void
    {
        if ($this->projectId === null) {
            return;
        }

        $this->authorizeUpdateOrAbort();

        if (! in_array($key, ['name', 'phrase', 'theme', 'dedicatoria'], true)) {
            return;
        }

        $this->inputs[$key] = $value;
    }

    public function goToStep(int $step): void
    {
        if ($step < 1 || $step > 7) {
            return;
        }

        if ($step > $this->step) {
            $project = $this->authorizeOrAbort();

            if ($project === null) {
                abort(404);
            }

            $missing = [];

            if ($step >= 2 && $project->mode_id === null) {
                $missing[] = __('mode');
            }

            if ($step >= 3 && $project->category_id === null) {
                $missing[] = __('category');
            }

            if ($step >= 4 && $project->style_id === null) {
                $missing[] = __('style');
            }

            if ($step >= 5 && $project->layout_id === null) {
                $missing[] = __('layout');
            }

            if ($missing !== []) {
                $this->addError(
                    'wizard',
                    __('Complete the earlier steps first: :steps.', ['steps' => implode(', ', $missing)]),
                );

                return;
            }
        }

        $this->step = $step;
        $this->resetErrorBag();
    }

    #[On('submit-wizard')]
    public function submit(): RedirectResponse|Redirector
    {
        if ($this->projectId === null) {
            abort(404);
        }

        $project = Project::find($this->projectId);

        if ($project === null) {
            abort(404);
        }

        $this->authorize('update', $project);

        $user = auth()->user();

        if ($user === null || (int) $user->credit_balance <= 0) {
            $this->addError('generate', __("You're out of credits."));

            return redirect()->route('dashboard');
        }

        app(SubmitGeneration::class)->execute($user, $project);

        return redirect()->route('projects.show', ['project' => $project->id]);
    }

    public function next(): void
    {
        $project = $this->authorizeOrAbort();

        if ($this->step === 1) {
            if ($project->isModeLocked()) {
                $this->step = min(7, $this->step + 1);

                return;
            }

            $validIds = ProjectMode::whereIn('slug', ['free', 'mug'])->pluck('id')->all();
            $modeId = $this->modeId !== null ? (int) $this->modeId : null;

            if ($modeId === null || ! in_array($modeId, $validIds, true)) {
                $this->addError('modeId', __('Please select a mode.'));

                return;
            }
        }

        if ($this->step === 5) {
            $this->step = 6;
            $this->resetErrorBag();

            return;
        }

        if ($this->step === 6) {
            $this->authorize('update', $project);

            $this->validate();

            $project->inputs = $this->inputs;
            $project->save();

            $this->step = 7;
            $this->resetErrorBag();

            return;
        }

        $this->step = min(7, $this->step + 1);
        $this->resetErrorBag();
    }

    public function back(): void
    {
        $this->authorizeOrAbort();
        $this->step = max(1, $this->step - 1);
        $this->resetErrorBag();
    }

    public function exit(): RedirectResponse|Redirector
    {
        if ($this->projectId !== null) {
            $this->authorizeOrAbort();
        }

        return redirect()->route('dashboard');
    }

    /**
     * @return array<string, string>
     */
    public function sectionName(): string
    {
        return match ($this->step) {
            1 => __('Mode'),
            2 => __('Category'),
            3 => __('Style'),
            4 => __('Layout'),
            5 => __('Source Image'),
            6 => __('Inputs'),
            7 => __('Review'),
            default => __('Mode'),
        };
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

        return $project;
    }

    private function hydrateFromProject(Project $project): void
    {
        $this->projectId = $project->id;
        $this->modeId = $project->mode_id;
        $this->categoryId = $project->category_id;
        $this->styleId = $project->style_id;
        $this->layoutId = $project->layout_id;
        $this->sourceImageId = $project->source_image_id;
        $this->inputs = $project->inputs ?? [];
        $this->step = $this->computeMaxStep($project);
    }

    private function computeMaxStep(Project $project): int
    {
        $step = 1;

        if ($project->mode_id !== null) {
            $step = 2;
        }

        if ($project->category_id !== null) {
            $step = 3;
        }

        if ($project->style_id !== null) {
            $step = 4;
        }

        if ($project->layout_id !== null) {
            $step = 5;
        }

        if (filled($this->inputs['name'] ?? null)) {
            $step = 7;
        }

        return $step;
    }

    public function render()
    {
        return view('livewire.projects.wizard');
    }
}

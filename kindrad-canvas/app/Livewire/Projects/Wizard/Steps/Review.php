<?php

namespace App\Livewire\Projects\Wizard\Steps;

use App\Models\Category as CategoryModel;
use App\Models\Layout as LayoutModel;
use App\Models\ProjectMode;
use App\Models\SourceImage;
use App\Models\Style as StyleModel;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Review extends Component
{
    public int $projectId;

    public ?int $modeId = null;

    public ?int $categoryId = null;

    public ?int $styleId = null;

    public ?int $layoutId = null;

    public ?int $sourceImageId = null;

    /**
     * @var array<string, mixed>
     */
    public array $inputs = [];

    /**
     * @param  array<string, mixed>  $inputs
     */
    public function mount(
        int $projectId,
        ?int $modeId = null,
        ?int $categoryId = null,
        ?int $styleId = null,
        ?int $layoutId = null,
        ?int $sourceImageId = null,
        array $inputs = [],
    ): void {
        $this->projectId = $projectId;
        $this->modeId = $modeId;
        $this->categoryId = $categoryId;
        $this->styleId = $styleId;
        $this->layoutId = $layoutId;
        $this->sourceImageId = $sourceImageId;
        $this->inputs = $inputs;
    }

    public function modeLabel(): ?string
    {
        if ($this->modeId === null) {
            return null;
        }

        return ProjectMode::find($this->modeId)?->name;
    }

    public function categoryLabel(): ?string
    {
        if ($this->categoryId === null) {
            return null;
        }

        return CategoryModel::find($this->categoryId)?->name;
    }

    public function styleLabel(): ?string
    {
        if ($this->styleId === null) {
            return null;
        }

        return StyleModel::find($this->styleId)?->name;
    }

    public function layoutLabel(): ?string
    {
        if ($this->layoutId === null) {
            return null;
        }

        return LayoutModel::find($this->layoutId)?->name;
    }

    public function sourceImageUrl(): ?string
    {
        if ($this->sourceImageId === null) {
            return null;
        }

        $image = SourceImage::find($this->sourceImageId);

        if ($image === null) {
            return null;
        }

        return Storage::disk($image->disk)->url($image->path);
    }

    public function sourceImageFilename(): ?string
    {
        if ($this->sourceImageId === null) {
            return null;
        }

        return SourceImage::find($this->sourceImageId)?->original_filename;
    }

    public function creditBalance(): int
    {
        return (int) (auth()->user()?->credit_balance ?? 0);
    }

    public function render()
    {
        return view('livewire.projects.wizard.steps.review');
    }
}

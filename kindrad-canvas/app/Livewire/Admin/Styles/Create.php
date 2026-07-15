<?php

namespace App\Livewire\Admin\Styles;

use App\Livewire\Admin\Concerns\StoresThumbnail;
use App\Models\Category;
use App\Models\Style;
use App\Models\StyleStatus;
use App\Services\AuditLogger;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Create extends Component
{
    use StoresThumbnail;
    use WithFileUploads;

    public string $name = '';

    public string $slug = '';

    public ?string $prompt_fragment = null;

    public ?int $status_id = null;

    /** @var array<int> */
    public array $selectedCategories = [];

    /** @var TemporaryUploadedFile|null */
    public $thumbnail = null;

    protected function disk(): string
    {
        return config('filesystems.default');
    }

    protected function thumbnailFolder(): string
    {
        return 'catalog/styles';
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function updatedName(): void
    {
        $this->slug = Str::slug($this->name);
    }

    public function save(AuditLogger $audit): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:styles,slug'],
            'prompt_fragment' => ['nullable', 'string'],
            'status_id' => ['required', 'exists:style_statuses,id'],
            'selectedCategories' => ['array'],
            'selectedCategories.*' => ['exists:categories,id'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $style = Style::create($this->only(['name', 'slug', 'prompt_fragment', 'status_id']));

        if ($this->thumbnail !== null) {
            $path = $this->storeThumbnail($this->thumbnail);
            $style->thumbnail_path = $path;
            $style->save();
        }

        if (! empty($this->selectedCategories)) {
            $style->categories()->sync($this->selectedCategories);
        }

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_style',
            target: $style,
            payload: [
                'event' => 'created',
                'attributes' => $style->only(['name', 'slug', 'status_id']),
                'categories' => $this->selectedCategories,
                'thumbnail_uploaded' => $this->thumbnail !== null,
            ],
        );

        $this->redirect(route('admin.styles.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.styles.create', [
            'statuses' => StyleStatus::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Create Style'),
        ]);
    }
}

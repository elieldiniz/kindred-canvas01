<?php

namespace App\Livewire\Admin\Categories;

use App\Livewire\Admin\Concerns\StoresThumbnail;
use App\Models\Category;
use App\Models\CategoryStatus;
use App\Models\Product;
use App\Models\Style;
use App\Services\AuditLogger;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Create extends Component
{
    use StoresThumbnail;
    use WithFileUploads;

    public ?int $product_id = null;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public int $sort_order = 0;

    public ?int $status_id = null;

    /** @var array<int> */
    public array $selectedStyles = [];

    /** @var TemporaryUploadedFile|null */
    public $thumbnail = null;

    protected function disk(): string
    {
        return config('filesystems.default');
    }

    protected function thumbnailFolder(): string
    {
        return 'catalog/categories';
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
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255',
                Rule::unique('categories', 'slug')->where('product_id', $this->product_id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['required', 'integer'],
            'status_id' => ['required', 'exists:category_statuses,id'],
            'selectedStyles' => ['array'],
            'selectedStyles.*' => ['exists:styles,id'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $category = Category::create($this->only([
            'product_id', 'name', 'slug', 'description', 'sort_order', 'status_id',
        ]));

        if ($this->thumbnail !== null) {
            $path = $this->storeThumbnail($this->thumbnail);
            $category->thumbnail_path = $path;
            $category->save();
        }

        if (! empty($this->selectedStyles)) {
            $category->styles()->sync($this->selectedStyles);
        }

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_category',
            target: $category,
            payload: [
                'event' => 'created',
                'attributes' => $category->only(['product_id', 'name', 'slug', 'sort_order', 'status_id']),
                'styles' => $this->selectedStyles,
                'thumbnail_uploaded' => $this->thumbnail !== null,
            ],
        );

        $this->redirect(route('admin.categories.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.categories.create', [
            'products' => Product::orderBy('name')->get(),
            'statuses' => CategoryStatus::orderBy('name')->get(),
            'styles' => Style::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Create Category'),
        ]);
    }
}

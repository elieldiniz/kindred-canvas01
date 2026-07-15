<?php

namespace App\Livewire\Admin\Layouts;

use App\Livewire\Admin\Concerns\StoresThumbnail;
use App\Models\Layout;
use App\Models\LayoutStatus;
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

    public ?int $status_id = null;

    public string $proportion_ratio = '1:1';

    public ?string $safe_area_overlay = null;

    /** @var TemporaryUploadedFile|null */
    public $preview = null;

    protected function disk(): string
    {
        return config('filesystems.default');
    }

    protected function thumbnailFolder(): string
    {
        return 'catalog/layouts';
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
            'slug' => ['required', 'string', 'max:255', 'unique:layouts,slug'],
            'status_id' => ['required', 'exists:layout_statuses,id'],
            'proportion_ratio' => ['required', 'string', 'max:10'],
            'safe_area_overlay' => ['nullable', 'string'],
            'preview' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $data = $this->only(['name', 'slug', 'status_id', 'proportion_ratio']);
        $data['safe_area_overlay'] = $this->safe_area_overlay ? json_decode($this->safe_area_overlay, true) : null;

        $layout = Layout::create($data);

        if ($this->preview !== null) {
            $path = $this->storeThumbnail($this->preview);
            $layout->preview_path = $path;
            $layout->save();
        }

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_layout',
            target: $layout,
            payload: [
                'event' => 'created',
                'attributes' => $layout->only(['name', 'slug', 'status_id', 'proportion_ratio']),
                'preview_uploaded' => $this->preview !== null,
            ],
        );

        $this->redirect(route('admin.layouts.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.layouts.create', [
            'statuses' => LayoutStatus::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Create Layout'),
        ]);
    }
}

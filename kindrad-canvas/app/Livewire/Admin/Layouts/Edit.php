<?php

namespace App\Livewire\Admin\Layouts;

use App\Livewire\Admin\Concerns\StoresThumbnail;
use App\Models\Layout;
use App\Models\LayoutStatus;
use App\Models\Style;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class Edit extends Component
{
    use StoresThumbnail;
    use WithFileUploads;

    public Layout $layoutModel;

    public string $name = '';

    public string $slug = '';

    public ?int $status_id = null;

    public string $proportion_ratio = '1:1';

    public ?string $safe_area_overlay = null;

    /** @var array<int> */
    public array $selectedStyles = [];

    /** @var TemporaryUploadedFile|null */
    public $preview = null;

    public bool $removePreview = false;

    protected function disk(): string
    {
        return config('filesystems.default');
    }

    protected function thumbnailFolder(): string
    {
        return 'catalog/layouts';
    }

    public function mount(int|Layout $layout): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $model = $layout instanceof Layout ? $layout : Layout::findOrFail($layout);
        $this->layoutModel = $model;
        $this->name = $model->name;
        $this->slug = $model->slug;
        $this->status_id = $model->status_id;
        $this->proportion_ratio = $model->proportion_ratio;
        $this->safe_area_overlay = $model->safe_area_overlay ? json_encode($model->safe_area_overlay, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null;
        $this->selectedStyles = $model->styles()->pluck('styles.id')->toArray();
    }

    public function removeExistingPreview(): void
    {
        $this->removePreview = true;
    }

    public function previewUrl(): ?string
    {
        if ($this->removePreview || $this->preview !== null) {
            return null;
        }

        $path = $this->layoutModel->preview_path;

        return $path !== null ? Storage::disk($this->disk())->url($path) : null;
    }

    public function save(AuditLogger $audit): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:layouts,slug,'.$this->layoutModel->id],
            'status_id' => ['required', 'exists:layout_statuses,id'],
            'proportion_ratio' => ['required', 'string', 'max:10'],
            'safe_area_overlay' => ['nullable', 'string'],
            'selectedStyles' => ['array'],
            'selectedStyles.*' => ['exists:styles,id'],
            'preview' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $tracked = ['name', 'slug', 'status_id', 'proportion_ratio', 'safe_area_overlay'];
        $stylesBefore = $this->layoutModel->styles()->pluck('styles.id')->all();
        $before = $this->layoutModel->only($tracked);

        $data = $this->only(['name', 'slug', 'status_id', 'proportion_ratio']);
        $data['safe_area_overlay'] = $this->safe_area_overlay ? json_decode($this->safe_area_overlay, true) : null;

        $this->layoutModel->update($data);
        $this->layoutModel->styles()->sync($this->selectedStyles);

        $previewChanged = false;
        $oldPreview = $this->layoutModel->preview_path;

        if ($this->preview !== null) {
            $newPath = $this->storeThumbnail($this->preview);
            $this->layoutModel->preview_path = $newPath;
            $this->layoutModel->save();
            $previewChanged = true;

            if ($oldPreview !== null) {
                $this->deleteThumbnail($oldPreview);
            }
        } elseif ($this->removePreview && $oldPreview !== null) {
            $this->layoutModel->preview_path = null;
            $this->layoutModel->save();
            $previewChanged = true;

            $this->deleteThumbnail($oldPreview);
        }

        $after = $this->layoutModel->fresh()->only($tracked);
        $changedAttrs = array_keys(array_diff_assoc($after, $before));
        $stylesChanged = $this->selectedStyles !== $stylesBefore;

        if ($changedAttrs !== [] || $stylesChanged || $previewChanged) {
            $payload = ['event' => 'updated'];
            if ($changedAttrs !== []) {
                $payload['changed'] = $changedAttrs;
                $payload['before'] = array_intersect_key($before, array_flip($changedAttrs));
                $payload['after'] = array_intersect_key($after, array_flip($changedAttrs));
            }
            if ($stylesChanged) {
                $payload['styles_before'] = $stylesBefore;
                $payload['styles_after'] = $this->selectedStyles;
            }
            if ($previewChanged) {
                $payload['preview_before'] = $oldPreview;
                $payload['preview_after'] = $this->layoutModel->fresh()->preview_path;
            }

            $audit->record(
                actor: auth()->user(),
                actionSlug: 'edit_layout',
                target: $this->layoutModel,
                payload: $payload,
            );
        }

        $this->redirect(route('admin.layouts.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.layouts.edit', [
            'statuses' => LayoutStatus::orderBy('name')->get(),
            'styles' => Style::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Edit Layout'),
        ]);
    }
}

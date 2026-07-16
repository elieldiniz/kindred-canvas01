<?php

namespace App\Livewire\Admin\Showcase;

use App\Models\ShowcaseItem;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $newTitle = '';

    /**
     * Holds inline-edited titles keyed by ShowcaseItem id.
     *
     * @var array<int, string>
     */
    public array $titles = [];

    /**
     * @var UploadedFile|null
     */
    public $newImage;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'newTitle' => ['nullable', 'string', 'max:255'],
            'newImage' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function updatedNewImage(): void
    {
        $this->validateOnly('newImage');
    }

    /**
     * Named saveArtwork (NOT upload) to avoid a name-collision with Livewire's
     * built-in JavaScript `upload()` helper that WithFileUploads injects into
     * every component's Alpine scope. Using "upload" as the wire:submit target
     * causes the JS helper to intercept the form submit before the request ever
     * reaches the server-side PHP method, resulting in a TypeError in the browser.
     */
    public function saveArtwork(): void
    {
        $data = $this->validate();

        /** @var UploadedFile $file */
        $file = $data['newImage'];
        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        $path = 'showcase/'.Str::uuid().'.'.$extension;

        $disk = Storage::disk(config('generation.disk'));
        $stored = $disk->putFileAs(dirname($path), $file, basename($path), 'public');

        if ($stored === false) {
            $this->addError('newImage', __('Upload failed: could not store the file. Please try again.'));

            return;
        }

        $nextOrder = (int) (ShowcaseItem::query()->max('sort_order') ?? 0) + 10;

        $item = ShowcaseItem::create([
            'title' => $data['newTitle'] ?: null,
            'image_path' => $path,
            'sort_order' => $nextOrder,
            'is_active' => true,
        ]);

        app(AuditLogger::class)->record(
            actor: auth()->user(),
            actionSlug: 'create_showcase_item',
            target: $item,
            payload: ['title' => $item->title, 'image_path' => $path],
        );

        $this->reset(['newTitle', 'newImage']);
        $this->resetPage();
    }

    public function toggleActive(int $itemId): void
    {
        $item = ShowcaseItem::query()->findOrFail($itemId);
        $was = (bool) $item->is_active;
        $item->is_active = ! $was;
        $item->save();

        app(AuditLogger::class)->record(
            actor: auth()->user(),
            actionSlug: $was ? 'deactivate_showcase_item' : 'activate_showcase_item',
            target: $item,
            payload: ['before' => $was, 'after' => $item->is_active],
        );
    }

    public function move(int $itemId, string $direction): void
    {
        if (! in_array($direction, ['up', 'down'], true)) {
            return;
        }

        $items = ShowcaseItem::query()->orderBy('sort_order')->get(['id', 'sort_order']);

        $currentIndex = $items->search(fn ($i) => $i->id === $itemId);
        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;
        if ($targetIndex < 0 || $targetIndex >= $items->count()) {
            return;
        }

        $currentOrder = $items[$currentIndex]->sort_order;
        $targetOrder = $items[$targetIndex]->sort_order;

        // Swap order values.
        ShowcaseItem::query()->where('id', $itemId)->update(['sort_order' => $targetOrder]);
        ShowcaseItem::query()->where('id', $items[$targetIndex]->id)->update(['sort_order' => $currentOrder]);

        app(AuditLogger::class)->record(
            actor: auth()->user(),
            actionSlug: 'reorder_showcase_item',
            target: ShowcaseItem::find($itemId),
            payload: ['direction' => $direction, 'from' => $currentOrder, 'to' => $targetOrder],
        );
    }

    public function updateTitle(int $itemId): void
    {
        $validated = $this->validateOnly('titles.'.$itemId, [
            'titles.'.$itemId => ['nullable', 'string', 'max:255'],
        ]);

        $item = ShowcaseItem::query()->findOrFail($itemId);
        $item->title = $validated['titles.'.$itemId] ?: null;
        $item->save();

        app(AuditLogger::class)->record(
            actor: auth()->user(),
            actionSlug: 'update_showcase_item',
            target: $item,
            payload: ['title' => $item->title],
        );
    }

    public function delete(int $itemId): void
    {
        $item = ShowcaseItem::query()->findOrFail($itemId);
        $path = $item->image_path;

        Storage::disk(config('generation.disk'))->delete($path);

        app(AuditLogger::class)->record(
            actor: auth()->user(),
            actionSlug: 'delete_showcase_item',
            target: $item,
            payload: ['image_path' => $path],
        );

        $item->delete();
    }

    public function render(): View
    {
        $items = ShowcaseItem::query()
            ->orderBy('sort_order')
            ->get();

        // Keep the $titles array in sync with the current DB values so
        // existing titles are pre-filled in the inline-edit inputs.
        foreach ($items as $item) {
            if (! array_key_exists($item->id, $this->titles)) {
                $this->titles[$item->id] = (string) ($item->title ?? '');
            }
        }

        return view('livewire.admin.showcase.index', [
            'items' => $items,
        ])->layout('components.layouts.admin', [
            'header' => __('Showcase'),
        ]);
    }
}

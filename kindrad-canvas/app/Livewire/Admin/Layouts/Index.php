<?php

namespace App\Livewire\Admin\Layouts;

use App\Models\Layout;
use App\Services\AuditLogger;
use Livewire\Component;

class Index extends Component
{
    public bool $confirmDelete = false;

    public ?int $deleteId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->confirmDelete = true;
    }

    public function delete(AuditLogger $audit): void
    {
        $layout = Layout::findOrFail($this->deleteId);

        if ($layout->styles()->exists()) {
            $this->addError('deleteId', 'Cannot delete a layout that has styles.');

            return;
        }

        $snapshot = $layout->only(['id', 'name', 'slug']);
        $layout->delete();

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_layout',
            target: $layout,
            payload: ['event' => 'deleted', 'snapshot' => $snapshot],
        );

        $this->confirmDelete = false;
        $this->deleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.layouts.index', [
            'layouts' => Layout::with('status')
                ->withCount('styles')
                ->latest()
                ->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Layouts'),
        ]);
    }
}

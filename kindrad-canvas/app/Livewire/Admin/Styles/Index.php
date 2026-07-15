<?php

namespace App\Livewire\Admin\Styles;

use App\Models\Style;
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
        $style = Style::findOrFail($this->deleteId);
        $snapshot = $style->only(['id', 'name', 'slug']);
        $style->delete();

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_style',
            target: $style,
            payload: ['event' => 'deleted', 'snapshot' => $snapshot],
        );

        $this->confirmDelete = false;
        $this->deleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.styles.index', [
            'styles' => Style::with('status')
                ->withCount('categories')
                ->latest()
                ->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Styles'),
        ]);
    }
}

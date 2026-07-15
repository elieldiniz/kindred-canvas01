<?php

namespace App\Livewire\Admin\PromptTemplates;

use App\Models\PromptTemplate;
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

    public function delete(): void
    {
        PromptTemplate::findOrFail($this->deleteId)->delete();
        $this->confirmDelete = false;
        $this->deleteId = null;
    }

    public function render()
    {
        return view('livewire.admin.prompt-templates.index', [
            'templates' => PromptTemplate::with('product', 'category', 'style', 'layout')
                ->orderBy('product_id')
                ->orderBy('category_id')
                ->latest('version')
                ->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Prompt templates'),
        ]);
    }
}

<?php

namespace App\Livewire\Admin\PromptTemplates;

use App\Models\Category;
use App\Models\Layout;
use App\Models\Product;
use App\Models\PromptTemplate;
use App\Models\Style;
use App\Services\AuditLogger;
use Livewire\Component;

class Create extends Component
{
    public ?int $product_id = null;

    public ?int $category_id = null;

    public ?int $style_id = null;

    public ?int $layout_id = null;

    public string $body = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function save(AuditLogger $audit): void
    {
        $this->validate([
            'product_id' => ['required', 'exists:products,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'style_id' => ['required', 'exists:styles,id'],
            'layout_id' => ['required', 'exists:layouts,id'],
            'body' => ['required', 'string'],
        ]);

        $exists = PromptTemplate::query()
            ->where('product_id', $this->product_id)
            ->where('category_id', $this->category_id)
            ->where('style_id', $this->style_id)
            ->where('layout_id', $this->layout_id)
            ->exists();

        if ($exists) {
            $this->addError('product_id', __('A template already exists for this combination.'));

            return;
        }

        $template = PromptTemplate::create([
            'product_id' => $this->product_id,
            'category_id' => $this->category_id,
            'style_id' => $this->style_id,
            'layout_id' => $this->layout_id,
            'body' => $this->body,
            'version' => 1,
        ]);

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_prompt_template',
            target: $template,
            payload: [
                'event' => 'created',
                'version' => 1,
                'product_id' => $template->product_id,
                'category_id' => $template->category_id,
                'style_id' => $template->style_id,
                'layout_id' => $template->layout_id,
            ],
        );

        $this->redirect(route('admin.prompt-templates.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.prompt-templates.create', [
            'products' => Product::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'styles' => Style::orderBy('name')->get(),
            'layouts' => Layout::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Create Prompt Template'),
        ]);
    }
}

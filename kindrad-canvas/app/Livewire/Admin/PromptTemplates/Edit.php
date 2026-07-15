<?php

namespace App\Livewire\Admin\PromptTemplates;

use App\Models\Category;
use App\Models\Layout;
use App\Models\Product;
use App\Models\PromptTemplate;
use App\Models\Style;
use App\Services\AuditLogger;
use Livewire\Component;

class Edit extends Component
{
    public PromptTemplate $templateModel;

    public ?int $product_id = null;

    public ?int $category_id = null;

    public ?int $style_id = null;

    public ?int $layout_id = null;

    public string $body = '';

    public int $version = 0;

    public function mount(int|PromptTemplate $template): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);

        $model = $template instanceof PromptTemplate ? $template : PromptTemplate::findOrFail($template);
        $this->templateModel = $model;
        $this->product_id = $model->product_id;
        $this->category_id = $model->category_id;
        $this->style_id = $model->style_id;
        $this->layout_id = $model->layout_id;
        $this->body = $model->body;
        $this->version = $model->version;
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

        $bodyBefore = $this->templateModel->body;
        $versionBefore = $this->templateModel->version;

        $this->templateModel->update([
            'product_id' => $this->product_id,
            'category_id' => $this->category_id,
            'style_id' => $this->style_id,
            'layout_id' => $this->layout_id,
            'body' => $this->body,
            'version' => $versionBefore + 1,
        ]);

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_prompt_template',
            target: $this->templateModel,
            payload: [
                'event' => 'updated',
                'version_before' => $versionBefore,
                'version_after' => $this->templateModel->version,
                'body_changed' => $bodyBefore !== $this->templateModel->body,
            ],
        );

        $this->redirect(route('admin.prompt-templates.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.prompt-templates.edit', [
            'products' => Product::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'styles' => Style::orderBy('name')->get(),
            'layouts' => Layout::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Edit Prompt Template'),
        ]);
    }
}

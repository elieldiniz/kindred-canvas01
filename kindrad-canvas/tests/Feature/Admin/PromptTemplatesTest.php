<?php

use App\Livewire\Admin\PromptTemplates\Create;
use App\Livewire\Admin\PromptTemplates\Edit;
use App\Livewire\Admin\PromptTemplates\Index;
use App\Models\Category;
use App\Models\Layout;
use App\Models\Product;
use App\Models\PromptTemplate;
use App\Models\Style;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->product = Product::factory()->create();
    $this->category = Category::factory()->create(['product_id' => $this->product->id]);
    $this->style = Style::factory()->create();
    $this->layout = Layout::factory()->create();
});

it('redirects guests to login', function (): void {
    $this->get(route('admin.prompt-templates.index'))
        ->assertRedirect(route('login'));
});

it('rejects non-admin users', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.prompt-templates.index'))
        ->assertForbidden();
});

it('lists all prompt templates', function (): void {
    $template = PromptTemplate::factory()
        ->forTuple($this->product->id, $this->category->id, $this->style->id, $this->layout->id)
        ->create();

    $this->actingAs($this->admin)->get(route('admin.prompt-templates.index'))
        ->assertOk()
        ->assertSee($this->product->name)
        ->assertSee('admin-prompt-templates-index');
});

it('shows create form', function (): void {
    $this->actingAs($this->admin)->get(route('admin.prompt-templates.create'))
        ->assertOk()
        ->assertSee('admin-prompt-template-create');
});

it('creates a new prompt template', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('product_id', $this->product->id)
        ->set('category_id', $this->category->id)
        ->set('style_id', $this->style->id)
        ->set('layout_id', $this->layout->id)
        ->set('body', '{{name}} in {{theme}}')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('prompt_templates', [
        'product_id' => $this->product->id,
        'category_id' => $this->category->id,
        'style_id' => $this->style->id,
        'layout_id' => $this->layout->id,
        'body' => '{{name}} in {{theme}}',
        'version' => 1,
    ]);
});

it('validates required fields on create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('body', '')
        ->call('save')
        ->assertHasErrors(['product_id', 'category_id', 'style_id', 'layout_id', 'body']);
});

it('refuses to create a duplicate 4-tuple', function (): void {
    PromptTemplate::factory()
        ->forTuple($this->product->id, $this->category->id, $this->style->id, $this->layout->id)
        ->create();

    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('product_id', $this->product->id)
        ->set('category_id', $this->category->id)
        ->set('style_id', $this->style->id)
        ->set('layout_id', $this->layout->id)
        ->set('body', 'duplicate')
        ->call('save');

    $count = PromptTemplate::query()
        ->where('product_id', $this->product->id)
        ->where('category_id', $this->category->id)
        ->where('style_id', $this->style->id)
        ->where('layout_id', $this->layout->id)
        ->count();
    expect($count)->toBe(1);
});

it('shows edit form', function (): void {
    $template = PromptTemplate::factory()
        ->forTuple($this->product->id, $this->category->id, $this->style->id, $this->layout->id)
        ->create(['body' => 'original body']);

    $this->actingAs($this->admin)->get(route('admin.prompt-templates.edit', $template))
        ->assertOk()
        ->assertSee('admin-prompt-template-edit')
        ->assertSee('original body');
});

it('updates a prompt template and bumps version', function (): void {
    $template = PromptTemplate::factory()
        ->forTuple($this->product->id, $this->category->id, $this->style->id, $this->layout->id)
        ->create(['body' => 'original', 'version' => 5]);

    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['template' => $template->id])
        ->set('body', 'updated body')
        ->call('save')
        ->assertHasNoErrors();

    $template->refresh();
    expect($template->body)->toBe('updated body');
    expect($template->version)->toBe(6);
});

it('bumps version on every save', function (): void {
    $template = PromptTemplate::factory()
        ->forTuple($this->product->id, $this->category->id, $this->style->id, $this->layout->id)
        ->create(['body' => 'first', 'version' => 1]);

    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['template' => $template->id])
        ->set('body', 'second')
        ->call('save');

    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['template' => $template->id])
        ->set('body', 'third')
        ->call('save');

    $template->refresh();
    expect($template->body)->toBe('third');
    expect($template->version)->toBe(3);
});

it('deletes a prompt template via modal', function (): void {
    $template = PromptTemplate::factory()
        ->forTuple($this->product->id, $this->category->id, $this->style->id, $this->layout->id)
        ->create();

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('confirmDelete', $template->id)
        ->assertSet('confirmDelete', true)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('prompt_templates', ['id' => $template->id]);
});

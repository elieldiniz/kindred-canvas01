<?php

use App\Livewire\Admin\Styles\Create;
use App\Livewire\Admin\Styles\Edit;
use App\Livewire\Admin\Styles\Index;
use App\Models\Category;
use App\Models\Style;
use App\Models\StyleStatus;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    StyleStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);
});

it('redirects guests to login', function (): void {
    $this->get(route('admin.styles.index'))
        ->assertRedirect(route('login'));
});

it('rejects non-admin users', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.styles.index'))
        ->assertForbidden();
});

it('lists all styles', function (): void {
    $style = Style::factory()->create(['name' => 'Watercolor']);

    $this->actingAs($this->admin)->get(route('admin.styles.index'))
        ->assertOk()
        ->assertSee($style->name)
        ->assertSee('admin-styles-index');
});

it('shows create form', function (): void {
    $this->actingAs($this->admin)->get(route('admin.styles.create'))
        ->assertOk()
        ->assertSee('admin-style-create');
});

it('creates a new style with category associations', function (): void {
    $category = Category::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('name', 'Watercolor')
        ->set('slug', 'watercolor')
        ->set('prompt_fragment', 'soft watercolor with gentle washes')
        ->set('status_id', StyleStatus::where('slug', 'active')->first()->id)
        ->set('selectedCategories', [$category->id])
        ->call('save')
        ->assertHasNoErrors();

    $style = Style::where('slug', 'watercolor')->first();
    expect($style)->not->toBeNull();
    expect($style->categories)->toHaveCount(1);
    expect($style->categories->first()->id)->toBe($category->id);
});

it('validates required fields on create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('name', '')
        ->set('slug', '')
        ->call('save')
        ->assertHasErrors(['name', 'slug', 'status_id']);
});

it('validates unique slug on create', function (): void {
    $existing = Style::factory()->create(['slug' => 'watercolor']);

    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('name', 'Watercolor')
        ->set('slug', 'watercolor')
        ->set('status_id', StyleStatus::where('slug', 'active')->first()->id)
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('shows edit form', function (): void {
    $style = Style::factory()->create(['name' => 'Watercolor']);

    $this->actingAs($this->admin)->get(route('admin.styles.edit', $style))
        ->assertOk()
        ->assertSee('admin-style-edit')
        ->assertSee($style->name);
});

it('updates a style including prompt_fragment', function (): void {
    $style = Style::factory()->create(['prompt_fragment' => 'old fragment']);

    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['style' => $style->id])
        ->set('prompt_fragment', 'new fragment')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('styles', [
        'id' => $style->id,
        'prompt_fragment' => 'new fragment',
    ]);
});

it('syncs category associations on update', function (): void {
    $style = Style::factory()->create();
    $cat1 = Category::factory()->create();
    $cat2 = Category::factory()->create();
    $style->categories()->attach($cat1->id);

    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['style' => $style->id])
        ->set('selectedCategories', [$cat2->id])
        ->call('save')
        ->assertHasNoErrors();

    $style->refresh();
    expect($style->categories)->toHaveCount(1);
    expect($style->categories->first()->id)->toBe($cat2->id);
});

it('deletes a style via modal', function (): void {
    $style = Style::factory()->create(['name' => 'To Delete']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('confirmDelete', $style->id)
        ->assertSet('confirmDelete', true)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('styles', ['id' => $style->id]);
});

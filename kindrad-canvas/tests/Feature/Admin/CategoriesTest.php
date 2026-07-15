<?php

use App\Livewire\Admin\Categories\Create;
use App\Livewire\Admin\Categories\Edit;
use App\Livewire\Admin\Categories\Index;
use App\Models\Category;
use App\Models\CategoryStatus;
use App\Models\Product;
use App\Models\Style;
use App\Models\StyleStatus;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->product = Product::factory()->create();
    CategoryStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);
    StyleStatus::firstOrCreate(['slug' => 'active'], ['name' => 'Active']);
});

it('redirects guests to login', function (): void {
    $this->get(route('admin.categories.index'))
        ->assertRedirect(route('login'));
});

it('rejects non-admin users', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.categories.index'))
        ->assertForbidden();
});

it('lists all categories', function (): void {
    $category = Category::factory()->create(['name' => 'Birthday']);

    $this->actingAs($this->admin)->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee($category->name)
        ->assertSee('admin-categories-index');
});

it('shows create form', function (): void {
    $this->actingAs($this->admin)->get(route('admin.categories.create'))
        ->assertOk()
        ->assertSee('admin-category-create');
});

it('creates a new category', function (): void {
    $product = Product::factory()->create();
    $style = Style::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('product_id', $product->id)
        ->set('name', 'Birthday')
        ->set('slug', 'birthday')
        ->set('description', 'Birthday themed artwork')
        ->set('sort_order', 1)
        ->set('status_id', CategoryStatus::where('slug', 'active')->first()->id)
        ->set('selectedStyles', [$style->id])
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::where('slug', 'birthday')->first();
    expect($category)->not->toBeNull();
    expect($category->styles)->toHaveCount(1);
    expect($category->styles->first()->id)->toBe($style->id);
});

it('validates required fields on create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('name', '')
        ->set('slug', '')
        ->call('save')
        ->assertHasErrors(['name', 'slug', 'product_id', 'status_id']);
});

it('validates unique slug per product on create', function (): void {
    $existing = Category::factory()->create(['product_id' => $this->product->id, 'slug' => 'birthday']);

    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('product_id', $this->product->id)
        ->set('name', 'Duplicate')
        ->set('slug', 'birthday')
        ->set('status_id', CategoryStatus::where('slug', 'active')->first()->id)
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('shows edit form', function (): void {
    $category = Category::factory()->create(['name' => 'Birthday']);

    $this->actingAs($this->admin)->get(route('admin.categories.edit', $category))
        ->assertOk()
        ->assertSee('admin-category-edit')
        ->assertSee($category->name);
});

it('updates a category', function (): void {
    $category = Category::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['category' => $category->id])
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'New Name',
    ]);
});

it('syncs style associations on update', function (): void {
    $category = Category::factory()->create();
    $style1 = Style::factory()->create();
    $style2 = Style::factory()->create();
    $category->styles()->attach($style1->id);

    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['category' => $category->id])
        ->set('selectedStyles', [$style2->id])
        ->call('save')
        ->assertHasNoErrors();

    $category->refresh();
    expect($category->styles)->toHaveCount(1);
    expect($category->styles->first()->id)->toBe($style2->id);
});

it('deletes a category via modal', function (): void {
    $category = Category::factory()->create(['name' => 'To Delete']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('confirmDelete', $category->id)
        ->assertSet('confirmDelete', true)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

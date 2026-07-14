<?php

use App\Models\ColorMode;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->product = Product::factory()->create();
    ProductStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
    ColorMode::firstOrCreate(['name' => 'RGB', 'slug' => 'rgb']);
});

it('redirects guests to login', function (): void {
    $this->get(route('admin.products.index'))
        ->assertRedirect(route('login'));
});

it('rejects non-admin users', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.products.index'))
        ->assertForbidden();
});

it('lists all products', function (): void {
    $this->actingAs($this->admin)->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee($this->product->name)
        ->assertSee('admin-products-index');
});

it('shows create form', function (): void {
    $this->actingAs($this->admin)->get(route('admin.products.create'))
        ->assertOk()
        ->assertSee('admin-product-create');
});

it('creates a new product', function (): void {
    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\Products\Create::class)
        ->set('name', 'Tote Bag')
        ->set('slug', 'tote-bag')
        ->set('print_width_mm', 300)
        ->set('print_height_mm', 400)
        ->set('min_dpi', 300)
        ->set('safe_area_mm', 5.0)
        ->set('status_id', ProductStatus::where('slug', 'active')->first()->id)
        ->set('color_mode_id', ColorMode::where('slug', 'rgb')->first()->id)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', [
        'name' => 'Tote Bag',
        'slug' => 'tote-bag',
    ]);
});

it('validates required fields on create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\Products\Create::class)
        ->set('name', '')
        ->set('slug', '')
        ->call('save')
        ->assertHasErrors(['name', 'slug']);
});

it('validates unique slug on create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\Products\Create::class)
        ->set('name', 'Duplicate')
        ->set('slug', $this->product->slug)
        ->set('print_width_mm', 100)
        ->set('print_height_mm', 100)
        ->set('status_id', ProductStatus::where('slug', 'active')->first()->id)
        ->set('color_mode_id', ColorMode::where('slug', 'rgb')->first()->id)
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('shows edit form', function (): void {
    $this->actingAs($this->admin)->get(route('admin.products.edit', $this->product))
        ->assertOk()
        ->assertSee('admin-product-edit')
        ->assertSee($this->product->name);
});

it('updates a product', function (): void {
    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\Products\Edit::class, ['product' => $this->product->id])
        ->set('name', 'Updated Product')
        ->set('print_width_mm', 250)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('products', [
        'id' => $this->product->id,
        'name' => 'Updated Product',
        'print_width_mm' => '250.00',
    ]);
});

it('validates required fields on update', function (): void {
    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\Products\Edit::class, ['product' => $this->product->id])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('validates numeric fields on create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\Products\Create::class)
        ->set('name', 'Bad Product')
        ->set('slug', 'bad-product')
        ->set('print_width_mm', 'not-a-number')
        ->set('print_height_mm', 'not-a-number')
        ->set('status_id', ProductStatus::where('slug', 'active')->first()->id)
        ->set('color_mode_id', ColorMode::where('slug', 'rgb')->first()->id)
        ->call('save')
        ->assertHasErrors(['print_width_mm', 'print_height_mm']);
});

it('deletes a product via modal', function (): void {
    $product = Product::factory()->create(['name' => 'To Delete']);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\Products\Index::class)
        ->call('confirmDelete', $product->id)
        ->assertSet('confirmDelete', true)
        ->assertSet('deleteId', $product->id);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\Products\Index::class)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('products', ['id' => $product->id]);
});

it('prevents deleting a product that has categories', function (): void {
    $product = Product::factory()->create();
    \App\Models\Category::factory()->create(['product_id' => $product->id]);

    Livewire::actingAs($this->admin)
        ->test(\App\Livewire\Admin\Products\Index::class)
        ->call('confirmDelete', $product->id)
        ->call('delete')
        ->assertHasErrors(['deleteId']);
});

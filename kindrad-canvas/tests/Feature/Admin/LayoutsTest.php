<?php

use App\Livewire\Admin\Layouts\Create;
use App\Livewire\Admin\Layouts\Edit;
use App\Livewire\Admin\Layouts\Index;
use App\Models\Layout;
use App\Models\LayoutStatus;
use App\Models\Style;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->layout = Layout::factory()->create();
    LayoutStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
});

it('redirects guests to login', function (): void {
    $this->get(route('admin.layouts.index'))
        ->assertRedirect(route('login'));
});

it('rejects non-admin users', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.layouts.index'))
        ->assertForbidden();
});

it('lists all layouts', function (): void {
    $this->actingAs($this->admin)->get(route('admin.layouts.index'))
        ->assertOk()
        ->assertSee($this->layout->name)
        ->assertSee('admin-layouts-index');
});

it('shows create form', function (): void {
    $this->actingAs($this->admin)->get(route('admin.layouts.create'))
        ->assertOk()
        ->assertSee('admin-layout-create');
});

it('creates a new layout', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('name', 'Border Wrap')
        ->set('slug', 'border-wrap')
        ->set('status_id', LayoutStatus::where('slug', 'active')->first()->id)
        ->set('proportion_ratio', '16:9')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('layouts', [
        'name' => 'Border Wrap',
        'slug' => 'border-wrap',
    ]);
});

it('creates a layout with safe area overlay JSON', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('name', 'Full Bleed')
        ->set('slug', 'full-bleed')
        ->set('status_id', LayoutStatus::where('slug', 'active')->first()->id)
        ->set('proportion_ratio', '3:4')
        ->set('safe_area_overlay', '{"top_mm": 10, "bottom_mm": 10, "left_mm": 5, "right_mm": 5}')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('layouts', [
        'name' => 'Full Bleed',
        'slug' => 'full-bleed',
    ]);
});

it('validates required fields on create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('name', '')
        ->set('slug', '')
        ->call('save')
        ->assertHasErrors(['name', 'slug']);
});

it('validates unique slug on create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Create::class)
        ->set('name', 'Duplicate')
        ->set('slug', $this->layout->slug)
        ->set('status_id', LayoutStatus::where('slug', 'active')->first()->id)
        ->set('proportion_ratio', '1:1')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('shows edit form', function (): void {
    $this->actingAs($this->admin)->get(route('admin.layouts.edit', $this->layout))
        ->assertOk()
        ->assertSee('admin-layout-edit')
        ->assertSee($this->layout->name);
});

it('updates a layout', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['layout' => $this->layout->id])
        ->set('name', 'Updated Layout')
        ->set('proportion_ratio', '2:3')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('layouts', [
        'id' => $this->layout->id,
        'name' => 'Updated Layout',
    ]);
});

it('validates required fields on update', function (): void {
    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['layout' => $this->layout->id])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('syncs style associations on edit', function (): void {
    $style1 = Style::factory()->create();
    $style2 = Style::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Edit::class, ['layout' => $this->layout->id])
        ->set('selectedStyles', [$style1->id, $style2->id])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('style_layouts', [
        'layout_id' => $this->layout->id,
        'style_id' => $style1->id,
    ]);
    $this->assertDatabaseHas('style_layouts', [
        'layout_id' => $this->layout->id,
        'style_id' => $style2->id,
    ]);
});

it('deletes a layout via modal', function (): void {
    $layout = Layout::factory()->create(['name' => 'To Delete']);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('confirmDelete', $layout->id)
        ->assertSet('confirmDelete', true)
        ->assertSet('deleteId', $layout->id)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('layouts', ['id' => $layout->id]);
});

it('prevents deleting a layout that has styles', function (): void {
    $layout = Layout::factory()->create();
    $style = Style::factory()->create();
    $layout->styles()->attach($style);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->call('confirmDelete', $layout->id)
        ->call('delete')
        ->assertHasErrors(['deleteId']);
});

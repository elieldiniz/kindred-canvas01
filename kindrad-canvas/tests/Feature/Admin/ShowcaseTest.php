<?php

use App\Livewire\Admin\Showcase\Index as AdminShowcase;
use App\Models\ShowcaseItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake(config('generation.disk'));
});

it('prevents non-admins from accessing the showcase admin', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('admin.showcase.index'))
        ->assertForbidden();
});

it('renders the admin showcase index when the user is admin', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get(route('admin.showcase.index'))
        ->assertOk()
        ->assertSee('data-test="admin-showcase-index"', false)
        ->assertSee('data-test="admin-showcase-upload"', false);
});

it('uploads a valid image and persists it to storage and the database', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $file = UploadedFile::fake()->image('artwork.png', 800, 1200);

    Livewire::actingAs($admin)
        ->test(AdminShowcase::class)
        ->set('newTitle', 'Cozy winter mug')
        ->set('newImage', $file)
        ->call('upload')
        ->assertHasNoErrors();

    $items = ShowcaseItem::all();
    expect($items)->toHaveCount(1)
        ->and($items[0]->title)->toBe('Cozy winter mug')
        ->and($items[0]->is_active)->toBeTrue()
        ->and($items[0]->sort_order)->toBe(10)
        ->and($items[0]->image_path)->toStartWith('showcase/')
        ->and(pathinfo($items[0]->image_path, PATHINFO_EXTENSION))->toBe('png');

    Storage::disk(config('generation.disk'))->assertExists($items[0]->image_path);
});

it('rejects invalid uploads by mime type and size', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(AdminShowcase::class)
        ->set('newTitle', 'Bad')
        ->set('newImage', UploadedFile::fake()->create('doc.pdf', 1))
        ->call('upload')
        ->assertHasErrors(['newImage']);

    expect(ShowcaseItem::count())->toBe(0);
});

it('toggles is_active and rotates sort_order on move', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $a = ShowcaseItem::factory()->sorted(10)->create();
    $b = ShowcaseItem::factory()->sorted(20)->create();
    $c = ShowcaseItem::factory()->sorted(30)->create();

    Livewire::actingAs($admin)
        ->test(AdminShowcase::class)
        ->call('toggleActive', $a->id)
        ->call('move', $c->id, 'up');

    $a->refresh();
    $b->refresh();
    $c->refresh();

    expect($a->is_active)->toBeFalse()
        ->and($c->sort_order)->toBe(20)
        ->and($b->sort_order)->toBe(30);
});

it('deletes the item row and the file from storage', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $item = ShowcaseItem::factory()->create();
    Storage::disk(config('generation.disk'))->put($item->image_path, 'binary');

    Livewire::actingAs($admin)
        ->test(AdminShowcase::class)
        ->call('delete', $item->id)
        ->assertHasNoErrors();

    expect(ShowcaseItem::find($item->id))->toBeNull();
    Storage::disk(config('generation.disk'))->assertMissing($item->image_path);
});

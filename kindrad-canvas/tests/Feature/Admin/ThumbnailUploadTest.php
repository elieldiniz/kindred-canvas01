<?php

use App\Livewire\Admin\Categories\Create as CategoryCreate;
use App\Livewire\Admin\Categories\Edit as CategoryEdit;
use App\Livewire\Admin\Layouts\Create as LayoutCreate;
use App\Livewire\Admin\Layouts\Edit as LayoutEdit;
use App\Livewire\Admin\Styles\Create as StyleCreate;
use App\Livewire\Admin\Styles\Edit as StyleEdit;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\CategoryStatus;
use App\Models\Layout;
use App\Models\LayoutStatus;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\Style;
use App\Models\StyleStatus;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    ProductStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
    CategoryStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
    StyleStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
    LayoutStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
    Storage::fake(config('filesystems.default'));
});

function fakeImage(string $name = 'thumb.jpg', int $sizeKb = 100): UploadedFile
{
    return UploadedFile::fake()->image($name)->size($sizeKb);
}

it('creates a category with thumbnail upload', function (): void {
    $image = fakeImage('cat.jpg');

    Livewire::actingAs($this->admin)
        ->test(CategoryCreate::class)
        ->set('product_id', Product::factory()->create()->id)
        ->set('name', 'Birthday')
        ->set('slug', 'birthday')
        ->set('sort_order', 1)
        ->set('status_id', CategoryStatus::where('slug', 'active')->value('id'))
        ->set('thumbnail', $image)
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::where('slug', 'birthday')->firstOrFail();
    expect($category->thumbnail_path)->not->toBeNull();
    Storage::disk(config('filesystems.default'))->assertExists($category->thumbnail_path);
});

it('creates a category without thumbnail leaves thumbnail_path null', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CategoryCreate::class)
        ->set('product_id', Product::factory()->create()->id)
        ->set('name', 'Wedding')
        ->set('slug', 'wedding')
        ->set('sort_order', 1)
        ->set('status_id', CategoryStatus::where('slug', 'active')->value('id'))
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::where('slug', 'wedding')->firstOrFail();
    expect($category->thumbnail_path)->toBeNull();
});

it('rejects category thumbnail with invalid mime type', function (): void {
    $pdf = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

    Livewire::actingAs($this->admin)
        ->test(CategoryCreate::class)
        ->set('product_id', Product::factory()->create()->id)
        ->set('name', 'Pets')
        ->set('slug', 'pets')
        ->set('sort_order', 1)
        ->set('status_id', CategoryStatus::where('slug', 'active')->value('id'))
        ->set('thumbnail', $pdf)
        ->call('save')
        ->assertHasErrors(['thumbnail']);
});

it('rejects category thumbnail larger than 2MB', function (): void {
    $big = fakeImage('huge.jpg', 3000);

    Livewire::actingAs($this->admin)
        ->test(CategoryCreate::class)
        ->set('product_id', Product::factory()->create()->id)
        ->set('name', 'Family')
        ->set('slug', 'family')
        ->set('sort_order', 1)
        ->set('status_id', CategoryStatus::where('slug', 'active')->value('id'))
        ->set('thumbnail', $big)
        ->call('save')
        ->assertHasErrors(['thumbnail']);
});

it('replaces existing category thumbnail and deletes old file', function (): void {
    $old = fakeImage('old.jpg');
    $oldPath = $old->storeAs('catalog/categories', 'old-uuid.jpg', config('filesystems.default'));
    Storage::disk(config('filesystems.default'))->assertExists($oldPath);

    $category = Category::factory()->create(['thumbnail_path' => $oldPath]);

    $new = fakeImage('new.jpg');

    Livewire::actingAs($this->admin)
        ->test(CategoryEdit::class, ['category' => $category->id])
        ->set('thumbnail', $new)
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk(config('filesystems.default'))->assertMissing($oldPath);

    $fresh = $category->fresh();
    expect($fresh->thumbnail_path)->not->toBe($oldPath);
    Storage::disk(config('filesystems.default'))->assertExists($fresh->thumbnail_path);
});

it('removes existing category thumbnail when remove button clicked', function (): void {
    $image = fakeImage('to-remove.jpg');
    $path = $image->storeAs('catalog/categories', 'remove-uuid.jpg', config('filesystems.default'));
    Storage::disk(config('filesystems.default'))->assertExists($path);

    $category = Category::factory()->create(['thumbnail_path' => $path]);

    Livewire::actingAs($this->admin)
        ->test(CategoryEdit::class, ['category' => $category->id])
        ->call('removeExistingThumbnail')
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk(config('filesystems.default'))->assertMissing($path);
    expect($category->fresh()->thumbnail_path)->toBeNull();
});

it('creates a style with thumbnail upload', function (): void {
    $image = fakeImage('style.jpg');

    Livewire::actingAs($this->admin)
        ->test(StyleCreate::class)
        ->set('name', 'Watercolor')
        ->set('slug', 'watercolor')
        ->set('prompt_fragment', 'soft watercolor brushstrokes')
        ->set('status_id', StyleStatus::where('slug', 'active')->value('id'))
        ->set('thumbnail', $image)
        ->call('save')
        ->assertHasNoErrors();

    $style = Style::where('slug', 'watercolor')->firstOrFail();
    expect($style->thumbnail_path)->not->toBeNull();
    Storage::disk(config('filesystems.default'))->assertExists($style->thumbnail_path);
});

it('rejects style thumbnail with invalid mime', function (): void {
    $gif = UploadedFile::fake()->create('anim.gif', 50, 'image/gif');

    Livewire::actingAs($this->admin)
        ->test(StyleCreate::class)
        ->set('name', 'Cartoon')
        ->set('slug', 'cartoon')
        ->set('prompt_fragment', 'bold cartoon outlines')
        ->set('status_id', StyleStatus::where('slug', 'active')->value('id'))
        ->set('thumbnail', $gif)
        ->call('save')
        ->assertHasErrors(['thumbnail']);
});

it('replaces existing style thumbnail and deletes old file', function (): void {
    $old = fakeImage('old-style.jpg');
    $oldPath = $old->storeAs('catalog/styles', 'old-style-uuid.jpg', config('filesystems.default'));
    Storage::disk(config('filesystems.default'))->assertExists($oldPath);

    $style = Style::factory()->create(['thumbnail_path' => $oldPath]);

    $new = fakeImage('new-style.jpg');

    Livewire::actingAs($this->admin)
        ->test(StyleEdit::class, ['style' => $style->id])
        ->set('thumbnail', $new)
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk(config('filesystems.default'))->assertMissing($oldPath);
    $fresh = $style->fresh();
    expect($fresh->thumbnail_path)->not->toBe($oldPath);
    Storage::disk(config('filesystems.default'))->assertExists($fresh->thumbnail_path);
});

it('removes existing style thumbnail', function (): void {
    $image = fakeImage('style-remove.jpg');
    $path = $image->storeAs('catalog/styles', 'style-remove-uuid.jpg', config('filesystems.default'));
    Storage::disk(config('filesystems.default'))->assertExists($path);

    $style = Style::factory()->create(['thumbnail_path' => $path]);

    Livewire::actingAs($this->admin)
        ->test(StyleEdit::class, ['style' => $style->id])
        ->call('removeExistingThumbnail')
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk(config('filesystems.default'))->assertMissing($path);
    expect($style->fresh()->thumbnail_path)->toBeNull();
});

it('creates a layout with preview upload', function (): void {
    $image = fakeImage('layout.png');

    Livewire::actingAs($this->admin)
        ->test(LayoutCreate::class)
        ->set('name', 'Centered')
        ->set('slug', 'centered')
        ->set('status_id', LayoutStatus::where('slug', 'active')->value('id'))
        ->set('proportion_ratio', '1:1')
        ->set('preview', $image)
        ->call('save')
        ->assertHasNoErrors();

    $layout = Layout::where('slug', 'centered')->firstOrFail();
    expect($layout->preview_path)->not->toBeNull();
    Storage::disk(config('filesystems.default'))->assertExists($layout->preview_path);
});

it('rejects layout preview larger than 2MB', function (): void {
    $big = fakeImage('big.png', 3000);

    Livewire::actingAs($this->admin)
        ->test(LayoutCreate::class)
        ->set('name', 'Border Wrap')
        ->set('slug', 'border-wrap')
        ->set('status_id', LayoutStatus::where('slug', 'active')->value('id'))
        ->set('proportion_ratio', '16:9')
        ->set('preview', $big)
        ->call('save')
        ->assertHasErrors(['preview']);
});

it('replaces existing layout preview and deletes old file', function (): void {
    $old = fakeImage('old-layout.png');
    $oldPath = $old->storeAs('catalog/layouts', 'old-layout-uuid.png', config('filesystems.default'));
    Storage::disk(config('filesystems.default'))->assertExists($oldPath);

    $layout = Layout::factory()->create(['preview_path' => $oldPath]);

    $new = fakeImage('new-layout.png');

    Livewire::actingAs($this->admin)
        ->test(LayoutEdit::class, ['layout' => $layout->id])
        ->set('preview', $new)
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk(config('filesystems.default'))->assertMissing($oldPath);
    $fresh = $layout->fresh();
    expect($fresh->preview_path)->not->toBe($oldPath);
    Storage::disk(config('filesystems.default'))->assertExists($fresh->preview_path);
});

it('removes existing layout preview', function (): void {
    $image = fakeImage('layout-remove.png');
    $path = $image->storeAs('catalog/layouts', 'layout-remove-uuid.png', config('filesystems.default'));
    Storage::disk(config('filesystems.default'))->assertExists($path);

    $layout = Layout::factory()->create(['preview_path' => $path]);

    Livewire::actingAs($this->admin)
        ->test(LayoutEdit::class, ['layout' => $layout->id])
        ->call('removeExistingPreview')
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk(config('filesystems.default'))->assertMissing($path);
    expect($layout->fresh()->preview_path)->toBeNull();
});

it('category edit thumbnailUrl returns null when remove is flagged', function (): void {
    $category = Category::factory()->create(['thumbnail_path' => 'catalog/categories/keep.jpg']);
    Storage::disk(config('filesystems.default'))->put('catalog/categories/keep.jpg', 'fake');

    $component = Livewire::actingAs($this->admin)
        ->test(CategoryEdit::class, ['category' => $category->id])
        ->call('removeExistingThumbnail')
        ->assertSet('removeThumbnail', true);

    expect($component->instance()->thumbnailUrl())->toBeNull();
});

it('audit log records thumbnail upload on category create', function (): void {
    $image = fakeImage('audit-cat.jpg');

    Livewire::actingAs($this->admin)
        ->test(CategoryCreate::class)
        ->set('product_id', Product::factory()->create()->id)
        ->set('name', 'Kids')
        ->set('slug', 'kids')
        ->set('sort_order', 1)
        ->set('status_id', CategoryStatus::where('slug', 'active')->value('id'))
        ->set('thumbnail', $image)
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::where('slug', 'kids')->firstOrFail();
    $log = AuditLog::where('target_type', Category::class)
        ->where('target_id', $category->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->payload)->toMatchArray(['thumbnail_uploaded' => true]);
});

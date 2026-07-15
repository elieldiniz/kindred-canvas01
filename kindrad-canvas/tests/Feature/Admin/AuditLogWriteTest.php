<?php

use App\Livewire\Admin\Categories\Create as CategoryCreate;
use App\Livewire\Admin\Categories\Edit as CategoryEdit;
use App\Livewire\Admin\Categories\Index as CategoryIndex;
use App\Livewire\Admin\Layouts\Create as LayoutCreate;
use App\Livewire\Admin\Layouts\Edit as LayoutEdit;
use App\Livewire\Admin\Layouts\Index as LayoutIndex;
use App\Livewire\Admin\Products\Create as ProductCreate;
use App\Livewire\Admin\Products\Edit as ProductEdit;
use App\Livewire\Admin\Products\Index as ProductIndex;
use App\Livewire\Admin\PromptTemplates\Create as TemplateCreate;
use App\Livewire\Admin\PromptTemplates\Edit as TemplateEdit;
use App\Livewire\Admin\Styles\Create as StyleCreate;
use App\Livewire\Admin\Styles\Edit as StyleEdit;
use App\Livewire\Admin\Styles\Index as StyleIndex;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\AuditLog;
use App\Models\AuditLogAction;
use App\Models\Category;
use App\Models\CategoryStatus;
use App\Models\ColorMode;
use App\Models\CreditTransactionReason;
use App\Models\Layout;
use App\Models\LayoutStatus;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\PromptTemplate;
use App\Models\Style;
use App\Models\StyleStatus;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    ProductStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
    CategoryStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
    StyleStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
    LayoutStatus::firstOrCreate(['name' => 'Active', 'slug' => 'active']);
    ColorMode::firstOrCreate(['name' => 'RGB', 'slug' => 'rgb']);
    CreditTransactionReason::firstOrCreate(['slug' => 'admin_grant'], ['name' => 'Admin Grant', 'expected_sign' => '+']);
    AuditLogAction::firstOrCreate(['slug' => 'edit_product'], ['name' => 'Edit Product']);
    AuditLogAction::firstOrCreate(['slug' => 'edit_category'], ['name' => 'Edit Category']);
    AuditLogAction::firstOrCreate(['slug' => 'edit_style'], ['name' => 'Edit Style']);
    AuditLogAction::firstOrCreate(['slug' => 'edit_layout'], ['name' => 'Edit Layout']);
    AuditLogAction::firstOrCreate(['slug' => 'edit_prompt_template'], ['name' => 'Edit Prompt Template']);
    AuditLogAction::firstOrCreate(['slug' => 'toggle_admin'], ['name' => 'Toggle Admin']);
    AuditLogAction::firstOrCreate(['slug' => 'grant_credits'], ['name' => 'Grant Credits']);
});

it('records audit log on product create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ProductCreate::class)
        ->set('name', 'Tote Bag')
        ->set('slug', 'tote-bag')
        ->set('print_width_mm', 300)
        ->set('print_height_mm', 400)
        ->set('status_id', ProductStatus::where('slug', 'active')->value('id'))
        ->set('color_mode_id', ColorMode::where('slug', 'rgb')->value('id'))
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('slug', 'tote-bag')->firstOrFail();

    $this->assertDatabaseHas('audit_logs', [
        'actor_user_id' => $this->admin->id,
        'action_id' => AuditLogAction::where('slug', 'edit_product')->value('id'),
        'target_type' => Product::class,
        'target_id' => $product->id,
    ]);

    $log = AuditLog::where('target_type', Product::class)->where('target_id', $product->id)->first();
    expect($log->payload)->toMatchArray(['event' => 'created']);
});

it('records audit log on product edit with diff', function (): void {
    $product = Product::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->admin)
        ->test(ProductEdit::class, ['product' => $product->id])
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', Product::class)->where('target_id', $product->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload)->toHaveKey('changed');
    expect($log->payload['changed'])->toContain('name');
});

it('does not record audit log when product edit is a no-op', function (): void {
    $product = Product::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ProductEdit::class, ['product' => $product->id])
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('audit_logs', [
        'target_type' => Product::class,
        'target_id' => $product->id,
    ]);
});

it('records audit log on product delete', function (): void {
    $product = Product::factory()->create(['name' => 'To Delete']);

    Livewire::actingAs($this->admin)
        ->test(ProductIndex::class)
        ->call('confirmDelete', $product->id)
        ->call('delete')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', Product::class)->where('target_id', $product->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload)->toMatchArray(['event' => 'deleted']);
});

it('records audit log on category create with styles payload', function (): void {
    $style = Style::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(CategoryCreate::class)
        ->set('product_id', Product::factory()->create()->id)
        ->set('name', 'Birthday')
        ->set('slug', 'birthday')
        ->set('sort_order', 1)
        ->set('status_id', CategoryStatus::where('slug', 'active')->value('id'))
        ->set('selectedStyles', [$style->id])
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::where('slug', 'birthday')->firstOrFail();

    $log = AuditLog::where('target_type', Category::class)->where('target_id', $category->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload['styles'])->toBe([$style->id]);
});

it('records audit log on category edit when styles change', function (): void {
    $style1 = Style::factory()->create();
    $style2 = Style::factory()->create();
    $category = Category::factory()->create();
    $category->styles()->attach($style1);

    Livewire::actingAs($this->admin)
        ->test(CategoryEdit::class, ['category' => $category->id])
        ->set('selectedStyles', [$style2->id])
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', Category::class)->where('target_id', $category->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload)->toHaveKey('styles_before');
    expect($log->payload)->toHaveKey('styles_after');
});

it('records audit log on category delete', function (): void {
    $category = Category::factory()->create(['name' => 'To Delete']);

    Livewire::actingAs($this->admin)
        ->test(CategoryIndex::class)
        ->call('confirmDelete', $category->id)
        ->call('delete')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', Category::class)->where('target_id', $category->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload)->toMatchArray(['event' => 'deleted']);
});

it('records audit log on style create with categories payload', function (): void {
    $category = Category::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(StyleCreate::class)
        ->set('name', 'Watercolor')
        ->set('slug', 'watercolor')
        ->set('prompt_fragment', 'soft watercolor brush strokes')
        ->set('status_id', StyleStatus::where('slug', 'active')->value('id'))
        ->set('selectedCategories', [$category->id])
        ->call('save')
        ->assertHasNoErrors();

    $style = Style::where('slug', 'watercolor')->firstOrFail();

    $log = AuditLog::where('target_type', Style::class)->where('target_id', $style->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload['categories'])->toBe([$category->id]);
});

it('records audit log on style edit when categories change', function (): void {
    $cat1 = Category::factory()->create();
    $cat2 = Category::factory()->create();
    $style = Style::factory()->create();
    $style->categories()->attach($cat1);

    Livewire::actingAs($this->admin)
        ->test(StyleEdit::class, ['style' => $style->id])
        ->set('selectedCategories', [$cat2->id])
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', Style::class)->where('target_id', $style->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload)->toHaveKey('categories_before');
    expect($log->payload)->toHaveKey('categories_after');
});

it('records audit log on style delete', function (): void {
    $style = Style::factory()->create(['name' => 'To Delete']);

    Livewire::actingAs($this->admin)
        ->test(StyleIndex::class)
        ->call('confirmDelete', $style->id)
        ->call('delete')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', Style::class)->where('target_id', $style->id)->first();
    expect($log)->not->toBeNull();
});

it('records audit log on layout create', function (): void {
    Livewire::actingAs($this->admin)
        ->test(LayoutCreate::class)
        ->set('name', 'Centered')
        ->set('slug', 'centered')
        ->set('status_id', LayoutStatus::where('slug', 'active')->value('id'))
        ->set('proportion_ratio', '1:1')
        ->call('save')
        ->assertHasNoErrors();

    $layout = Layout::where('slug', 'centered')->firstOrFail();

    $log = AuditLog::where('target_type', Layout::class)->where('target_id', $layout->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload)->toMatchArray(['event' => 'created']);
});

it('records audit log on layout edit when styles change', function (): void {
    $style1 = Style::factory()->create();
    $style2 = Style::factory()->create();
    $layout = Layout::factory()->create();
    $layout->styles()->attach($style1);

    Livewire::actingAs($this->admin)
        ->test(LayoutEdit::class, ['layout' => $layout->id])
        ->set('selectedStyles', [$style2->id])
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', Layout::class)->where('target_id', $layout->id)->first();
    expect($log)->not->toBeNull();
});

it('records audit log on layout delete', function (): void {
    $layout = Layout::factory()->create(['name' => 'To Delete']);

    Livewire::actingAs($this->admin)
        ->test(LayoutIndex::class)
        ->call('confirmDelete', $layout->id)
        ->call('delete')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', Layout::class)->where('target_id', $layout->id)->first();
    expect($log)->not->toBeNull();
});

it('records audit log on prompt template create with version 1', function (): void {
    $product = Product::factory()->create();
    $category = Category::factory()->create(['product_id' => $product->id]);
    $style = Style::factory()->create();
    $layout = Layout::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TemplateCreate::class)
        ->set('product_id', $product->id)
        ->set('category_id', $category->id)
        ->set('style_id', $style->id)
        ->set('layout_id', $layout->id)
        ->set('body', 'A {{name}} painting')
        ->call('save')
        ->assertHasNoErrors();

    $template = PromptTemplate::where('product_id', $product->id)->firstOrFail();

    $log = AuditLog::where('target_type', PromptTemplate::class)->where('target_id', $template->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload['event'])->toBe('created');
    expect($log->payload['version'])->toBe(1);
});

it('records audit log on prompt template edit with version bump', function (): void {
    $template = PromptTemplate::factory()->create(['version' => 3]);

    Livewire::actingAs($this->admin)
        ->test(TemplateEdit::class, ['template' => $template->id])
        ->set('body', 'A {{name}} revised painting')
        ->call('save')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', PromptTemplate::class)->where('target_id', $template->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload['version_before'])->toBe(3);
    expect($log->payload['version_after'])->toBe(4);
    expect($log->payload['body_changed'])->toBeTrue();
});

it('records audit log on user toggle admin', function (): void {
    $target = User::factory()->create(['is_admin' => false]);

    Livewire::actingAs($this->admin)
        ->test(UsersIndex::class)
        ->call('toggleAdmin', $target->id)
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', User::class)->where('target_id', $target->id)->first();
    expect($log)->not->toBeNull();
    expect($log->payload['before'])->toBeFalse();
    expect($log->payload['after'])->toBeTrue();
});

it('does not record audit log when self-demotion is blocked', function (): void {
    Livewire::actingAs($this->admin)
        ->test(UsersIndex::class)
        ->call('toggleAdmin', $this->admin->id)
        ->assertHasErrors(['toggleAdmin']);

    $this->assertDatabaseMissing('audit_logs', [
        'actor_user_id' => $this->admin->id,
        'target_type' => User::class,
        'target_id' => $this->admin->id,
    ]);
});

it('records audit log on credit grant with amount + notes', function (): void {
    $target = User::factory()->create(['credit_balance' => 0]);

    Livewire::actingAs($this->admin)
        ->test(UsersIndex::class)
        ->set('grantUserId', $target->id)
        ->set('grantAmount', 50)
        ->set('grantNotes', 'Welcome bonus')
        ->call('grant')
        ->assertHasNoErrors();

    $log = AuditLog::where('target_type', User::class)
        ->where('target_id', $target->id)
        ->whereJsonContains('payload->notes', 'Welcome bonus')
        ->first();
    expect($log)->not->toBeNull();
    expect($log->payload['amount'])->toBe(50);
    expect($log->payload['balance_after'])->toBe(50);
});

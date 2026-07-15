<?php

use App\Livewire\Admin\AuditLog\Index;
use App\Models\AuditLog;
use App\Models\AuditLogAction;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->admin = User::factory()->admin()->create();
    $this->action = AuditLogAction::firstOrCreate(['name' => 'Edit Product', 'slug' => 'edit_product']);
});

it('redirects guests to login', function (): void {
    $this->get(route('admin.audit-log.index'))
        ->assertRedirect(route('login'));
});

it('rejects non-admin users', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.audit-log.index'))
        ->assertForbidden();
});

it('lists audit log entries', function (): void {
    AuditLog::create([
        'actor_user_id' => $this->admin->id,
        'action_id' => $this->action->id,
        'target_type' => Product::class,
        'target_id' => 1,
    ]);

    $this->actingAs($this->admin)->get(route('admin.audit-log.index'))
        ->assertOk()
        ->assertSee('admin-audit-log-index')
        ->assertSee('Edit Product');
});

it('filters by actor', function (): void {
    $otherAdmin = User::factory()->admin()->create();

    AuditLog::create([
        'actor_user_id' => $this->admin->id,
        'action_id' => $this->action->id,
        'target_type' => Product::class,
        'target_id' => 1,
    ]);
    AuditLog::create([
        'actor_user_id' => $otherAdmin->id,
        'action_id' => $this->action->id,
        'target_type' => Product::class,
        'target_id' => 2,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('filterActor', $this->admin->id)
        ->assertSee('admin-audit-row')
        ->assertSet('filterActor', (string) $this->admin->id);

    $this->assertDatabaseCount('audit_logs', 2);
    $this->assertEquals(1, AuditLog::where('actor_user_id', $this->admin->id)->count());
});

it('filters by action', function (): void {
    $deleteAction = AuditLogAction::firstOrCreate(['name' => 'Delete Product', 'slug' => 'delete_product']);

    AuditLog::create([
        'actor_user_id' => $this->admin->id,
        'action_id' => $this->action->id,
        'target_type' => Product::class,
        'target_id' => 1,
    ]);
    AuditLog::create([
        'actor_user_id' => $this->admin->id,
        'action_id' => $deleteAction->id,
        'target_type' => Product::class,
        'target_id' => 2,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Index::class)
        ->set('filterAction', $this->action->id)
        ->assertSee('admin-audit-row')
        ->assertSet('filterAction', (string) $this->action->id);

    $this->assertEquals(1, AuditLog::where('action_id', $this->action->id)->count());
});

it('shows empty state when no logs exist', function (): void {
    $this->actingAs($this->admin)->get(route('admin.audit-log.index'))
        ->assertOk()
        ->assertSee('admin-audit-empty');
});

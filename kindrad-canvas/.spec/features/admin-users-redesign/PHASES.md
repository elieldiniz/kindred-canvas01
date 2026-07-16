# Phases: admin-users-redesign

Gerado por /plan a partir de PLAN.md — view executável para `./ralph.sh .spec/features/admin-users-redesign/PHASES.md`.

## Phase 1: Foundation — schema + model trait + audit action seed

Antes de implementar, leia:
1. `.spec/features/admin-users-redesign/SPEC.md` — requisitos RIGID RF-03 (slug do audit log) e RF-06 (SoftDeletes + coluna `deleted_at`)
2. `.spec/features/admin-users-redesign/PLAN.md` — Tasks T01, T02; dependências e riscos

- [ ] T01 — Migration: `is_suspended` + `deleted_at` + audit action seed
      Arquivos: `database/migrations/YYYYMMDDHHMMSS_add_is_suspended_and_soft_deletes_to_users_table.php` (novo), `database/migrations/YYYYMMDDHHMMSS_add_password_reset_by_admin_audit_action.php` (novo)
      Mudança: Migration 1 adiciona coluna `is_suspended` (boolean nullable, default false) e `deleted_at` (timestamp nullable) na tabela `users`, no mesmo estilo de `add_admin_and_credits_to_users_table.php`. Migration 2 faz upsert idempotente do slug `password_reset_by_admin` em `audit_log_actions`, no mesmo padrão de `add_edit_subscription_plan_audit_action.php`. NÃO rodar migrations (o planner não roda).
      Cobre: RF-03, RF-06
      Acceptance criteria: `php -l` em ambas as migrations não emite erros de sintaxe; o nome das migrations segue `YYYYMMDDHHMMSS_add_*`; `down()` reverte simetricamente; a migration 2 contém guarda `if (! $exists)` idêntica ao padrão do projeto.
      Testes: `tests/Feature/Admin/UsersTest.php` — adicionar `test_user_uses_soft_deletes_and_deleted_at_column_exists` e `test_password_reset_by_admin_audit_action_seeded`.
- [ ] T02 — User model: SoftDeletes trait + `is_suspended` cast
      Arquivos: `app/Models/User.php`
      Mudança: Importar `Illuminate\Database\Eloquent\SoftDeletes`; adicionar o trait na lista `use ...`; adicionar `'is_suspended' => 'boolean'` ao método `casts()`; expandir o PHPDoc `@property` com `is_suspended` e `deleted_at`. Preservar todos os outros traits (Billable, PasskeyAuthenticatable, TwoFactorAuthenticatable, Notifiable, HasFactory) na ordem existente.
      Cobre: RF-06 (suporta RF-02/RF-05)
      Acceptance criteria: `php -l app/Models/User.php` sem erros; trait `SoftDeletes` aparece exatamente uma vez na lista `use`; cast `'is_suspended' => 'boolean'` presente; PHPDoc `@property` contém ambas as novas propriedades com tipos corretos.
      Testes: cobertura indireta via `test_user_uses_soft_deletes_and_deleted_at_column_exists` (T01) e `test_soft_deleted_user_excluded_from_admin_index` (T05).

## Phase 2: Suspensão — Fortify block + UserAdminService + admin UI toggle

Antes de implementar, leia:
1. `.spec/features/admin-users-redesign/SPEC.md` — requisitos RIGID RF-01 (Fortify bloqueia login de suspenso) e RF-02 (toggle + audit log)
2. `.spec/features/admin-users-redesign/PLAN.md` — Task T03; dependência de T01/T02; risco de lockout global

- [ ] T03 — Fortify suspension block + UserAdminService.Suspend + admin UI toggle
      Arquivos: `app/Providers/FortifyServiceProvider.php` (alterado), `app/Services/Admin/UserAdminService.php` (novo), `app/Livewire/Admin/Users/Index.php` (alterado), `resources/views/livewire/admin/users/index.blade.php` (alterado)
      Mudança: Em `FortifyServiceProvider::boot()`, registrar `Fortify::authenticateUsing(...)` que retorna `null` quando o `User` resolvido tem `is_suspended === true`; manter lookup canônico do Fortify para os demais casos. Criar `App\Services\Admin\UserAdminService` com métodos públicos `suspend(User $target, User $actor): User` e `unsuspend(User $target, User $actor): User` que invertem `is_suspended` e chamam `AuditLogger::record` com slugs `user_suspended` / `user_unsuspended` (FLEXIBLE, mas fixos nesta implementação). Adicionar `toggleSuspension(int $userId, UserAdminService $svc)` em `Users\Index` (sem mexer nos botões Promote/Grant existentes). Adicionar botão **Suspend/Unsuspend** inline em cada linha da view, com `data-test="admin-user-toggle-suspension"`. Não bloquear self-suspend (consistente com o padrão self-toggle existente).
      Cobre: RF-01, RF-02
      Acceptance criteria: `php -l` em todos os arquivos sem erros; `Fortify::authenticateUsing(...)` está presente exatamente uma vez em `FortifyServiceProvider`; `UserAdminService::suspend` e `::unsuspend` ambos chamam `AuditLogger::record`; a view contém `data-test="admin-user-toggle-suspension"` no botão; os botões Promote/Grant inline permanecem inalterados nesta fase.
      Testes: `tests/Feature/Admin/UsersTest.php` — adicionar `test_suspended_user_cannot_log_in_via_fortify`, `test_non_suspended_user_can_log_in_via_fortify`, `test_admin_can_toggle_user_suspension`, `test_toggle_suspension_writes_audit_log_entry`.

## Phase 3: Settings modal — concentrate Promote/Demote, Grant, Suspend/Unsuspend, Reset password, Soft-delete

Antes de implementar, leia:
1. `.spec/features/admin-users-redesign/SPEC.md` — requisitos RIGID RF-02 (modal wiring), RF-03 (reset password + audit slug literal), RF-04 (grant credits), RF-05 (soft-delete do admin), RF-07 (sem impersonation/restore)
2. `.spec/features/admin-users-redesign/PLAN.md` — Task T04; refactor de view; preservação dos `data-test`

- [ ] T04 — Settings modal: Promote/Demote + Grant + Suspend/Unsuspend + Reset password + Soft-delete
      Arquivos: `resources/views/livewire/admin/users/partials/settings-modal.blade.php` (novo), `app/Livewire/Admin/Users/Index.php` (alterado), `app/Services/Admin/UserAdminService.php` (alterado, novos métodos), `resources/views/livewire/admin/users/index.blade.php` (alterado)
      Mudança: Adicionar em `UserAdminService`: `resetPassword(User $target, User $actor, ?string $generated = null): string` — gera `Str::random(32)`, persiste via cast `password => hashed`, chama `AuditLogger::record($actor, 'password_reset_by_admin', $target, ['target_user_id' => $target->id])`; retorna o plaintext para flash. `softDelete(User $target, User $actor): void` — `$target->delete()` (usa SoftDeletes) + `AuditLogger::record($actor, 'user_soft_deleted', $target)`. Em `Users\Index`: adicionar estado `showSettingsModal`, `settingsUserId`, `resetPasswordFor`; ações `openSettingsModal(int $userId)`, `closeSettingsModal()`, `promoteFromSettings`, `demoteFromSettings` (ambos protegem `targetId !== auth()->id()`), `grantFromSettings` (reusa `grantAmount`/`grantNotes` já existentes), `toggleSuspensionFromSettings`, `resetPasswordFromSettings`, `softDeleteFromSettings`. View: criar partial `partials/settings-modal.blade.php` com `<flux:modal wire:model="showSettingsModal">` contendo seções Promote/Demote, Grant, Suspend/Unsuspend, Reset password, Soft-delete (cada um com `data-test` próprio). Em `index.blade.php`: substituir os botões inline Promote/Grant/Suspend por um único botão **Settings** (`data-test="admin-user-settings-button"`); incluir `@include('livewire.admin.users.partials.settings-modal')`. NÃO criar rota/action de Restore; NÃO criar rota/action de impersonation.
      Cobre: RF-02, RF-03, RF-04, RF-05 (admin-side), RF-07
      Acceptance criteria: `php -l` em todos os arquivos sem erros; o partial `partials/settings-modal.blade.php` existe e é incluído via `@include`; nenhum botão Promote/Grant/Suspend inline permanece na view (apenas o Settings); nenhum route de impersonation ou restore é registrado em `routes/web.php` ou `routes/admin.php`; `UserAdminService::resetPassword` retorna uma string não-vazia e chama `AuditLogger::record` com slug literal `password_reset_by_admin`; `UserAdminService::softDelete` chama `$target->delete()`.
      Testes: `tests/Feature/Admin/UsersTest.php` — adicionar `test_admin_can_reset_user_password`, `test_password_reset_by_admin_writes_audit_log_with_slug`, estender `test_admin_can_grant_credits_to_user` e `test_grant_credits_increases_target_balance_by_amount` para o novo caminho via modal, adicionar `test_soft_deleted_user_excluded_from_admin_index`, adicionar `test_no_impersonation_or_restore_routes_are_registered`.

## Phase 4: Dashboard metric + test sweep

Antes de implementar, leia:
1. `.spec/features/admin-users-redesign/SPEC.md` — requisito RIGID RF-05 (métrica `soft-deleted` no dashboard)
2. `.spec/features/admin-users-redesign/PLAN.md` — Task T05; varredura final de testes

- [ ] T05 — Dashboard metric `soft-deleted` + test sweep + Pint
      Arquivos: `app/Livewire/Admin/Dashboard.php` (alterado), `resources/views/livewire/admin/dashboard.blade.php` (alterado), `tests/Feature/Admin/UsersTest.php` (alterado)
      Mudança: Em `app/Livewire/Admin/Dashboard.php`, adicionar método público `softDeletedCount(): int` retornando `User::onlyTrashed()->count()`. Em `resources/views/livewire/admin/dashboard.blade.php`, adicionar um tile de métrica com o mesmo padrão visual dos tiles existentes (`<flux:*>` ou wrapper equivalente já usado), label "Soft-deleted" e `data-test="dashboard-soft-deleted-count"`. Após o último arquivo PHP tocado: `vendor/bin/pint --dirty --format agent`. NÃO rodar migrations; rodar `php artisan test --compact --filter=UsersTest` e `php artisan test --compact --filter=Dashboard`.
      Cobre: RF-05 (dashboard half)
      Acceptance criteria: `php -l` em `Dashboard.php` sem erros; método `softDeletedCount` existe e retorna `User::onlyTrashed()->count()`; view contém `data-test="dashboard-soft-deleted-count"`; `php artisan test --compact --filter=UsersTest` passa 100%; `php artisan test --compact --filter=Dashboard` passa 100%; `vendor/bin/pint --dirty --format agent` reporta "No changes" (todos os arquivos já estão no estilo).
      Testes: `tests/Feature/Admin/UsersTest.php` — adicionar `test_dashboard_soft_deleted_metric_counts_trashed_users`.
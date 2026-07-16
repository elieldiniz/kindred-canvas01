# SPEC: admin-users-redesign

## Status: draft
## Tier: standard

## Metadata

| Field | Value |
|-------|-------|
| Source | developer description via /plan |
| Service | kindred-canvas (Laravel 13) |
| Architecture references | AGENTS.md (Laravel Boost guidelines), .agents/skills/laravel-best-practices, .agents/skills/pest-testing, .agents/skills/fortify-development |
| Verification anchors | `app/Services/CreditLedger.php` (verified), `app/Services/AuditLogger.php` (verified), `app/Providers/FortifyServiceProvider.php` (verified), `tests/Feature/Admin/UsersTest.php` (verified at 102 lines) |

## Context

The admin Users page currently has no soft-delete handling, no suspension mechanism that blocks new logins, no in-admin password reset for users who forgot theirs, and no credit grant UI tied to the existing `CreditLedger` service. This redesign adds four operator capabilities (suspend, password reset, credit grant, soft-delete) and one dashboard metric (soft-deleted count) while reusing the existing `CreditLedger::adminGrant` and `AuditLogger::record` services so the audit and ledger layers stay canonical. Suspension is a **new-login block** enforced in `FortifyServiceProvider` via `Fortify::authenticateUsing(...)` — existing sessions remain valid and admin write-paths may additionally guard on `is_suspended`.

## RIGID (Non-Negotiable)

### Functional Requirements

- **RF-01** [State-Driven]: While `users.is_suspended = true`, the `Fortify::authenticateUsing` closure registered in `app/Providers/FortifyServiceProvider.php` MUST return `null` so that the login attempt fails with the standard Fortify authentication error. Users already holding a valid session continue to be authenticated until that session ends.
  - AC: A user with `is_suspended = true` attempting to log in via `/login` receives an authentication failure response; a user with `is_suspended = false` (or `null`) logging in via the same form succeeds.

- **RF-02** [Event-Driven]: When an admin clicks the **Suspend / Unsuspend** toggle on a user row, the system MUST update `users.is_suspended` to the new boolean value and MUST record an audit log entry via `App\Services\AuditLogger::record(...)` with `actionSlug` set to a literal that identifies the suspension action (the exact slug is implementation-defined in `audit_log_actions` seed).
  - AC: After toggling, `users.is_suspended` reflects the new value in the database; `audit_log` contains one new row referencing the target user with the actor set to the authenticated admin.

- **RF-03** [Event-Driven]: When an admin submits the **Reset password** form for a target user, the system MUST hash a freshly generated password, persist it on the target `users` row, and MUST record an audit log entry via `App\Services\AuditLogger::record(...)` with `actor: $admin`, `actionSlug: 'password_reset_by_admin'` (literal seed value in `audit_log_actions`), `target: $user`, and a `payload` containing at minimum the target user id.
  - AC: After submission, `users.password` no longer equals the previous hash; `audit_log` contains exactly one new row whose `action_slug` equals `password_reset_by_admin` and whose `target_id` matches the target user.

- **RF-04** [Event-Driven]: When an admin submits the **Grant credits** form with a positive integer amount and a non-empty reason, the system MUST call `App\Services\CreditLedger::adminGrant($target, $amount, $actor, $notes)` with `$target` = the target user, `$actor = auth()->user()`, `$amount` = the submitted integer, and `$notes` = the submitted reason string. The function MUST persist a credit ledger entry that increases the target's available credits by `$amount`.
  - AC: A `CreditLedger::adminGrant(...)` call with the mapped arguments increases the target user's credit balance by exactly `$amount` and returns without throwing.

- **RF-05** [State-Driven]: When a target user is soft-deleted (i.e. `users.deleted_at` is set to a non-null timestamp), the admin Users index page MUST exclude that user from the default listing and the admin dashboard MUST include the count of such users in the `soft-deleted` metric tile. The metric MUST read `count(*) from users where deleted_at IS NOT NULL`.
  - AC: A user with `deleted_at IS NOT NULL` does not appear in the default admin Users index; the dashboard `soft-deleted` metric equals the SQL count of `users` rows where `deleted_at IS NOT NULL`.

- **RF-06** [Conditional]: If `App\Models\User` does not yet use the `SoftDeletes` trait and the `users` table has no `deleted_at` column, then this feature MUST add the `SoftDeletes` trait to `App\Models\User` and MUST ship a migration `add_soft_deletes_to_users_table` that adds a nullable `deleted_at` timestamp column to the `users` table.
  - AC: After `php artisan migrate`, the `users` table contains a nullable `deleted_at` column; `User::find($id)` followed by `$user->delete()` sets `deleted_at` and excludes the row from default queries; `User::withTrashed()->find($id)` still returns it.

- **RF-07** [Unwanted]: The implementation MUST NOT introduce impersonation (logging in as another user), MUST NOT implement a full audit-trail UI (only the canonical `audit_log` writes are required), MUST NOT implement a **Restore user** action, and MUST NOT implement a GDPR hard-delete flow. Soft-delete only.
  - AC: No route, Livewire action, controller method, or Blade view exists in the application that performs user impersonation, restore-from-trash, or hard-delete on the `users` table as part of this feature.

## FLEXIBLE (Implementation Suggestions)

- The exact `actionSlug` for suspend/unsuspend (e.g. `user_suspended`, `user_unsuspended`) and for credit grant (e.g. `credits_granted_by_admin`) is up to the implementer; only `password_reset_by_admin` is frozen by RF-03.
- Suspension write-path guards on admin actions (e.g. blocking a suspended target from being edited by another admin) are optional. If implemented, they belong in a policy or a Livewire action check, not in `Fortify`.
- The dashboard `soft-deleted` metric tile may share a component with other user-count tiles.
- The credit grant form may validate `$amount > 0` and `$reason` length with project-default limits.
- Toast/flash messaging after suspend, reset, or grant is a UX choice (Flux toast, session flash, etc.).
- Tests for RF-02..RF-05 may use `actingAs($admin)` with the `User` factory; suspension tests (RF-01) may use `Fortify::authenticateUsing(...)` plus a request to `/login`.

## Out of scope

- User impersonation ("log in as user X").
- Full audit-trail UI / filters / pagination (only writes to `audit_log` are in scope).
- Restore-from-trash (undeleting a soft-deleted user).
- GDPR hard-delete or anonymization.
- Email notification to the user when their password is reset by an admin (optional, not required).
- Bulk actions on the Users index (bulk suspend, bulk delete, etc.).
- Per-user role/permission editing (orthogonal feature).

## Open Questions

`[]`

## Acceptance Tests

| AC# | Test method (in `tests/Feature/Admin/UsersTest.php`) |
|-----|-----------------------------------------------------|
| RF-01 (login blocked) | `test_suspended_user_cannot_log_in_via_fortify` |
| RF-01 (login allowed) | `test_non_suspended_user_can_log_in_via_fortify` |
| RF-02 (suspend toggles flag) | `test_admin_can_toggle_user_suspension` |
| RF-02 (audit log row) | `test_toggle_suspension_writes_audit_log_entry` |
| RF-03 (password hash changes) | `test_admin_can_reset_user_password` |
| RF-03 (audit log slug) | `test_password_reset_by_admin_writes_audit_log_with_slug` |
| RF-04 (credit grant) | `test_admin_can_grant_credits_to_user` |
| RF-04 (ledger balance) | `test_grant_credits_increases_target_balance_by_amount` |
| RF-05 (index excludes trashed) | `test_soft_deleted_user_excluded_from_admin_index` |
| RF-05 (dashboard metric) | `test_dashboard_soft_deleted_metric_counts_trashed_users` |
| RF-06 (migration + trait) | `test_user_uses_soft_deletes_and_deleted_at_column_exists` |
| RF-07 (out-of-scope guard) | `test_no_impersonation_or_restore_routes_are_registered` |
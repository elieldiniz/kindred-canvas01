# Workspace notes for opencode

This file is a handoff so a fresh `opencode` session can pick up where the
Kindred Canvas work left off. Keep it updated.

## What exists today

- `kindrad-canvas/` is a Laravel 13 + Livewire 4 + Flux + Tailwind 4 application.
- `main` branch has 2 commits:
  - `52bb958` — Initial commit (Laravel starter + .spec + .opencode config + the
    wizard + foundation + Phase 6/7/8 work all bundled by `liddera` as the
    initial commit because the prior session never landed an intermediate
    commit).
  - `a764485` — `feat(projects): add project show surface, generation download,
    soft delete + 30-day purge` (7.5-light + 8.x delta).
- Worktree: `/home/eliel/workspace/projects/kindred-canvas/kindrad-canvas/`
- Spec refs live at the workspace root `.spec/` (NOT inside `kindrad-canvas/`):
  - `.spec/features/project-wizard/` — wizard slice
  - `.spec/features/project-wizard-resume/` — STATUS.md snapshot of where the wizard stopped
  - `.spec/init/` — overall project phases, design tokens, user stories, schema

## Test + lint snapshot (last verified `a764485`)

```
vendor/bin/pint --dirty --format agent   →  passed
php artisan test --compact               →  229 tests, 228 passed, 1 skipped
```

The single skipped test is `WizardPerformanceTest::picker_render_time_best_effort_*`
which is `->skip(env('CI'))` — a known CI-only flake on the perf-timing assertion.

## Real implementation status (drift-corrected 2026-07-14)

`.spec/init/project-phases.md` was previously marked `[x]` for many tasks that
were NOT implemented. Audit corrected: Phase 3.2 (Google OAuth) is missing,
Phase 4.1 has no credit_balance widget, Phase 4.2 (credits history) is missing,
Phase 5.1-5.8 (admin back-office) are mostly missing, Phase 7.4 (Reverb
broadcasting) was skipped per vertical-slice decision, Phase 7.5 is partial
(polling only, no Echo).

**Actually implemented:** Phase 1.1-1.6, 2.1-2.5, 3.1, 3.2, 3.3, 4.1, 4.2, 5.1, 6.1-6.4,
7.1-7.3, 8.1-8.3. **Partial:** 3.3, 7.5. **Missing:** 5.2-5.8, 7.4.

**Top-of-mind next steps** (from previous session): implement Phase 7.4
broadcasting, Phase 5 admin CRUD, Phase 3.2 OAuth, Phase 4.1/4.2 dashboard
widgets / credits history, or move on to Phase 9 deferred work.

## Where things live

| Concern | File / dir |
| --- | --- |
| Project Wizard parent | `app/Livewire/Projects/Wizard.php` |
| Wizard step children | `app/Livewire/Projects/Wizard/Steps/*` |
| Wizard tests | `tests/Feature/Projects/Wizard*Test.php` |
| Generation pipeline tests | `tests/Feature/Actions/Generation`, `tests/Feature/Jobs` |
| Credit ledger | `app/Services/CreditLedger.php` (single write path) |
| Generation providers | `app/Services/Generation/{OpenAI,Gemini,Replicate}Provider.php` |
| Prompt rendering | `app/Services/PromptAssembler.php` |
| Generation submit action | `app/Actions/Generation/SubmitGeneration.php` |
| Generation job | `app/Jobs/GenerateArtworkJob.php` |
| Project show page | `app/Livewire/Projects/Show.php` + `resources/views/livewire/projects/show.blade.php` |
| Download endpoint | `app/Http/Controllers/Generations/DownloadController.php` |
| Soft-delete command | `app/Console/Commands/Projects/PurgeDeletedProjects.php` |
| Policies | `app/Policies/{ProjectPolicy,GenerationPolicy}.php` |
| Admin middleware | `app/Http/Middleware/EnsureAdmin.php` (registered as `admin` alias in `bootstrap/app.php`) |
| Migrations | `database/migrations/2026_07_13_*` |
| Catalog seeder | `database/seeders/CatalogSeeder.php` |
| Signup grant | `app/Actions/Fortify/CreateNewUser.php` + `Registered` event → `CreditLedger::signupGrant` |
| README | `README.md` at workspace root |

## Default workdir for the next agent

Always `workdir: kindrad-canvas/` (or pass it explicitly when calling shells).

## Local-environment quirks (non-obvious)

1. **`phpunit.xml` uses sqlite + custom storage path.** `DB_CONNECTION=sqlite`,
   `DB_DATABASE=:memory:`, and `LARAVEL_STORAGE_PATH=/home/eliel/.local/laravel-storage-testing`.
   The custom storage path was added because `/storage/framework/testing/disks/`
   was owned by root from a previous session and broke `Storage::fake('s3')`.
   If the path is wiped, recreate it:
   ```bash
   mkdir -p /home/eliel/.local/laravel-storage-testing/{app/public,framework/cache/data,framework/sessions,framework/views,framework/testing,logs}
   chmod -R 755 /home/eliel/.local/laravel-storage-testing
   ```
2. **`App\Livewire\Projects\Wizard\Steps\Layout` and `App\Models\Layout` collide.**
   The wizard step child aliases the model as `LayoutModel`. Same trick for
   `Category`/`Style`. Don't try to `use App\Models\Layout;` inside a step
   child — import the alias instead.
3. **Identity on the existing commits** is `liddera <dev@lidderacont.com.br>`
   (came from global git config). Don't push without confirming identity
   with the user first; the previous session deliberately did **not** push.

## Where the open spec lives when work resumes

- `.spec/features/project-wizard/PHASES.md` (wizard slice, all 33 tasks `[x]`
  as of `a764485` — read for context but don't re-run).
- `.spec/features/project-wizard-resume/STATUS.md` (detailed status of the
  wizard slice including 14 follow-up tasks NEW-A..N).
- `.spec/init/project-phases.md` (overall plan). Phases complete: 1.1-1.6,
  2.1-2.5, 3.1, 3.3 (partial), 6.1-6.4, 7.1-7.3, 8.1-8.3. Phase 7.4 (Reverb
  broadcasting) was skipped per vertical-slice decision; Phase 5 (admin
  back-office), Phase 3.2 (Google OAuth), Phase 4.1/4.2 (dashboard widgets
  + credits history), Phase 7.5 (Echo subscription) are pending.

## What the user cares about next

Top of mind from the last conversation: implement the suggested "next steps"
in the README (Phase 7.4 broadcasting, admin CRUD, OAuth, dashboard widgets,
deferred Phase 9 work). Always ask the user before pushing anything.

## Conventions to preserve

- `php artisan make:*` for all scaffolds — never hand-write new Livewire
  components, jobs, policies, migrations, factories, seeders, commands, or
  controllers.
- Class-based Livewire (the project's `<x-layouts>` slot convention paired
  with class components, no SFCs).
- Server-side authorization on every Livewire action via `$this->authorize(...)`.
  `authorizeOrAbort()` is the canonical pre-action gate.
- `pint --dirty --format agent` after every PHP change. No inline comments.
- No git commit without explicit user request. No push without explicit
  user request (and identity confirmation).

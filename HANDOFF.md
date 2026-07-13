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
- Spec refs live in `kindrad-canvas/.spec/` (project-wizard slice) and the
  outer `.spec/` (overall project phases).

## Test + lint snapshot (last verified `a764485`)

```
vendor/bin/pint --dirty --format agent   →  passed
php artisan test --compact               →  194 tests, 193 passed, 1 skipped
```

The single skipped test is `WizardPerformanceTest::picker_render_time_best_effort_*`
which is `->skip(env('CI'))` — a known CI-only flake on the perf-timing assertion.

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
| Migrations | `database/migrations/2026_07_13_*` |
| Catalog seeder | `database/seeders/CatalogSeeder.php` |
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

- `kindrad-canvas/.spec/features/project-wizard/PHASES.md` (wizard slice,
  already executed — read for context but don't re-run).
- `.spec/init/project-phases.md` (overall plan). Phases complete: 1.0
  (Foundation) + 6 (Wizard 1-8) + 7.0/7.1/7.2/7.3/7.5-light + 8.1/8.2/8.3.
  Phase 7.4 (Reverb broadcasting) was skipped per vertical-slice decision.

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

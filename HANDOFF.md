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
  - `.spec/features/project-wizard-v2/` — single-page configurator spec
  - `.spec/features/project-wizard-resume/` — STATUS.md snapshot of where the wizard stopped
  - `.spec/init/` — overall project phases, design tokens, user stories, schema

## Test + lint snapshot (last verified end of session)

```
vendor/bin/pint --dirty --format agent   →  passed
php artisan test --compact               →  307 tests, 307 passed
```

No skipped tests.

## Real implementation status (drift-corrected end of session)

`.spec/init/project-phases.md` status after this session:

**Fully implemented:** Phase 1.1-1.6, 2.1-2.5, 3.1, 3.2 (OAuth), 3.3 (signup grant),
4.1 (dashboard), 4.2 (credits history), 5.1-5.8 (admin back-office complete),
6.1-6.4 (wizard → single-page configurator), 7.1-7.5 (retry button + **debit
idempotency**), 8.1-8.3.

**Missing / Skipped:** Phase 7.4 (Reverb broadcasting — skipped per vertical-slice
decision).

### Admin CRUD built in this session (Phase 5.2-5.8)

| Resource | Routes | Components | Tests |
| --- | --- | --- | --- |
| Products | `admin.products.{index,create,edit}` | `Livewire\Admin\Products\{Index,Create,Edit}` | 13 |
| Categories | `admin.categories.{index,create,edit}` | `Livewire\Admin\Categories\{Index,Create,Edit}` | 11 |
| Styles | `admin.styles.{index,create,edit}` | `Livewire\Admin\Styles\{Index,Create,Edit}` | 11 |
| Layouts | `admin.layouts.{index,create,edit}` | `Livewire\Admin\Layouts\{Index,Create,Edit}` | 14 |
| PromptTemplates | `admin.prompt-templates.{index,create,edit}` | `Livewire\Admin\PromptTemplates\{Index,Create,Edit}` | 11 |
| Users | `admin.users.index` (with grant-credits modal + toggle-admin) | `Livewire\Admin\Users\Index` | 8 |
| AuditLog | `admin.audit-log.index` (filterable by actor + action) | `Livewire\Admin\AuditLog\Index` | 6 |

Sidebar nav: Products / Categories / Styles / Layouts / Prompt templates / Users /
Audit log — all enabled in `resources/views/components/layouts/admin.blade.php`
with `data-test="admin-nav-*"` and `:current="request()->routeIs(...)"`.

**PromptTemplate edit bumps `version` on every save** (the 4-tuple is the unique key).

**Layouts CRUD** has `safe_area_overlay` as a JSON textarea editor (accepts JSON
string, decodes on save). The Edit form includes a style associations checkbox
grid using `style_layouts` pivot, same pattern as Categories/Styles.
`layoutModel` is used instead of `layout` to avoid the NestingComponents quirk.

**AuditLog viewer** is filterable by actor and action. Paginated 25/page.
Uses `AuditLog::with('actor', 'action', 'target')` eager loading.

**AuditLog writes (US-8.3)** — every admin mutation now writes a row via
`App\Services\AuditLogger::record($actor, $actionSlug, $target, $payload)`.
Coverage:
- Products / Categories / Styles / Layouts / PromptTemplates: create + edit (with before/after diff + pivot changes) + delete
- Users: toggle_admin (before/after `is_admin`), grant_credits (amount + notes + balance_after)

Edits skip the audit row when nothing changed (no-op save). The Edit
components read the "before" snapshot from `$model->fresh()` inside `save()`
instead of caching it in `mount()` — Livewire 4 drops private properties on
rehydration between `set()` and `call()`, so a `private $before` array was
always empty at save time. Tests: `tests/Feature/Admin/AuditLogWriteTest.php` (18 tests).

### Debit idempotency (US-8.2)

`CreditLedger::debit()` now checks for an existing `generation_debit` row
referencing the same `Generation` before debiting. If found, returns the
existing row instead of double-charging. Matches the existing pattern in
`refund()` and `signupGrant()`. This closes a correctness gap: a retried job
(`tries=3`) that re-runs `SubmitGeneration` would previously double-debit the
user. Tests: `tests/Feature/Services/CreditLedgerTest.php` (3 new: idempotent
for same generation, no double-charge after refund, idempotent under lock).

### Admin thumbnail uploads (US-7.2/7.3/7.4/7.5)

Categories / Styles / Layouts Create + Edit components all gained file
upload via `WithFileUploads` + shared `App\Livewire\Admin\Concerns\StoresThumbnail`
trait. Files are stored under `catalog/{entity}/{uuid}.{ext}` on the default
disk. Mime validation: `jpeg|png|webp`, max 2MB. Edit forms show current
thumbnail with a Remove button; replacing deletes the old S3/local file.

Products was skipped — the `products` table has no `thumbnail_path` column
by design (only print specs). Poses was skipped — no admin CRUD exists yet
for the Pose model (seeded only).

Tests: `tests/Feature/Admin/ThumbnailUploadTest.php` (16 tests covering
create with upload, replace, remove, mime/size validation, audit-log
integration, no-op cases).

### Schema cleanup — drop `projects.source_image_id`

`project_photos` (v2 multi-photo pivot) was the canonical source for user
uploads, but `projects.source_image_id` (v1 single-photo FK) was left
orphaned. Cleanup work:

- **Migration** `2026_07_15_000001_drop_source_image_id_from_projects_table.php` —
  drops the FK + column with a reversible `down()`.
- **Project model** — removed `'source_image_id'` from `$fillable` and the
  `sourceImage()` BelongsTo relation. Keeps `photos()` HasMany
  (`->ordered()` scope).
- **ProjectFactory** — removed `withSourceImage()` state.
- **`GenerateArtworkJob`** — was reading `$project?->sourceImage` (always
  NULL for v2 projects — a silent bug, photos never reached the AI). Now
  reads `$project?->photos()->orderBy('position')->with('sourceImage')->first()?->sourceImage`.
- **`PurgeDeletedProjects`** — was only deleting the v1 source_image file
  and orphans. Now iterates `$project->photos()->with('sourceImage')` and
  deletes each file + SourceImage row + ProjectPhoto row. Also respects
  the photo's own `disk` field (was hardcoded to `config('generation.disk')`).

Tests: 2 new in `GenerateArtworkJobTest` (passes project photo to provider,
handles no-photo case), 2 new in `PurgeDeletedProjectsTest` (multiple photos
in order, projects with no photos gracefully). The pre-existing "removes s3
files" test was migrated from `source_image_id` to `project_photos`.

**Users CRUD** uses `CreditLedger::adminGrant` to write a ledger row with
`reference_type=User::class`, `reference_id=actor->id`, and admin-supplied `notes`.
Self-demotion is blocked at the Livewire layer (`toggleAdmin` checks `auth()->id()`).

### Retry button on failed history rows (Phase 7.5)

The existing `Show::retry(int $generationId)` method now has a corresponding
UI affordance inside the history list. Each row uses a `<div>` wrapper with a
nested `<button>` that selects the row, plus a conditional retry pill on
failed rows with `wire:click.stop="retry({{ $generation->id }})"`.
Tests: `tests/Feature/Projects/ShowTest.php::retry_button_*` (2 tests).

## Where things live

| Concern | File / dir |
| --- | --- |
| Configurator (single-page wizard) | `app/Livewire/Projects/Configurator.php` + `app/Livewire/Projects/Configurator/Block*` |
| Project show page | `app/Livewire/Projects/Show.php` + `resources/views/livewire/projects/show.blade.php` |
| Generation pipeline tests | `tests/Feature/Actions/Generation`, `tests/Feature/Jobs` |
| Credit ledger | `app/Services/CreditLedger.php` (single write path) |
| Generation providers | `app/Services/Generation/{OpenAI,Gemini,Replicate}Provider.php` |
| Prompt rendering | `app/Services/PromptAssembler.php` |
| Generation submit action | `app/Actions/Generation/SubmitGeneration.php` |
| Generation job | `app/Jobs/GenerateArtworkJob.php` |
| Download endpoint | `app/Http/Controllers/Generations/DownloadController.php` |
| Soft-delete command | `app/Console/Commands/Projects/PurgeDeletedProjects.php` |
| Policies | `app/Policies/{ProjectPolicy,GenerationPolicy}.php` |
| Admin middleware | `app/Http/Middleware/EnsureAdmin.php` (registered as `admin` alias in `bootstrap/app.php`) |
| Admin components | `app/Livewire/Admin/{Dashboard,Products/*,Categories/*,Styles/*,Layouts/*,PromptTemplates/*,Users/*,AuditLog/*}` |
| Admin layout | `resources/views/components/layouts/admin.blade.php` |
| Admin views | `resources/views/livewire/admin/{dashboard,products/*,categories/*,styles/*,layouts/*,prompt-templates/*,users/*,audit-log/*}` |
| Admin tests | `tests/Feature/Admin/{AccessGateTest,ProductsTest,CategoriesTest,StylesTest,LayoutsTest,PromptTemplatesTest,UsersTest,AuditLogTest,AuditLogWriteTest,ThumbnailUploadTest}.php` |
| Audit logger | `app/Services/AuditLogger.php` |
| Thumbnail trait | `app/Livewire/Admin/Concerns/StoresThumbnail.php` |
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
2. ~~`App\Livewire\Projects\Wizard\Steps\Layout` and `App\Models\Layout` collide.~~
   No longer relevant — the legacy `Wizard` + `Steps/*` architecture was
   deleted in favor of `Configurator` + `Configurator/Block*`.
3. **Identity on the existing commits** is `liddera <dev@lidderacont.com.br>`
   (came from global git config). Don't push without confirming identity
   with the user first; the previous session deliberately did **not** push.
4. **Livewire 4 + route model binding quirk:** when an Edit component has a
   public property matching the route param (e.g. `public Product $product`),
   Livewire's `NestingComponents` will try to assign the param to the property
   during `Livewire::test([...])` calls, causing TypeError on `int → Product`.
   Workaround used in Phase 5: rename the property to `productModel` /
   `categoryModel` / `styleModel` / `templateModel` / `layoutModel` and accept
   `int|Model` in `mount()`. HTTP requests resolve the model via `SubstituteBindings`
   before `mount()` is called; `Livewire::test([...])` calls pass the raw
   value which is then resolved manually.
5. **Flux free icon set is limited.** Many Material Symbols / Heroicons are NOT
   in Flux free — use `<span class="material-symbols-outlined">name</span>` directly
   instead. Confirmed-available: `cube`, `tag`, `swatch`, `code-bracket-square`,
   `user-group`, `chart-pie`, `arrow-left`, `pencil`, `trash`, `plus`, `check`,
   `check-badge`, `shield-check`, `bolt`. NOT available: `shield` alone (use
   `shield-check` or `shield-exclamation`), `plus-circle` (use `plus` + circle
   background), `cog`, `cog-6-tooth`.
6. **`flux:table` doesn't respect custom column widths.** The codebase uses
   plain HTML `<table style="table-layout:fixed">` with `<colgroup>` widths
   instead. Credits history page and admin tables both follow this pattern.
7. **Photo uploads use `config('filesystems.default')`**, not a hardcoded disk.
   S3 is not configured locally so the default disk is `local` →
   `storage/app/private/`. This was fixed in Phase 6.4 — don't reintroduce
   hardcoded `Storage::disk('s3')`.
8. **Test DB is SQLite in-memory** (`phpunit.xml` overrides `DB_CONNECTION=sqlite`).
   All tests run against SQLite with `RefreshDatabase`. Production DB is
   PostgreSQL via Docker Sail — be aware of cross-DB differences (e.g. JSON
   casting, unique constraint error codes).

## Where the open spec lives when work resumes

- `.spec/features/project-wizard/PHASES.md` (wizard slice, all 33 tasks `[x]`
  as of `a764485` — read for context but don't re-run).
- `.spec/features/project-wizard-v2/PHASES.md` (single-page configurator spec;
  implemented end-to-end in a prior session).
- `.spec/features/project-wizard-resume/STATUS.md` (detailed status of the
  wizard slice including 14 follow-up tasks NEW-A..N).
- `.spec/init/project-phases.md` (overall plan). After this session:
  1.1-1.6, 2.1-2.5, 3.1-3.3, 4.1-4.2, 5.1-5.8, 6.1-6.4, 7.1-7.3, 7.5, 8.1-8.3
  are implemented. Only pending: Phase 7.4 (Reverb broadcasting — skipped).

## What the user cares about next

Top of mind from the last conversation: implement the suggested "next steps"
in the README (Phase 7.4 broadcasting, admin CRUD, OAuth, dashboard widgets,
deferred Phase 9 work). Always ask the user before pushing anything.

After this session: admin CRUD (5.2-5.8) is fully complete including Layouts
and Audit Log viewer. The only remaining gap is Phase 7.4 (Reverb broadcasting
— skipped per vertical-slice decision). Phase 9 (payments, mockups, multi-product)
is intentionally deferred.

**Cleanup done this session:** legacy `Wizard` + `Steps/*` Livewire
components and their 14 test files were deleted as dead code — they pointed
at the old multi-step wizard architecture that was replaced by the v2
`Configurator` + `Block*` single-page wizard. All references in HANDOFF.md
updated; test count went from 361 to 266 (95 dead tests removed).

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

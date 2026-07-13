# PLAN: project-wizard

<!-- inputs: SPEC.md@sha256:1f902d0de79c -->

## Overview

This plan decomposes the **project-wizard** feature (Phase 6 in `project-phases.md`, US-3.1–3.7 + US-6.1) into architecture-aware tasks that ship the user-facing 7-step personalization wizard. The implementation strategy is **Livewire 4 with a single parent + step-routed child components**, mounted behind a dedicated wizard layout (`layouts/wizard.blade.php`), gated by the `auth` middleware and `ProjectPolicy::view|update`. The wizard owns the entire draft `projects` row lifecycle: it is **created on `mount()`** at step 1 (mode), incrementally updated through steps 2–6 (category/style/layout/source image/inputs), and finalized on step 7 (review) where `submit()` is a seam stub to Phase 7's `SubmitGeneration`.

Phase ordering follows the SPEC and `project-phases.md` build order: **foundation first** (wizard shell + layouts), then each step component, then policy/authorization hardening. Every step component queries the catalog via **pivot-driven eager-loaded relationships** (`Category::styles`, `Style::layouts`) filtered by the prior selection. Source image uploads use Livewire 4's `WithFileUploads` trait with the `s3` disk and user-scoped keys. Validation and authorization run server-side on every action; no client trust.

This phase assumes Phase 1 (migrations + seeders), Phase 2.3 (`Project` model), and Phase 2.5 (`ProjectPolicy`) are merged. If any are missing at implementation time, tasks reference them as hard prerequisites and the build cannot proceed. Phase 5 (admin) and Phase 3 (auth grant) are **soft prerequisites** — the wizard will function without them in a development environment seeded by hand, but production-quality end-to-end coverage requires both.

## Architecture Decisions

- **Parent + step child Livewire components.** Single parent `App\Livewire\Projects\Wizard` holds shared state (`projectId`, `step`, `modeId`, `categoryId`, `styleId`, `layoutId`, `sourceImageId`, `inputs`); each step child (`App\Livewire\Projects\Wizard\Steps\*`) renders one screen. Child components receive props from parent (`<livewire:is>` `step` slot per `components.md` D.2). This matches `screens.md` S3 "Step Content" modularity without creating seven sibling top-level components.
- **Single draft `projects` row mutated across steps.** Created on `mount()` with `status_id = draft`, `product_id = mug`, all other FKs `NULL`, `inputs = {}`; each `select*` action writes one column. Avoids orphaned drafts and keeps REQ-N1 (no partial writes) enforceable per-action.
- **Wizard-only layout shell.** Dedicated `layouts/wizard.blade.php` (no sidebar — wizard topbar + sticky footer per `components.md` A.6). Reuses global Flux/Tailwind setup from `layouts/app.blade.php` for `<html class="dark">`, fonts, and custom CSS (`.glass-card`, `.selection-glow`, `.shimmer-effect`).
- **Server-side authorization via `ProjectPolicy`.** Every mutating action calls `$this->authorize('update', $project)` (REQ-09 / US-8.1). Mounting and reading state also re-authorize per REQ-N3. Guests get the standard Fortify `auth` redirect (REQ-10).
- **File upload via Livewire `WithFileUploads` + S3 disk.** Temp storage on `local` during the request; persist to `s3` on action (`source-images/{user_id}/{uuid}.{ext}`). Validation enforced server-side (`mimes:jpeg,png,webp` and `max:10240`) so a forged client cannot bypass REQ-N2.
- **Validation via Livewire `rules()`.** Per Boost guidelines, no FormRequests for Livewire actions; `rules()` on the parent returns rules keyed by input field; failures render `<flux:error>`. Source image uses the file-upload `rules()` validator on the action.
- **Inputs JSON persistence.** Step 6 binds `<flux:input>` fields via `wire:model.live` to the parent's `inputs` array; on `next()`, validation runs and the array is persisted to `projects.inputs` as JSON (REQ-07). Per-category validation rules default to fixed max lengths (name 80, phrase 240, theme 120, dedicatoria 500) per SPEC's suggested default until an `input_schema` column is added in a later phase.
- **Generate button is a seam stub.** Per SPEC, `submit()` either redirects to `projects.show` or is a no-op marker — it never performs generation. Phase 7.3's `SubmitGeneration` action will replace the seam. This keeps the wizard strictly bounded.
- **Pint + Pest on every change.** Every task ends with `vendor/bin/pint --dirty --format agent` and `php artisan test --compact --filter=Projects` per Boost guidelines.
- **No inline comments.** Per Boost rules + SPEC RIGID.
- **Generated scaffolds.** Every new file uses `php artisan make:livewire`, `php artisan make:policy`, `php artisan make:test --pest` per Boost guidelines.

## Dependencies (upstream phases)

- **Phase 1 (Database Foundation — REQUIRED)**: all 26 domain tables (`projects`, `source_images`, `categories`, `styles`, `layouts`, `category_styles`, `style_layouts`, `project_modes`, `project_statuses`, `category_statuses`, `style_statuses`, `layout_statuses`, `products`). The wizard cannot run without the `projects` and `source_images` tables existing.
- **Phase 1.6 (Catalog Seeder — REQUIRED)**: `CatalogSeeder` populating `mug` product, 6 categories, 5 styles, 4 layouts, pivot rows. Without seeded catalog data, every picker step shows the empty state (REQ-U8).
- **Phase 2.1 + 2.3 (Models — REQUIRED)**: `Project`, `SourceImage`, `Category`, `Style`, `Layout`, `ProjectMode`, `Product` Eloquent models with relationships and casts. The parent Livewire component resolves everything via Eloquent.
- **Phase 2.5 (ProjectPolicy — REQUIRED)**: `ProjectPolicy::view|update` must exist and be auto-discovered before any mutating action runs. This is REQ-09's hard prerequisite.
- **Phase 3 (Auth Flows — REQUIRED for full coverage)**: Fortify signup with credit grant so `auth()->user()->credit_balance > 0` test path is possible. Without it, the disabled-Generate button (REQ-08) cannot be exercised by a real user — only by a factory seed.
- **Phase 4.1 (Dashboard — soft prerequisite)**: the `New Project` CTA on the dashboard linking to `route('projects.new')`. The wizard works without it (route is directly addressable) but US-3.1's "one click from dashboard" experience requires it.
- **Phase 5 (Admin — soft prerequisite)**: catalog CRUD so REQ-03/04/05 status filters are exercisable; absent, picker empty states dominate tests.

If any **REQUIRED** dependency is missing at implementation time, this plan's tasks must pause until they are merged.

## Risk Register

- **[HIGH] Phase 1/2 dependencies may not be merged.** Verified at `kindrad-canvas/app/Models/` — only `User.php` exists; no `Project`, `Category`, `Style`, `Layout`, `SourceImage`. The `project-phases.md` mark Phase 1–5 as `[x]` but the filesystem contradicts that. **Mitigation:** Phase 1 of this plan (T01 wizard shell + `mount`) is the first implementation task; if `Project::create` fails, treat Phase 1 tasks as blocked and surface to the user. **Rollback:** N/A — these phases must exist; if not, they need their own planning cycle.
- **[HIGH] Source-image S3 disk may not be configured.** REQ-06 requires the `s3` disk driver and credentials. **Mitigation:** in tests, swap `Storage::fake('s3')` per Pest convention; verify `config/filesystems.php` has an `s3` disk before implementation. **Rollback:** the action throws a configuration exception — feature tests must catch this and fail with a clear message.
- **[MEDIUM] Step 6 input-validation rules conflict with US-3.6's "category-driven rules".** SPEC §"Open Questions" flags this as blocking: `categories` has no `input_schema` column. **Mitigation:** default to fixed max lengths (name 80 / phrase 240 / theme 120 / dedicatoria 500) and surface this assumption in feature tests; if a later phase adds `input_schema`, swap to dynamic rules.
- **[MEDIUM] `Replace` semantics for source_images are undefined per SPEC §"Open Questions".** **Mitigation:** implement per SPEC FLEXIBLE default — on Replace, create a new `source_images` row and update `projects.source_image_id`; leave the old row in DB (no FK cascade). A future nightly command (Phase 8+) can purge orphans.
- **[MEDIUM] Exit-wizard confirmation modal behaviour is undefined.** SPEC §"Open Questions" notes that `screens.md` S3.1 "Exit" is unclear. **Mitigation:** implement the wizard topbar `Exit` as a navigation link that routes to `dashboard` route *without* discarding the draft row (SPEC's suggested default). User can resume from `/projects/{id}` once Phase 8 ships that route.
- **[MEDIUM] Livewire 4 `WithFileUploads` may store temp files on `local` disk only.** Spec rules require persistence to `s3`. **Mitigation:** in the action, after validation passes, copy the temp upload to `s3` via `Storage::disk('s3')->putFileAs(...)` with a user-scoped UUID key, then create the `source_images` row. `local` temp file is auto-cleaned by Livewire.
- **[LOW] Step 1 mode selector reads `project_modes` lookup table.** This row must be seeded in Phase 1.2 / 1.6. **Mitigation:** the empty-state card (F.1) provides a graceful fallback; tests assert the slug filter is applied to `slug IN ('free','mug')` rather than assuming a non-empty result.
- **[LOW] Picker query latency (REQ-N4 — p95 < 300 ms, ≤ 4 queries per render).** Requires indexed `categories(product_id, status_id, sort_order)`, `category_styles(category_id)`, `style_layouts(style_id)`. **Mitigation:** DBML defines these indexes; feature test asserts query log count ≤ 4.

## Validation Criteria

The feature is complete when **all** binary checks pass:

1. `php artisan route:list --name=projects.new` shows `GET /projects/new` → `livewire.projects.wizard` with `auth` middleware.
2. `php artisan test --compact --filter=Projects` passes with at least one feature test per spec REQ (01, 02, 03, 04, 05, 06, 07, 08, 09, 10, 11).
3. `vendor/bin/pint --dirty --format agent` returns clean (no formatting drift).
4. `php artisan test --compact --filter=ProjectPolicy` confirms non-owner returns 403, admin returns 200 (REQ-09).
5. A feature test posts a synthetic 12 MiB file directly to the wizard upload action and receives 422 (REQ-N2).
6. A feature test asserts step 3 progress bar fill is between 28 % and 43 % when `step === 3` (REQ-U2).
7. A feature test mounts the wizard as user A while supplying `project_id` of user B's draft and asserts 403 (REQ-N3).
8. `php artisan db:seed` followed by a curl/feature test against the route produces visible category/style/layout tiles against the seeded catalog.
9. The wizard exits at `Generate` — `submit()` either redirects to `projects.show` or returns a no-op, and no `credit_transactions` row is written by the wizard.
10. The Exit button routes to `route('dashboard')` and leaves `projects` row intact (SPEC §"Open Questions" assumed default).

## Out of Scope

- **`Generate` action execution**: no `CreditLedger::debit`, no `GenerateArtworkJob` dispatch, no `generation` row creation. These are Phase 7 deliverables. The wizard's `submit()` is a seam stub.
- **Result views / history / download**: Phase 8. After step 7 the user is shown the Review screen only; clicking `Generate` does not transport them anywhere yet.
- **Admin catalog CRUD**: Phase 5. The wizard reads catalog data; it does not write to `categories`, `styles`, `layouts`, etc.
- **Multi-product wizard entry**: SPEC explicitly defers to Phase 9.3. Step 1 is a mode selector (Free vs Mug); `product_id` is hard-set to `mug` on `mount()`. No product picker.
- **Mockup generation, AI vision analysis, multi-mockup, payment integration** (Phase 9).
- **Source-image post-processing**: width/height inference, EXIF stripping, virus scanning — none implemented. Only MIME + size checks.
- **Free-form "describe your artwork" mode** without a Category (per `user-stories.md` Appendix — explicitly excluded from MVP).
- **Resume-draft flow**: Phase 8 will need a `/projects/{id}` resume route and a list of in-progress drafts. This SPEC scopes the wizard to the first draft; subsequent drafts / resume are out of scope.
- **Audit-log writes**: admin actions are audited (Phase 5 + 8.3). The wizard writes NO audit rows.
- **Internationalization (i18n)**: SPEC notes PT-BR may live in `lang/pt_BR.json` but is not required for MVP. All copy ships in English.

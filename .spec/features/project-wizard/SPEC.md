# SPEC: project-wizard

<!-- inputs: project-description.md user-stories.md database-schema.md project-phases.md design/tokens.md design/screens.md design/components.md kindrad-canvas/AGENTS.md -->

## Overview

The **project-wizard** feature is the user-facing flow that turns an authenticated Kindred Canvas user with a non-zero credit balance into the owner of a `draft` `projects` row that is ready for AI generation. The wizard is a 7-step guided sequence (mode → category → style → layout → source image → inputs → review) implemented as Livewire 4 components under a wizard-specific layout shell. It is the single entry point for new personalization projects and the only place where `projects.mode_id`, `category_id`, `style_id`, `layout_id`, `source_image_id`, and `inputs` are written prior to generation.

This SPEC scopes the **wizard slice only**: step navigation, picker logic, S3-backed source image upload, validation, persistence of the draft `projects` row, and `ProjectPolicy`-gated authorization on every step. It explicitly does **not** cover the `Generate` action (Phase 7 — `SubmitGeneration`, `CreditLedger::debit`, `GenerateArtworkJob`, broadcasting) or downstream result/history views (Phase 8). The wizard ends with the user on the Review step; pressing `Generate` is the seam where the wizard hands off to the Phase 7 generation pipeline.

The primary users are authenticated end users (per `User Types` in `user-stories.md`) creating a single draft project in under a minute. Administrators do not use this feature. Business users share the same flow in MVP.

## Context

### Bounded Contexts Touched

- **Identity & Credits** (read-only): `users.credit_balance` is consulted at the Review step to enable/disable the `Generate` button; no ledger writes happen here (those are Phase 7).
- **Personalization Catalog** (read-only): `categories`, `styles`, `layouts`, `category_styles`, `style_layouts`, `products` are queried to drive pickers. The wizard never mutates them.
- **Project Lifecycle** (read+write): a single `projects` row is created on step 1 (mode selection) and incrementally updated as the user advances. `source_images` is inserted at step 5.
- **Object Storage**: source-image uploads land on the `s3` disk under a user-scoped key.
- **Authorization**: every action method invokes `ProjectPolicy` (Phase 2.5) via `Gate::authorize` or `authorize('...', $project)`.

### Architecture Reference

The architecture rules come from `kindrad-canvas/AGENTS.md` (Laravel Boost guidelines). The SPEC must therefore require:

- **`php artisan make:` for new files** — Livewire components via `php artisan make:livewire`, policies via `php artisan make:policy ProjectPolicy --model=Project`, tests via `php artisan make:test --pest Projects/WizardStartTest` (concrete concrete test paths listed in `project-phases.md` Phase 6 acceptance criteria).
- **Server-side state** — Livewire actions validate and authorize as if they were HTTP requests (no client-only trust).
- **Pest feature tests** — every acceptance criterion gets a Pest feature test under `tests/Feature/Projects/`.
- **No code comments** unless explicitly requested.

### AS IS — Estado atual

The slice the wizard touches is **greenfield**: the only authenticated surface today is `Route::view('dashboard', 'dashboard')` (verified at `kindrad-canvas/routes/web.php:8`) backed by `resources/views/dashboard.blade.php`. There are no `Livewire/Projects` components, no `Project`/`Category`/`Style`/`Layout`/`SourceImage` models, no `category_styles` or `style_layouts` pivots, and no `ProjectPolicy`. Migrations only ship `users`, `cache`, `jobs`, `passkeys`, and 2FA columns (verified at `kindrad-canvas/database/migrations/`). The dashboard lacks a `New Project` CTA wired to the wizard.

```mermaid
flowchart LR
  User([Authenticated user]) -->|clicks "dashboard"| DashboardView["dashboard.blade.php<br/>(resources/views)"]
  DashboardView -->|no New Project CTA| Nothing[(no wizard route)]
```

_Caption: Hoje a única rota autenticada é `GET /dashboard` renderizada por uma view Blade estática. Não há rota `/projects/new`, nenhum componente Livewire de projeto, nenhum modelo de domínio além de `User`, e nenhum ponto de entrada para criação de projeto._

### TO BE — Estado proposto

```mermaid
flowchart LR
  User([Authenticated user]) -->|clicks New Project CTA| DashboardView["dashboard.blade.php<br/>(updated, S2.1)"]
  DashboardView -->|route('projects.new')| WizardRoute["GET /projects/new<br/>(web.php)"]
  WizardRoute -->|mount| WizardComponent["Livewire\\Projects\\Wizard<br/>(D.2 parent)"]
  WizardComponent -->|mode selected<br/>persist draft row| ProjectRow[("projects<br/>(status_id=draft, mode_id)")]
  WizardComponent -->|renders step 2..6| StepChild["Step child components<br/>(mode|category|style|layout|source|inputs|review)"]
  StepChild -->|reads| CategoryQuery["Category::query<br/>(product_id=mug, status=active)"]
  StepChild -->|reads| StyleQuery["Style via category_styles<br/>(filtered)"]
  StepChild -->|reads| LayoutQuery["Layout via style_layouts<br/>(filtered)"]
  StepChild -->|upload| S3[("S3 disk<br/>(source_images)")]
  StepChild -->|authorize| ProjectPolicy{{"ProjectPolicy<br/>(owner|admin)"}}
  ProjectPolicy -->|allow/deny| WizardComponent
```

_Caption: Após esta feature, o dashboard expõe um CTA `New Project` que resolve para `GET /projects/new`, onde o componente Livewire `Projects\Wizard` orquestra as 7 etapas, persiste `projects.mode_id`/`category_id`/`style_id`/`layout_id`/`source_image_id`/`inputs` no mesmo registro `draft`, e exige `ProjectPolicy` em toda mutação (RF-08). RF-01, RF-02, RF-03, RF-04, RF-05, RF-06, RF-07, RF-08._

## Scope

**In:**

- `GET /projects/new` route + Livewire wizard shell with mode selector as step 1.
- Steps 2–4: category, style, layout pickers with cascading filters via pivots.
- Step 5: source-image upload to S3 disk, validation (jpeg/png/webp, ≤ 10 MB), `source_images` row, Replace/Remove actions.
- Step 6: inputs form (`name`, `phrase`, `theme`, `dedicatoria`) with category-level validation.
- Step 7: review screen with Edit buttons per section and disabled `Generate` button when `users.credit_balance == 0`.
- Persistence of a single `projects` row created on step 1 and incrementally updated.
- `ProjectPolicy` enforcement on every Livewire action.
- Wizard-only layout shell (`layouts/wizard.blade.php`) with topbar, sticky footer, and progress indicator per S3.

**Out:**

- The actual `Generate` action, credit debit, job dispatch, and result streaming (Phase 7.3).
- Project Show page, history list, download streaming, and soft-delete (Phase 8).
- Admin CRUD for catalog entities (Phase 5).
- Multi-product wizard entry — only `mug` is exposed (deferred per Phase 9.3).
- Mockup generation, payment, and source-image vision analysis (Phase 9).
- Free-form "describe your artwork" mode without a Category (explicitly out per `user-stories.md` Appendix).

## RIGID (Non-Negotiable)

### Functional Requirements

- **REQ-01:** A single click on a `New Project` control exposed on the dashboard MUST land the authenticated user on `GET /projects/new` and create exactly one new `projects` row with `status_id` pointing to the `draft` lookup row, owned by the authenticated user.
  - **Rationale:** Anchors US-3.1; every downstream step writes into the same draft row.
  - **Acceptance:** After clicking the CTA, `projects` contains exactly one new row for `auth()->id()` with `status_id` resolving to `project_statuses.slug = 'draft'`.

- **REQ-02:** Step 1 of the wizard MUST be a mode selector that presents only the rows from `project_modes` (`free`, `mug`) and persists the chosen `mode_id` on the draft `projects` row before advancing.
  - **Rationale:** Mode influences prompt assembly (US-6.1) and per US-6.1 the mode becomes immutable once any generation completes; the wizard captures it first.
  - **Acceptance:** Selecting `mug` saves `projects.mode_id` referencing `project_modes.slug='mug'`; the user cannot advance without a selection; navigating away and returning to step 1 shows the saved selection highlighted.

- **REQ-03:** Step 2 (Category) MUST list only `categories` rows where `products.slug='mug'` AND `category_statuses.slug='active'`, each rendered with its `thumbnail_path` and `description`, and persist exactly one `category_id` on the draft project.
  - **Rationale:** US-3.2 and US-7.2 require that deactivating a product/category hides it from the wizard without touching historical projects; the filter must therefore consult `status_id`, not a soft-delete.
  - **Acceptance:** Categories with `status_id != active` (slug) are not displayed; inactive products are not displayed; selecting a category writes `projects.category_id`; the selection is recoverable on reload.

- **REQ-04:** Step 3 (Style) MUST list only `styles` rows associated with the chosen `category_id` via the `category_styles` pivot AND with `style_statuses.slug='active'`, and persist exactly one `style_id`.
  - **Rationale:** US-3.3 + US-7.3/7.4. Filter must use the pivot, not a denormalized column.
  - **Acceptance:** Styles not present in `category_styles` for the chosen category are not displayed; inactive styles are not displayed; selecting a style writes `projects.style_id`.

- **REQ-05:** Step 4 (Layout) MUST list only `layouts` rows associated with the chosen `style_id` via the `style_layouts` pivot AND with `layout_statuses.slug='active'`, and persist exactly one `layout_id`.
  - **Rationale:** US-3.4 + US-7.5. Same pivot-driven filtering pattern as REQ-04.
  - **Acceptance:** Layouts not present in `style_layouts` for the chosen style are not displayed; inactive layouts are not displayed; selecting a layout writes `projects.layout_id`.

- **REQ-06:** Step 5 (Source Image) MUST accept only files with `mime_type` in `{image/jpeg, image/png, image/webp}` and `size_bytes <= 10_485_760` (10 MiB). Accepted uploads MUST be stored on the `s3` disk at a user-scoped key, MUST create one `source_images` row referencing the file with original filename, mime type, and size, and MUST set `projects.source_image_id` on the draft row. The step MUST support `Skip`, `Replace`, and `Remove` actions.
  - **Rationale:** US-3.5 enforces the file constraints; user-scoped keys prevent collisions; Replace/Remove are explicit user actions in US-3.5.
  - **Acceptance:** A 12 MB file is rejected with a validation error; a `.gif` is rejected with a validation error; a valid upload creates exactly one `source_images` row; `projects.source_image_id` is set; `Replace` overwrites the same project reference (old `source_images` row archived/cleaned per FLEXIBLE); `Remove` sets `projects.source_image_id = NULL`; `Skip` leaves it `NULL` and advances.

- **REQ-07:** Step 6 (Inputs) MUST capture the user-supplied text fields (`name`, `phrase`, `theme`, `dedicatoria`) with category-driven validation rules (length caps, required/optional) and persist them as a JSON object in `projects.inputs`. The wizard MUST NOT submit a generation until step 6 is valid.
  - **Rationale:** US-3.6 stores inputs as JSON and wires them into prompt placeholders in Phase 7.2.
  - **Acceptance:** Empty required fields block `Continue` with a validation error; values exceeding configured max length block `Continue`; valid submit writes `projects.inputs` as JSON containing only the configured keys.

- **REQ-08:** Step 7 (Review) MUST render a read-only summary of product, mode, category, style, layout, source image (if any), and all input fields. Each section MUST expose an `Edit` action that returns the user to the corresponding step with previously entered values preserved. The `Generate` button MUST be disabled when `auth()->user()->credit_balance == 0`, with an explanatory tooltip.
  - **Rationale:** US-3.7 last-chance correction; credit gating belongs on the wizard per AC #9 because no credits means the next action (Phase 7.3) will refuse anyway, but the wizard must signal this to the user.
  - **Acceptance:** Review screen displays every section; each Edit button routes to the matching step with state preserved; clicking `Generate` when `credit_balance == 0` is impossible at the DOM level; a tooltip string is present on the disabled button.

- **REQ-09:** Every Livewire action that reads or mutates a `projects` row MUST be authorized through `ProjectPolicy` (or `$this->authorize(...)`) and MUST return HTTP-style 403 behavior for non-owners. Admin users (`users.is_admin = true`) MUST be allowed.
  - **Rationale:** US-8.1 mandates server-side authorization on every action; the wizard is the only mutation path for new `projects` rows in this feature.
  - **Acceptance:** A second user requesting another user's draft project id receives a 403; an admin requesting it succeeds; missing authorization throws `AuthorizationException` rather than silently succeeding.

- **REQ-10:** The wizard MUST be reachable only by authenticated users; guests visiting `GET /projects/new` MUST be redirected to the login screen by the standard `auth` middleware.
  - **Rationale:** Unauthenticated draft creation would orphan `projects.user_id`; Fortify `auth` middleware is already wired (`routes/web.php:7`).
  - **Acceptance:** Guest requests to `GET /projects/new` redirect to `login`; authenticated requests succeed.

- **REQ-11:** Once a `projects.first_generated_at` is non-null, the mode selector (step 1) MUST be read-only for that project. Within this SPEC (wizard-only), the mode becomes read-only only after the first generation, so this requirement is met vacuously while the project is in `draft`; subsequent Phase 7 must not mutate `mode_id` after `first_generated_at` is set. (Forward-declared constraint, not enforced here.)
  - **Rationale:** US-6.1 immutability rule.
  - **Acceptance:** No code path in this feature writes `mode_id` after `first_generated_at` is non-null; an automated test asserts no wizard action mutates `mode_id` once set.

### UI Requirements

- **REQ-U1:** The wizard MUST use a dedicated layout shell with no sidebar, a minimal topbar (logo + Exit), a centered main canvas, and a sticky footer carrying `Back`, current-selection summary, and `Continue`. (Design ref: `screens.md` S3 + `components.md` A.3, A.4, A.6.)
  - **Acceptance:** The rendered HTML uses `layouts/wizard.blade.php`; sidebar is absent; the topbar matches A.3; the footer matches A.4.

- **REQ-U2:** A progress indicator MUST display `STEP 0X OF 07` plus the current section name, with a progress bar fill proportional to `(current_step / 7) * 100%`. (Design ref: `screens.md` S3.1 + `components.md` custom progress bar.)
  - **Acceptance:** At step 3 the indicator reads `STEP 03 OF 07` and the bar fill is ≥ 28% and ≤ 43%.

- **REQ-U3:** Category tiles MUST be rendered as 3-column glass-card tiles on `md` and up, each showing an icon tile, title (`headline-md`), description (`label-md`), and a check-circle badge that is visible only when the tile is selected. (Design ref: `screens.md` S3.2 category row + `components.md` B.5 text variant.)
  - **Acceptance:** Visual snapshot matches `components.md` B.5 text variant; selection triggers the `.selection-glow` ring.

- **REQ-U4:** Style and Layout tiles MUST be rendered as aspect-square image tiles with full-bleed thumbnail, hover scale-up, and `selection-glow` ring when selected. (Design ref: `screens.md` S3.2 style/layout row + `components.md` B.5 image variant.)
  - **Acceptance:** Tiles have aspect-ratio 1:1; selected tile shows `box-shadow: 0 0 0 2px #c0c1ff`.

- **REQ-U5:** Step 5 (Source Image) MUST use a dashed-border drop zone with `cloud_upload` icon and headline `Drag your photo here`, supporting both drag-and-drop and click-to-select. On upload, the area MUST switch to a thumbnail preview with `Replace` and `Remove` buttons. (Design ref: `screens.md` S3.2 source image + `components.md` C.6.)
  - **Acceptance:** Drop zone is keyboard-accessible; Replace reopens the file picker; Remove clears `projects.source_image_id`.

- **REQ-U6:** Step 6 (Inputs) MUST render `<flux:input>` fields for `name`, `phrase`, `theme`, `dedicatoria` with visible per-field max-length indicators when limits are configured. (Design ref: `screens.md` S3.2 inputs + `components.md` C.1.)
  - **Acceptance:** All four fields render with their labels; entering text updates a `Livewire` property; character counters reflect current length vs. limit.

- **REQ-U7:** Step 7 (Review) MUST render a read-only summary card list. Each row MUST have a per-section `Edit` button. The `Generate` CTA MUST be a primary pill button that is `disabled` (with tooltip text `You're out of credits`) when `credit_balance == 0`. (Design ref: `screens.md` S3.2 review + `components.md` H status pills.)
  - **Acceptance:** Six Edit buttons exist (mode, category, style, layout, source image, inputs); `Generate` is `<button disabled>` with a `title` / `aria-describedby` attribute containing the tooltip when balance is 0.

- **REQ-U8:** Empty-state messages MUST be rendered with the shared empty-state card when a step has zero selectable rows (e.g., a Category with no associated Styles). (Design ref: `components.md` F.1.)
  - **Acceptance:** When `styles` count is 0 for the chosen category, the step shows the `style` icon and copy `No styles available for this category` from F.1, with a `Browse other categories` action.

### Non-Functional Requirements

- **REQ-N1:** Server-side validation MUST run on every wizard step before any DB write; invalid input MUST NOT partially update `projects`.
  - **Rationale:** Phase 7 reads `projects` rows; partial writes produce an unrecoverable draft.
  - **Acceptance:** A feature test that posts an invalid input on step 6 asserts that `projects.category_id`, `style_id`, `layout_id`, `source_image_id` are unchanged from the prior valid step.

- **REQ-N2:** A single S3 upload for source image MUST NOT exceed 10 MiB server-side, regardless of client-supplied `Content-Length`; the validation MUST occur on the Livewire action, not only on the browser.
  - **Rationale:** US-3.5 enforces ≤ 10 MB; bypassed client validation is a common attack.
  - **Acceptance:** A feature test posts a synthetic 12 MiB payload directly to the action and receives a 422 validation error.

- **REQ-N3:** Wizard server-side state MUST be re-validated on every Livewire request (`mount`, `hydrate`, and action calls), and the loaded `Project` MUST be re-authorized via `ProjectPolicy` per request — never trusted from the client snapshot.
  - **Rationale:** US-8.1.
  - **Acceptance:** A feature test simulates a Livewire request carrying a `project_id` belonging to user B while authenticated as user A and asserts a 403.

- **REQ-N4:** Picker queries MUST use eager-loaded relationships and indexed columns: `categories(product_id, status_id, sort_order)`, `category_styles(category_id)`, `style_layouts(style_id)`. Picker responses on the seeded 6×5×4 catalog MUST complete within p95 < 300 ms server-side.
  - **Rationale:** Wizard is the highest-traffic authenticated surface; picker latency dominates perceived performance.
  - **Acceptance:** Pest feature test asserts picker query completes below the threshold on the seeded catalog; query log inspection confirms ≤ 4 queries per step render.

### Contracts

- **REQ-C1 (HTTP):** `GET /projects/new` — authenticated route, renders `livewire.projects.wizard`. Named route: `projects.new`. (Verified absent at `kindrad-canvas/routes/web.php:1-11`; to be added.)
- **REQ-C2 (Livewire component):** `App\Livewire\Projects\Wizard` — public properties: `step: int (1-7)`, `projectId: int`, `modeId: ?int`, `categoryId: ?int`, `styleId: ?int`, `layoutId: ?int`, `sourceImageId: ?int`, `inputs: array`. Public actions: `selectMode`, `selectCategory`, `selectStyle`, `selectLayout`, `uploadSourceImage`, `removeSourceImage`, `replaceSourceImage`, `updateInput`, `next`, `back`, `goToStep`, `submit` (submit is the seam to Phase 7.3 and is **not** implemented in this SPEC).
- **REQ-C3 (Livewire components, step children):** `App\Livewire\Projects\Wizard\Steps\{Mode,Category,Style,Layout,SourceImage,Inputs,Review}` — each consumes the parent's state and emits selection events back. (Component split is FLEXIBLE; the **existence** of a step component per stage is rigid only insofar as the wizard must visually present 7 distinct steps.)
- **REQ-C4 (Policy):** `App\Policies\ProjectPolicy` — methods `view(User, Project)`, `update(User, Project)`, `delete(User, Project)`. Returns `true` only when `$user->id === $project->user_id` OR `$user->is_admin === true`. (Policy existence mandated by Phase 2.5; this SPEC requires it is actually invoked on every wizard action.)
- **REQ-C5 (Validation):** Step 6 uses Livewire `rules()` (per Boost guidelines) returning an array keyed by input field name. Failure renders `<flux:error>` messages bound to the field. Step 5 validation uses Livewire file-upload validators (`mimes:jpeg,png,webp`, `max:10240` for MiB-aligned cap, or explicit size assertion).
- **REQ-C6 (Event seam):** Wizard dispatches no events; the handoff to generation in Phase 7 is via the `projects` row read by `SubmitGeneration`. (No event contract for this SPEC.)

## FLEXIBLE (Implementation Suggestions)

- **Livewire component split:** A single `App\Livewire\Projects\Wizard` parent with a `$step` int property and a Blade view that conditionally renders step content (using `@if` chains or `livewire:is`) is the simplest approach. The `components.md` D.2 "Step Wrapper" parent already implies a parent + child split — children may live under `App\Livewire\Projects\Wizard\Steps\*` and be mounted via `<livewire:is>`. Either is acceptable; do **not** create seven sibling top-level components.
- **File upload implementation:** Use Livewire 4's `WithFileUploads` trait. Temporary uploads land on the `local` disk first; persist to `s3` only when the user advances past step 5. (Discards orphaned temp files on session expiry; Laravel handles this automatically.)
- **Replace behavior:** When the user clicks `Replace`, soft-archive the previous `source_images` row by leaving it in the DB (no FK cascade). A nightly command (post-MVP) may hard-delete orphaned rows. Acceptable alternative: hard-delete on Replace.
- **Validation library:** Prefer Livewire `rules()` for step-level rules and `WithFileUploads` validators for the source image. No FormRequest classes are needed for Livewire actions; FormRequests only apply to traditional controllers.
- **Mode immutability forward declaration:** REQ-11 is satisfied vacuously here; implement a `Project::isModeLocked(): bool` helper on the model (in Phase 2.3) and call it in step 1 to set a read-only flag.
- **Empty-state copy:** Use exact strings from `components.md` F.1's empty-state library where they apply. Localized strings may live in `lang/pt_BR.json` (PT-BR per S4 hero copy) but this is not required for MVP.
- **Pint + tests:** After every change run `vendor/bin/pint --dirty --format agent` and `php artisan test --compact --filter=Projects` (per Boost guidelines).
- **Generated commands:** Use `php artisan make:livewire Projects/Wizard` and `php artisan make:policy ProjectPolicy --model=Project` rather than hand-writing scaffolds. Tests via `php artisan make:test --pest Projects/WizardStartTest` (etc., per Phase 6 acceptance criteria).

## Domain Model

### Entities Touched (write)

- **`Project`** (`projects` table) — created on step 1 (mode) and incrementally updated through step 7.
  - Invariants:
    - `user_id` immutable after creation.
    - `product_id` set to `mug` on creation (only product in MVP scope).
    - `status_id` initially the `draft` lookup row; transitions to other statuses are not in this SPEC's scope.
    - `mode_id`, `category_id`, `style_id`, `layout_id` may be `NULL` until the user advances past the corresponding step; review step requires all four to be set before `Generate` is enabled.
    - `source_image_id` is nullable and may remain so after step 5 (skip is allowed).
    - `inputs` JSON MUST contain only keys configured for the chosen category; unknown keys are ignored at write time.
    - `first_generated_at` is `NULL` throughout this SPEC (set in Phase 7.3 on first successful generation).

- **`SourceImage`** (`source_images` table) — created on step 5 successful upload.
  - Invariants:
    - `disk = 's3'` is the canonical disk.
    - `path` is a user-scoped key (`source-images/{user_id}/{uuid}.{ext}`).
    - `mime_type` ∈ `{image/jpeg, image/png, image/webp}`.
    - `size_bytes` ≤ 10_485_760.
    - One user may have many `source_images`; one `projects` row references at most one.

### Entities Touched (read-only)

- `users` (for `credit_balance` and `is_admin`)
- `project_modes` (step 1 picker)
- `products` (filter for step 2: must be `mug` and active)
- `categories` + `category_statuses` (step 2 picker)
- `styles` + `style_statuses` (step 3 picker, filtered via `category_styles`)
- `layouts` + `layout_statuses` (step 4 picker, filtered via `style_layouts`)
- `project_statuses` (lookup for the `draft` row)
- `category_styles`, `style_layouts` (pivots driving the cascading filters)

### Key Invariants Enforced by This Feature

- `projects.user_id = auth()->id()` on creation.
- `projects.status_id` resolves to `project_statuses.slug='draft'` at creation.
- `projects.product_id` resolves to `products.slug='mug'` at creation.
- A `Project` is fully queryable by `id` and owned by exactly one user.
- `source_images.user_id = projects.user_id` for any image attached to a project.

## Workflows

### Workflow 1: Start a New Project (US-3.1 + US-6.1)

1. Authenticated user clicks `New Project` on the dashboard.
2. Browser navigates to `GET /projects/new`.
3. The `Projects\Wizard` Livewire component `mount()`s:
   - Creates a new `Project` with `user_id = auth()->id()`, `product_id = mug product`, `status_id = draft project_status`, `mode_id = NULL`, all other FKs `NULL`, `inputs = {}`.
   - Sets `step = 1`, `projectId = $project->id`.
4. The component renders the mode selector (step 1) — two tiles from `project_modes`.
5. User selects `free` or `mug`. The `selectMode($modeId)` action sets `projects.mode_id` and advances to step 2.

### Workflow 2: Pick Catalog Entities (US-3.2, US-3.3, US-3.4)

1. Step 2 renders active `mug` categories from `categories` filtered by `products.slug='mug' AND category_statuses.slug='active'`.
2. User clicks a category tile → `selectCategory($id)` writes `projects.category_id` and advances to step 3.
3. Step 3 renders `styles` joined via `category_styles` for the chosen `category_id` AND `style_statuses.slug='active'`.
4. User clicks a style tile → `selectStyle($id)` writes `projects.style_id` and advances to step 4.
5. Step 4 renders `layouts` joined via `style_layouts` for the chosen `style_id` AND `layout_statuses.slug='active'`.
6. User clicks a layout tile → `selectLayout($id)` writes `projects.layout_id` and advances to step 5.

### Workflow 3: Optional Source Image Upload (US-3.5)

1. Step 5 renders the drop zone (REQ-U5).
2. User either drops a file or clicks to open the file picker.
3. Livewire's `WithFileUploads` stores the file temporarily on the `local` disk.
4. On the `uploadSourceImage` action: validate `mimes:jpeg,png,webp` and size ≤ 10 MiB; create a `source_images` row with `disk='s3'`, copy the temp file to the user-scoped S3 key, and set `projects.source_image_id`.
5. UI shows the thumbnail + `Replace` / `Remove` buttons.
6. If the user clicks `Skip`, `project.source_image_id` stays `NULL` and the user advances to step 6.
7. If the user clicks `Remove`, `projects.source_image_id` is set to `NULL` and the UI returns to the drop zone.

### Workflow 4: Fill Inputs (US-3.6)

1. Step 6 renders `<flux:input>` fields for `name`, `phrase`, `theme`, `dedicatoria`.
2. `wire:model.live` keeps the parent's `inputs` array in sync.
3. On `next`: validate per category rules; merge valid input into `projects.inputs` (JSON column); advance to step 7.

### Workflow 5: Review and Hand Off (US-3.7)

1. Step 7 renders the summary card list.
2. Each section has an `Edit` button → `goToStep($n)` sets `step = $n` and re-renders the matching step with preserved state.
3. The `Generate` button is disabled with a tooltip when `auth()->user()->credit_balance == 0`.
4. When the user clicks `Generate` (Phase 7 handoff, **out of scope** for this SPEC), the wizard hands the `projectId` to `SubmitGeneration` (Phase 7.3). This SPEC's `submit()` method either redirects to `/projects/{id}` or is a no-op stub with a clear marker.

## Interfaces / Contracts

### HTTP Routes

- `GET /projects/new` — name `projects.new` — middleware `['auth']` — renders `<livewire:projects.wizard />` (or equivalent view).
- No POST/PUT routes; all mutations go through Livewire actions.

### Livewire Components

- `App\Livewire\Projects\Wizard` (parent).
- `App\Livewire\Projects\Wizard\Steps\Mode` (step 1).
- `App\Livewire\Projects\Wizard\Steps\Category` (step 2).
- `App\Livewire\Projects\Wizard\Steps\Style` (step 3).
- `App\Livewire\Projects\Wizard\Steps\Layout` (step 4).
- `App\Livewire\Projects\Wizard\Steps\SourceImage` (step 5).
- `App\Livewire\Projects\Wizard\Steps\Inputs` (step 6).
- `App\Livewire\Projects\Wizard\Steps\Review` (step 7).

(Component split into steps is FLEXIBLE; one parent with conditional rendering is acceptable. Names above are illustrative.)

### Blade Components Used

- `components/layout/wizard-topbar.blade.php` (A.3)
- `components/layout/wizard-footer.blade.php` (A.4)
- `layouts/wizard.blade.php` (A.6)
- `components/wizard/progress-bar.blade.php` (custom)
- `components/wizard/tile.blade.php` (B.5)
- `components/upload/dropzone.blade.php` (C.6)
- Flux: `<flux:input>`, `<flux:button>`, `<flux:error>`, `<flux:modal>` (for "Exit wizard?" confirmation)

### Policy

- `App\Policies\ProjectPolicy` (Phase 2.5 deliverable — must exist before this feature's actions can pass authorization).

### Events

- None emitted by the wizard in this SPEC. Generation-side events (`GenerationUpdated`) are Phase 7.4.

## UI Surfaces

### Screens (per `screens.md`)

- **S3 Project Wizard** — `screens.md:61-86`.
  - **S3.1 Top Progress Indicator** — referenced by REQ-U2.
  - **S3.2 Step Content** — referenced by REQ-U3 through REQ-U7.
  - **S3.3 Footer** — referenced by REQ-U1.

### Components (per `components.md`)

- **A.3 Wizard Topbar** — logo + Exit (REQ-U1).
- **A.4 Wizard Footer** — Back / Current Selection / Continue (REQ-U1).
- **A.6 Wizard Shell** — `layouts/wizard.blade.php` (REQ-U1).
- **B.5 Wizard Tile** — text variant for Category (REQ-U3), image variant for Style/Layout (REQ-U4).
- **C.1 Flux inputs** — for step 6 fields (REQ-U6).
- **C.6 File Upload Dropzone** — for step 5 (REQ-U5).
- **C.2 Flux select** — not required in MVP (no select-based pickers).
- **F.1 Empty State Card** — for steps with zero rows (REQ-U8).
- **F.2 Loading Spinner** — for in-progress picker queries.
- **F.3 Error Banner** — for failed S3 upload.

### Design Tokens Applied

- Glass-card (`components.md` B.1) on every picker tile.
- `selection-glow` (`components.md` B.5) on selected tiles.
- `headline-md` for tile titles; `label-md` for descriptions; `mono-sm` for `STEP 0X OF 07`.
- Primary fill `#c0c1ff` for the progress bar fill and the `Generate` CTA hover glow.

## Dependencies

This feature is **greenfield within the wizard slice** but depends on prior phases that the user message marks as "not yet executed". Each dependency is a hard prerequisite — none of these can be skipped.

- **Phase 1 (Database Foundation):** All 26 domain tables must exist with FKs and indexes per `database-schema.md`. Specifically required by the wizard:
  - `users` (with `credit_balance`, `is_admin` columns — already partial in the starter kit, extended in Phase 1.1).
  - `project_modes` (Phase 1.2) — for step 1 picker.
  - `products` (Phase 1.4) — for step 2 product filter.
  - `categories`, `category_statuses` (Phase 1.4) — step 2.
  - `styles`, `style_statuses` (Phase 1.4) — step 3.
  - `layouts`, `layout_statuses` (Phase 1.4) — step 4.
  - `category_styles`, `style_layouts` (Phase 1.4) — cascading filters.
  - `projects` with `SoftDeletes` (Phase 1.5) — the wizard writes here.
  - `source_images` (Phase 1.5) — step 5.
  - `project_statuses` with `draft` seed (Phase 1.2) — initial status.
- **Phase 1.6 (Catalog Seeder):** `CatalogSeeder` must populate the mug product, 6 categories, 5 styles, 4 layouts, pivot rows, and 120 prompt template stubs. Without it the pickers show empty states (REQ-U8) and the wizard is unusable end-to-end.
- **Phase 2 (Domain Models):**
  - **2.1** — Eloquent models for `Project`, `SourceImage`, `Category`, `Style`, `Layout`, `PromptTemplate`, lookup tables.
  - **2.2** — `User` model with `creditBalance` cast and relationships.
  - **2.3** — `Project` soft deletes, JSON casts, `first_generated_at` cast.
  - **2.5** — `ProjectPolicy` registered (REQUISITE for REQ-09).
- **Phase 3 (Auth Flows):** Fortify auth + signup grant so the wizard has an authenticated user with a credit balance. Without Phase 3, REQ-08's `credit_balance == 0` check is meaningless.
- **Phase 4 (Dashboard):** The `New Project` CTA on the dashboard is wired in Phase 4 (US-2.1 area). The route `GET /projects/new` exists independently of the CTA but a user typically reaches it via the CTA.
- **Phase 5 (Admin):** Catalog must be seeded and editable so REQ-03/04/05 status filters are exercisable; if admins have not yet curated associations, picker empty states appear (REQ-U8).
- **Phase 7 (Generation):** Not a hard dependency for this SPEC, but the wizard's `Generate` button is the seam — Phase 7.3 must implement `SubmitGeneration` for the button to do anything. Within this SPEC, `submit()` either redirects to `projects.show` or is a no-op stub.

## Risks & Open Questions

- **[NEEDS CLARIFICATION: category-driven inputs config]** US-3.6 says the inputs step shows "fields configured for the chosen category (e.g., name, phrase, theme, dedicatória)" but the `categories` DBML has no column for per-category input field configuration. Should the field list live on `categories` (e.g., a `JSON` column like `input_schema` describing required/optional fields and max lengths), or should a fixed four-field list (`name`, `phrase`, `theme`, `dedicatoria`) be hard-coded in this SPEC and any per-category variation be added later? The current SPEC assumes a fixed four-field list with optional per-field max-length config — please confirm.
- **[NEEDS CLARIFICATION: Replace semantics]** When the user clicks `Replace` on step 5, does the previous `source_images` row get hard-deleted, soft-archived (column flag), or left untouched (allowing history but wasting storage)? See FLEXIBLE for a default suggestion.
- **[NEEDS CLARIFICATION: Exit wizard confirmation]** `screens.md` S3.1 mentions an "Exit" button in the wizard topbar. Does clicking Exit discard the in-progress draft `projects` row (hard delete) or keep it (so the user can resume)? If kept, a `/projects/{id}` route must exist to resume (Phase 8). The SPEC assumes Exit shows a `<flux:modal>` confirming "Your draft will be saved" and routes to the dashboard, leaving the row intact.
- **[NEEDS CLARIFICATION: First-step product exposure]** The wizard's step 1 is described as "mode selector (Free vs Mug)" per the AC and US-6.1, with no prior product picker. `user-stories.md` US-3.1 says "The wizard exposes only the `mug` product in MVP", which could mean either (a) there is no product step and `mug` is implicit, or (b) there is a product step but it shows only one option. The SPEC assumes (a) — `product_id` is set to the `mug` row on `mount()` with no user choice. Please confirm.
- **[NEEDS CLARIFICATION: Source-image FK back-pointer]** The current `source_images` schema does not include a `project_id` column. The wizard sets `projects.source_image_id` but never the inverse. Confirm whether a denormalized `project_id` on `source_images` is needed for cleanup jobs (Phase 8.3 purge), or whether `projects.source_image_id` is the single source of truth.
- **[NEEDS CLARIFICATION: category-level validation rules storage]** Same root cause as the first clarification — without an `input_schema` column on `categories`, max lengths and required flags for `name`/`phrase`/`theme`/`dedicatoria` must come from somewhere. SPEC defaults to fixed lengths (`name` 80, `phrase` 240, `theme` 120, `dedicatoria` 500) hard-coded in step 6's `rules()`; please confirm or specify an alternative.

The above clarifications are blocking: they touch the data model (additive columns) and the persistence semantics. Until they are resolved, the implementer should default to the assumptions documented in FLEXIBLE and the workflows above.
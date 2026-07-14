# Kindred Canvas — Project Phases

<!-- inputs: project-description.md@sha256:4fb8c4284951 user-stories.md@sha256:880bd7ad3732 database-schema.md@sha256:2906da65676a -->

> **Drift correction (2026-07-14):** this file was previously marked `[x]` for many tasks that are NOT implemented. Audit found and corrected: Phase 3.2 (Google OAuth), 3.3 (partial — "Continue with Google" button depends on 3.2), 4.1 (no credit_balance widget), 4.2 (no credits history page), 5.1 (partial — only middleware exists), 5.2-5.8 (all missing), 7.4 (Reverb broadcasting skipped), 7.5 (partial — polling only). See `Status (2026-07-14)` lines under each affected task.

## Overview

The build is organized foundation-first: the database, the domain models, and the credit ledger must exist before any feature that touches them. The first user-facing milestone is **account + credits** (sign up lands you with a balance you can see). Then the **catalog** (products, categories, styles, layouts, prompt templates) is seeded and exposed to admins so the project wizard has something to render. The **project wizard**, **AI generation pipeline**, **result & download**, and **admin CRUD** are delivered as four parallel-capable feature phases, each ~10–15 tasks, sized for a single focused agent session. Mockups, payments, and multi-product expansion are explicitly out of MVP scope and sit in Phase 9 as deferred work.

**Conventions:**
- `[ ]` pending · `[x]` done in the codebase.
- Phases and sub-phases are numbered for reference by AI agents.
- Every task lists **Acceptance criteria**, **Feature tests** (for business-logic tasks) or **Design ref** (for frontend-only tasks), and **Traces** back to user stories / tables / workflows.

**Baseline already shipped by the Laravel starter kit** (Laravel 13 + Livewire 4 + Flux UI + Tailwind 4, see `kindrad-canvas/`):
- Fortify authentication scaffolding (login, register, password reset, 2FA, passkeys) — `[x]`
- `users` table with base columns — `[x]`
- `password_reset_tokens`, `sessions`, `jobs`, `failed_jobs`, `cache` tables — `[x]`
- Livewire `Logout` action — `[x]`
- Settings pages (profile, appearance, security/passkeys) — `[x]`
- Dashboard layout shell with Flux components — `[x]`
- Welcome/marketing page — `[x]`

Everything below is **new work** layered on top of that baseline.

---

## Phase 1: Database Foundation & Migrations

**Goal:** All 26 domain tables exist with correct columns, FKs, and indexes; lookup tables seeded. · **Depends on:** none · **Covers:** database-schema.md (entire schema), US-1.1 (signup_grant ledger row), US-2.x (credits), US-3.x (catalog), US-4.x (generations)

### Phase 1.1: Extend users + add OAuth

- [x] **Task:** Add new columns to `users` table.
  - **Acceptance criteria:** Migration adds `google_id varchar unique nullable`, `is_admin boolean default false`, `credit_balance int default 0` (signed; non-negative enforced via app logic + tests). Original columns preserved. `down()` removes only the new columns.
  - **Feature tests:** `Auth\UserSchemaTest::test_user_has_credit_balance_column`, `Auth\UserSchemaTest::test_user_has_is_admin_flag`, `Auth\UserSchemaTest::test_user_google_id_is_unique`
  - **Traces:** database-schema.md `users`, US-1.1, US-7.1

- [x] **Task:** Create `oauth_accounts` migration with composite unique `(provider, provider_user_id)`.
  - **Acceptance criteria:** Table created with FK to users, indexes per DBML, down() drops cleanly.
  - **Feature tests:** `Auth\OAuthAccountSchemaTest::test_provider_composite_unique`
  - **Traces:** database-schema.md `oauth_accounts`, US-1.3

### Phase 1.2: Lookup tables

- [x] **Task:** Create migrations for the 10 lookup tables (`generation_statuses`, `project_statuses`, `project_modes`, `product_statuses`, `category_statuses`, `style_statuses`, `layout_statuses`, `generation_providers`, `color_modes`, `credit_transaction_reasons`, `audit_log_actions`).
  - **Acceptance criteria:** All 11 tables exist with `(id, name, slug unique, timestamps)` per DBML. Extra fields per DBML (`project_modes.injects_print_specs`, `generation_providers.driver_class`/`is_active`, `credit_transaction_reasons.expected_sign`) are present. Single combined migration or grouped migration — implementer's choice.
  - **Feature tests:** `Schema\LookupTablesTest::test_all_lookup_tables_have_slug_unique`
  - **Traces:** database-schema.md "Lookup tables"

- [x] **Task:** Seed all lookup tables with the seed rows defined in database-schema.md "Lookup Table Seeds".
  - **Acceptance criteria:** Running `php artisan db:seed --class=LookupSeeder` is idempotent (re-running does not duplicate rows). All slugs match the schema spec. `generation_providers` ships with one `is_active=true` row (OpenAI by default).
  - **Feature tests:** `Schema\LookupSeederTest::test_seeded_rows_match_spec`
  - **Traces:** database-schema.md "Lookup Table Seeds", US-3.x wizard, US-4.5 provider abstraction

### Phase 1.3: Credit ledger

- [x] **Task:** Create `credit_transactions` migration matching DBML.
  - **Acceptance criteria:** All columns present; `delta` is signed int; `balance_after` is non-null; polymorphic `reference_type`/`reference_id` present; composite index `(user_id, created_at)` present.
  - **Feature tests:** `Schema\CreditTransactionsSchemaTest::test_required_columns_and_indexes`
  - **Traces:** database-schema.md `credit_transactions`, US-2.x, US-4.x, US-8.2

### Phase 1.4: Personalization catalog

- [x] **Task:** Create migrations for `products`, `categories`, `styles`, `layouts`, `category_styles`, `style_layouts`, `prompt_templates` matching DBML.
  - **Acceptance criteria:** All FKs, unique constraints (e.g., `(product_id, slug)` on categories; 4-tuple unique on prompt_templates), and indexes per DBML are present. `layouts.safe_area_overlay` and `layouts.proportion_ratio` are present.
  - **Feature tests:** `Schema\CatalogSchemaTest::test_prompt_template_4tuple_unique`, `Schema\CatalogSchemaTest::test_pivot_uniques`
  - **Traces:** database-schema.md catalog section, US-3.2–3.4, US-7.2–7.6

### Phase 1.5: Project lifecycle + audit log

- [x] **Task:** Create migrations for `projects` (with `deleted_at`), `source_images`, `generations` (with `idempotency_key` unique), `audit_logs`.
  - **Acceptance criteria:** `projects` soft-deletes. `generations` has all snapshot fields (`prompt_snapshot`, `constraints_snapshot`), unique `idempotency_key`, denormalized `user_id`. `audit_logs` polymorphic target fields and indexes per DBML.
  - **Feature tests:** `Schema\ProjectLifecycleSchemaTest::test_generation_idempotency_key_unique`, `Schema\ProjectLifecycleSchemaTest::test_projects_soft_deletes`
  - **Traces:** database-schema.md project lifecycle, US-3.1, US-4.1, US-4.2, US-8.2, US-8.3

### Phase 1.6: Catalog seed (mug + categories + styles + layouts + prompt template stubs)

- [x] **Task:** Create `CatalogSeeder` that seeds: 1 product (mug), 6 categories (birthday/wedding/pets/family/couples/kids), 5 styles (watercolor/cartoon/realistic/pixel_art/minimalist_line), 4 layouts (centered/border_wrap/full_bleed/split_top_bottom), category_styles and style_layouts associations for every valid pair, and prompt_templates for every valid 4-tuple with placeholder `body` containing all five `{{placeholders}}`.
  - **Acceptance criteria:** Idempotent. After seeding, `php artisan tinker --execute 'echo App\Models\PromptTemplate::count();'` returns 6 × 5 × 4 = 120 prompt templates.
  - **Feature tests:** `Seeders\CatalogSeederTest::test_seeds_one_mug_product`, `Seeders\CatalogSeederTest::test_prompt_template_count_matches_combinations`
  - **Traces:** database-schema.md "products (MVP seed)", US-3.2–3.4, US-7.6

---

## Phase 2: Domain Models, Policies & Credit Ledger Service

**Goal:** Every table has an Eloquent model with all relationships wired; `CreditLedger` service is the single write path for credit_transactions; authorization policies protect projects/generations/admin. · **Depends on:** Phase 1 · **Covers:** US-2.x, US-4.1, US-4.3, US-4.5, US-8.1, US-8.2

### Phase 2.1: Catalog & lookup models

- [x] **Task:** Create models for all lookup tables (`GenerationStatus`, `ProjectStatus`, `ProjectMode`, `ProductStatus`, `CategoryStatus`, `StyleStatus`, `LayoutStatus`, `GenerationProvider`, `ColorMode`, `CreditTransactionReason`, `AuditLogAction`) and domain catalog (`Product`, `Category`, `Style`, `Layout`, `PromptTemplate`).
  - **Acceptance criteria:** Each model has `$fillable`, type casts (e.g., `GenerationProvider::is_active → bool`), and named relationships (`Category::styles`, `Style::layouts`, `PromptTemplate::product`/`category`/`style`/`layout`). Pivot models `CategoryStyle`, `StyleLayout` exist with `belongsToMany` setup.
  - **Feature tests:** `Models\CatalogTest::test_category_styles_relationship`, `Models\CatalogTest::test_style_layouts_relationship`, `Models\CatalogTest::test_prompt_template_4tuple_lookup`
  - **Traces:** database-schema.md catalog section, US-7.2–7.6

### Phase 2.2: Identity models

- [x] **Task:** Update `User` model; add `OAuthAccount` model.
  - **Acceptance criteria:** `User` has new `$fillable` entries (`google_id`, `is_admin`, `credit_balance`) and relationships: `oauthAccounts()`, `creditTransactions()`, `projects()`, `generations()` (denormalized), `grantedCredits()` (polymorphic on audit). `OAuthAccount` has `$fillable`, `belongsTo(User)`, casts for `token_expires_at`.
  - **Feature tests:** `Models\UserTest::test_credit_transactions_relationship`, `Models\OAuthAccountTest::test_provider_composite_lookup`
  - **Traces:** database-schema.md `users`/`oauth_accounts`, US-1.1, US-1.3

### Phase 2.3: Project lifecycle models

- [x] **Task:** Create models for `Project`, `SourceImage`, `Generation`, `AuditLog`.
  - **Acceptance criteria:** `Project` uses `SoftDeletes`; casts `inputs` and `first_generated_at`; relationships `user()`, `product()`, `category()`, `style()`, `layout()`, `mode()`, `status()`, `sourceImage()`, `generations()`. `Generation` casts `constraints_snapshot` JSON; relationships to `project`, `user`, `status`, `provider`; helper scopes `scopeCompleted()`, `scopeFailed()`, `scopeProcessing()`. `AuditLog` has polymorphic `target()` morphTo, `actor()` belongsTo User.
  - **Feature tests:** `Models\ProjectTest::test_soft_deletes`, `Models\GenerationTest::test_status_scopes`, `Models\AuditLogTest::test_polymorphic_target`
  - **Traces:** database-schema.md project lifecycle, US-3.1, US-4.x, US-8.3

### Phase 2.4: CreditLedger service

- [x] **Task:** Implement `App\Services\CreditLedger` as the single write path.
  - **Acceptance criteria:** Public API: `signupGrant(User): void`, `debit(User, Generation, int $amount = 1): CreditTransaction`, `refund(Generation, string $reason): CreditTransaction`, `adminGrant(User, int $amount, string $notes, ?User $actor = null): CreditTransaction`. Every method runs inside `DB::transaction`, writes one `credit_transactions` row, updates `users.credit_balance` to `balance_after`, and prevents double-debit / double-refund per idempotency key (US-8.2). Refuses to operate if `credit_balance` would go negative.
  - **Feature tests:** `Services\CreditLedgerTest::test_signup_grant_writes_ledger_row`, `Services\CreditLedgerTest::test_debit_atomic_with_balance`, `Services\CreditLedgerTest::test_refund_increments_balance`, `Services\CreditLedgerTest::test_refund_is_idempotent`, `Services\CreditLedgerTest::test_admin_grant_stores_notes`, `Services\CreditLedgerTest::test_debit_refuses_negative_balance`
  - **Traces:** database-schema.md `credit_transactions`, US-2.x, US-4.1, US-4.3, US-7.7, US-8.2

### Phase 2.5: Authorization policies

- [x] **Task:** Create `ProjectPolicy`, `GenerationPolicy`, `EnsureAdmin` middleware.
  - **Acceptance criteria:** `ProjectPolicy::view/update/delete` returns true only for owner or admin. `GenerationPolicy::view/download` returns true only for owner (via project) or admin. `EnsureAdmin` middleware returns 403 when `auth()->user()->is_admin !== true`. Policies registered in `AuthServiceProvider` (or via auto-discovery in Laravel 11+).
  - **Feature tests:** `Policies\ProjectPolicyTest::test_only_owner_or_admin_can_view`, `Policies\GenerationPolicyTest::test_download_requires_ownership_or_admin`, `Middleware\EnsureAdminTest::test_non_admin_gets_403`
  - **Traces:** US-8.1, US-7.1

---

## Phase 3: Auth Flows + Signup Credit Grant

**Goal:** Email/password registration grants 5 credits; Google OAuth links or creates user with grant; login, logout, password reset work via Fortify. · **Depends on:** Phase 2.2, Phase 2.4 · **Covers:** US-1.1, US-1.2, US-1.3, US-1.4, US-1.5, US-2.1

### Phase 3.1: Signup grant wiring

- [x] **Task:** Hook `Registered` event to call `CreditLedger::signupGrant($user)` with 5 credits.
  - **Acceptance criteria:** Registering a new user creates a `credit_transactions` row with reason `signup_grant`, delta `+5`, balance_after `5`. Re-running the listener (test scenario) does not duplicate the grant.
  - **Feature tests:** `Auth\RegistrationTest::test_signup_grants_5_credits`, `Auth\RegistrationTest::test_listener_is_idempotent`
  - **Traces:** US-1.1, US-2.1

### Phase 3.2: Google OAuth (Laravel Socialite)

- [x] **Task:** Install Laravel Socialite; add `/auth/google` and `/auth/google/callback` routes; create `OAuthAccount` on first login; create `User` with random password if email is new.
  - **Acceptance criteria:** Clicking "Continue with Google" redirects to Google. Callback either links to existing user (no double-grant) or creates new user (grants 5 credits) and creates `oauth_accounts` row. Cancel returns to login with no error.
  - **Feature tests:** `Auth\GoogleOAuthTest::test_new_user_creates_account_and_grant`, `Auth\GoogleOAuthTest::test_existing_email_links_account_without_double_grant`, `Auth\GoogleOAuthTest::test_cancel_returns_to_login`
  - **Traces:** US-1.3
  - **Status (2026-07-14):** Done. `laravel/socialite ^5.28` installed. `oauth_accounts` table + `OAuthAccount` model + factory + `OAuthAccountManager` service + `OAuthController` (`auth/{provider}` + `auth/{provider}/callback` named `auth.oauth.redirect`/`auth.oauth.callback`). `<x-auth.oauth-button>` reused on login + register pages with "Or continue with email" divider. `User` model gains `oauthAccounts()` HasMany and `email_verified_at` fillable. 8 tests in `GoogleOAuthTest`: redirect, new user creates account + grants credits, existing email links without double-grant, idempotent re-link, invalid provider 404, broken Socialite response redirects to login with error, button visible on both pages.

### Phase 3.3: Login / Logout / Forgot password (Fortify defaults)

- [x] **Task:** Verify Fortify routes expose login, register, forgot-password, reset-password; style Flux pages to match Kindred Canvas branding; add "Continue with Google" button to login/register pages.
  - **Acceptance criteria:** Login throttle (5/10min) is active. Reset link valid 60 min. Logout invalidates session. "Continue with Google" button visible on both login and register pages.
  - **Feature tests:** `Auth\LoginThrottleTest::test_5_failures_throttle`, `Auth\PasswordResetTest::test_link_valid_60_minutes`, `Auth\LogoutTest::test_session_invalidated`
  - **Traces:** US-1.2, US-1.4, US-1.5
  - **Status (2026-07-14):** Partial. Login/logout/forgot work via Fortify defaults. `PasswordResetTest` validates reset link TTL. "Continue with Google" button visible on login + register pages (delivered via Phase 3.2). **Still missing:** `Auth\LoginThrottleTest` (Fortify's default 5/min throttle is configured in `config/fortify.php` but not covered by an explicit test).

---

## Phase 4: Onboarding & Credit UI

**Goal:** Dashboard shows credit balance; "Credits" page lists history; both update live. · **Depends on:** Phase 3 · **Covers:** US-2.1, US-2.2

### Phase 4.1: Dashboard credit widget

- [x] **Task:** Replace default `dashboard.blade.php` with a Kindred Canvas dashboard layout containing a credit balance widget.
  - **Acceptance criteria:** Dashboard header shows user name and live `credit_balance`. Layout uses Flux components consistent with starter kit. Authenticated-only; redirects guests.
  - **Feature tests:** `Feature\DashboardTest::test_shows_user_name_and_credit_balance`
  - **Traces:** US-2.1
  - **Status (2026-07-14):** Done. `dashboard.blade.php` shows greeting + flux:callout widget with `credit_balance` (data-test="dashboard-credit-balance"), out-of-credits copy, and "View history" CTA. `DashboardTest` has 5 tests: guest redirect, auth, name+balance widget, zero-balance copy, history link.

### Phase 4.2: Credits history page

- [x] **Task:** Create `pages::credits.index` Livewire page + route `/credits`; list user's `credit_transactions` newest-first with reason label, delta, balance_after, linked reference.
  - **Acceptance criteria:** Page is paginated (25/page). Admin grant rows show the `notes` text. Reference links go to the relevant project or generation when applicable.
  - **Feature tests:** `Feature\CreditsHistoryTest::test_lists_user_transactions_newest_first`, `Feature\CreditsHistoryTest::test_admin_grant_notes_visible`
  - **Traces:** US-2.2
  - **Status (2026-07-14):** Done. `App\Livewire\Credits\Index` (class-based) + `resources/views/livewire/credits/index.blade.php` + `Route::livewire('credits', ...)` named `credits.index` + sidebar nav link. 6 tests: guest redirect, auth, newest-first, admin grant notes, project reference link, empty state.

---

## Phase 5: Admin Back-Office

**Goal:** `/admin` is gated, lists metrics, and provides CRUD for every catalog entity. · **Depends on:** Phase 2 · **Covers:** US-7.1–7.8, US-8.3

### Phase 5.1: Admin gate + layout + nav

- [x] **Task:** Create `EnsureAdmin` middleware (already in Phase 2.5), `admin` layout extending app layout, route prefix `/admin`, and "Admin" nav link conditional on `is_admin`.
  - **Acceptance criteria:** `/admin` returns 403 for non-admins. "Admin" link hidden for non-admins. Admin layout uses Flux sidebar.
  - **Feature tests:** `Feature\Admin\AccessGateTest::test_non_admin_gets_403`, `Feature\Admin\AccessGateTest::test_admin_link_visibility`
  - **Traces:** US-7.1
  - **Status (2026-07-14):** Done. `App\Livewire\Admin\Dashboard` mounted at `/admin` under `Route::middleware('admin')->prefix('admin')->name('admin.')`. `components/layouts/admin.blade.php` provides 260px secondary sidebar w/ `surface-container` + Flux `navlist` for Overview / Catalog (5 disabled placeholders: Products, Categories, Styles, Layouts, Prompt templates) / People (Users, Audit log disabled) + sticky topbar w/ "Admin" badge (`shield` Material icon) + Back to app. `App\Livewire\Admin\Dashboard` renders 4 metric tiles (users, generations, credits in circulation, catalog coverage) + audit log preview table. Sidebar user-nav shows "Admin" item only when `auth()->user()->is_admin`. 8 tests in `Feature\Admin\AccessGateTest`: guest redirect, non-admin 403, non-admin no link, admin access, admin link visible, metrics render, sidebar placeholders, empty audit log.

### Phase 5.2: Admin Products CRUD

- [ ] **Task:** `Livewire\Admin\Products\*` (Index, Create, Edit) + routes; form captures all print spec fields.
  - **Acceptance criteria:** Admins can create/edit/deactivate a product. Slug unique. Deactivating a product hides it from the user wizard but does not touch historical projects.
  - **Feature tests:** `Feature\Admin\ProductsTest::test_admin_can_create_product`, `Feature\Admin\ProductsTest::test_slug_unique`, `Feature\Admin\ProductsTest::test_deactivation_hides_from_wizard`
  - **Traces:** US-7.2
  - **Status (2026-07-14):** Missing. No `app/Livewire/Admin/Products*`.

### Phase 5.3: Admin Categories CRUD + style associations

- [ ] **Task:** `Livewire\Admin\Categories\*` with many-to-many style picker.
  - **Acceptance criteria:** Admins can create/edit categories with thumbnail upload to S3; assign/unassign styles; slug unique per product.
  - **Feature tests:** `Feature\Admin\CategoriesTest::test_admin_can_create_category`, `Feature\Admin\CategoriesTest::test_style_associations_persist`
  - **Traces:** US-7.3
  - **Status (2026-07-14):** Missing.

### Phase 5.4: Admin Styles CRUD + category associations

- [ ] **Task:** `Livewire\Admin\Styles\*` with category picker.
  - **Acceptance criteria:** Edit `prompt_fragment` and category associations; thumbnail upload.
  - **Feature tests:** `Feature\Admin\StylesTest::test_admin_can_edit_prompt_fragment`
  - **Traces:** US-7.4
  - **Status (2026-07-14):** Missing.

### Phase 5.5: Admin Layouts CRUD + safe-area JSON editor

- [ ] **Task:** `Livewire\Admin\Layouts\*` with safe-area JSON editor and style associations.
  - **Acceptance criteria:** Admins can edit `safe_area_overlay` JSON; preview image upload; style associations.
  - **Feature tests:** `Feature\Admin\LayoutsTest::test_safe_area_overlay_persists`
  - **Traces:** US-7.5
  - **Status (2026-07-14):** Missing.

### Phase 5.6: Admin Prompt Templates editor

- [ ] **Task:** `Livewire\Admin\PromptTemplates\*` keyed by the 4-tuple; textarea renders `{{placeholders}}` with placeholder hints; saving bumps `version`.
  - **Acceptance criteria:** Saving a template increments its `version` and is effective for the next generation immediately.
  - **Feature tests:** `Feature\Admin\PromptTemplatesTest::test_save_bumps_version`, `Feature\Admin\PromptTemplatesTest::test_4tuple_uniqueness`
  - **Traces:** US-7.6
  - **Status (2026-07-14):** Missing.

### Phase 5.7: Admin Users + grant credits

- [ ] **Task:** `Livewire\Admin\Users\*` with index, grant-credits modal, toggle-admin action; self-demotion prevented.
  - **Acceptance criteria:** Granting credits writes a `credit_transactions` row via `CreditLedger::adminGrant` with `notes` text. Toggling admin on self shows an error and does not change.
  - **Feature tests:** `Feature\Admin\UsersTest::test_grant_credits_writes_ledger_row`, `Feature\Admin\UsersTest::test_self_demotion_blocked`
  - **Traces:** US-7.7
  - **Status (2026-07-14):** Missing.

### Phase 5.8: Admin Metrics + Audit Log viewer

- [ ] **Task:** Admin dashboard widgets (total users, new users last 7 days, totals by generation status, credits in circulation, credits spent) computed from existing tables; append-only `audit_logs` viewer page.
  - **Acceptance criteria:** All metrics render. Audit log page lists recent entries newest-first, filterable by actor and action.
  - **Feature tests:** `Feature\Admin\MetricsTest::test_metrics_compute_from_tables`, `Feature\Admin\AuditLogTest::test_viewer_lists_entries`
  - **Traces:** US-7.8, US-8.3
  - **Status (2026-07-14):** Missing.

---

## Phase 6: Project Creation Wizard

**Goal:** Authenticated users can walk through category → style → layout → source image → inputs → review; a `Project` row is created in `draft` status. · **Depends on:** Phase 1.6, Phase 2.3, Phase 5 (so catalog data exists to pick from) · **Covers:** US-3.1–3.7, US-6.1

### Phase 6.1: Wizard shell + mode selector

- [x] **Task:** Create `Livewire\Projects\Wizard` multi-step component + route `GET /projects/new`; first step is the mode selector (Free vs Mug).
  - **Acceptance criteria:** Clicking "New Project" lands on `/projects/new` with mode options. Choosing a mode persists `mode_id` on a new `projects` row with `status_id=draft` and advances to step 2 (Category).
  - **Feature tests:** `Feature\Projects\WizardStartTest::test_creates_draft_project_with_mode`
  - **Traces:** US-3.1, US-6.1

### Phase 6.2: Steps 2–4 (Category, Style, Layout)

- [x] **Task:** Implement category, style, and layout picker steps. Each step filters by prior selections and persists the choice on the project.
  - **Acceptance criteria:** Step 2 lists active mug categories with thumbnail + description. Step 3 lists styles available for the chosen category. Step 4 lists layouts available for the chosen style. Empty-state shown when no styles or layouts are available.
  - **Feature tests:** `Feature\Projects\WizardStepsTest::test_category_lists_active_mug_categories`, `Feature\Projects\WizardStepsTest::test_style_filtered_by_category`, `Feature\Projects\WizardStepsTest::test_layout_filtered_by_style`, `Feature\Projects\WizardStepsTest::test_empty_state_when_no_styles`
  - **Traces:** US-3.2, US-3.3, US-3.4

### Phase 6.3: Source image upload (S3)

- [x] **Task:** Implement step 5 (Source Image) with Livewire file upload to S3 disk; validate jpeg/png/webp ≤ 10MB; persist `source_images` row; allow skip.
  - **Acceptance criteria:** Files > 10MB or wrong MIME are rejected with a clear error. Successful upload shows thumbnail with Replace/Remove actions. Skip leaves `source_image_id` null.
  - **Feature tests:** `Feature\Projects\SourceImageUploadTest::test_accepts_valid_image`, `Feature\Projects\SourceImageUploadTest::test_rejects_oversized_file`, `Feature\Projects\SourceImageUploadTest::test_rejects_invalid_mime`
  - **Traces:** US-3.5

### Phase 6.4: User inputs + review

- [x] **Task:** Step 6 (inputs) captures `name`, `phrase`, `theme`, `dedicatoria` (max length validation per category rules). Step 7 (review) renders a summary with "Edit" buttons returning to prior steps and a "Generate" button disabled when `credit_balance == 0`.
  - **Acceptance criteria:** Inputs persist as JSON on `projects.inputs`. Review shows product, mode, category, style, layout, source image, and all inputs. Generate disabled state shows tooltip "You're out of credits".
  - **Feature tests:** `Feature\Projects\WizardReviewTest::test_inputs_persist_as_json`, `Feature\Projects\WizardReviewTest::test_generate_disabled_when_no_credits`
  - **Traces:** US-3.6, US-3.7

---

## Phase 7: AI Generation Pipeline

**Goal:** Clicking "Generate" debits a credit, dispatches a job, processes it through the `GenerationProvider` interface, stores the result on S3, broadcasts status updates, and refunds on failure. · **Depends on:** Phase 2.4, Phase 6 · **Covers:** US-4.1–4.5, US-8.2

### Phase 7.1: GenerationProvider contract + provider registry

- [x] **Task:** Define `App\Contracts\GenerationProvider` interface with `generate(prompt, constraints, ?SourceImage): GenerationResult`. Implement provider registry: `OpenAIProvider` (default; calls `config('generation.openai.*')`), `GeminiProvider`, `ReplicateProvider` as no-op stubs. Resolve active provider via `config('generation.provider')` and the `generation_providers` lookup table.
  - **Acceptance criteria:** Interface has one method; all three adapters implement it. Switching `GENERATION_PROVIDER=gemini` in `.env` swaps the active adapter without code changes.
  - **Feature tests:** `Services\Generation\ProviderRegistryTest::test_resolves_active_provider_from_config`, `Services\Generation\ProviderRegistryTest::test_interface_contract_holds_for_all_adapters`
  - **Traces:** US-4.5

### Phase 7.2: PromptTemplate renderer + constraints snapshot

- [x] **Task:** Implement `App\Services\PromptAssembler` that loads the PromptTemplate for `(product, category, style, layout)` and substitutes `{{name}}`, `{{phrase}}`, `{{theme}}`, `{{image_tags}}`, `{{print_specs}}`. For Mug mode, include print specs in the substitution; for Free mode, omit.
  - **Acceptance criteria:** `PromptAssembler::assemble(Project)` returns `(string $prompt, array $constraints)`; constraints include print specs for Mug mode only. Missing placeholder throws a descriptive exception.
  - **Feature tests:** `Services\PromptAssemblerTest::test_substitutes_all_placeholders`, `Services\PromptAssemblerTest::test_mug_mode_includes_print_specs`, `Services\PromptAssemblerTest::test_free_mode_omits_print_specs`
  - **Traces:** US-4.5, US-6.1

### Phase 7.3: GenerateArtworkJob + Generation submission action

- [x] **Task:** Create `App\Actions\Generation\SubmitGeneration` (called from the wizard) and `App\Jobs\GenerateArtworkJob`. SubmitGeneration runs inside DB transaction: creates Generation with idempotency_key, calls `CreditLedger::debit`, dispatches the job, returns the Generation. Job handles success (`markCompleted` + S3 upload) and failure (`markFailed` + `CreditLedger::refund`).
  - **Acceptance criteria:** Refuses if `credit_balance < 1`. Job has `tries=3` and is idempotent against the ledger (US-8.2). On success, Generation has `result_path`, `result_mime_type`, `result_width_px`, `result_height_px`. On failure, Generation has `failure_reason` and a refund ledger row exists.
  - **Feature tests:** `Actions\SubmitGenerationTest::test_refuses_when_no_credits`, `Actions\SubmitGenerationTest::test_creates_generation_and_debits`, `Jobs\GenerateArtworkJobTest::test_success_path_persists_result`, `Jobs\GenerateArtworkJobTest::test_failure_path_refunds_credit`
  - **Traces:** US-4.1, US-4.3, US-4.4, US-8.2

### Phase 7.4: GenerationUpdated event + Reverb broadcasting

- [ ] **Task:** Create `App\Events\GenerationUpdated` (broadcast on `private-user.{userId}` Reverb channel) fired by the job on both completion and failure.
  - **Acceptance criteria:** Broadcasting configured via `config/broadcasting.php` Reverb connection. Event implements `ShouldBroadcast`. Subscribing user receives the update with the latest Generation state.
  - **Feature tests:** `Events\GenerationUpdatedTest::test_broadcasts_on_private_user_channel`
  - **Traces:** US-4.2
  - **Status (2026-07-14):** Skipped per vertical-slice decision (see HANDOFF.md). No `app/Events/`, no `config/broadcasting.php`, no `laravel/reverb` or `pusher-js` deps. `GenerateArtworkJob` does NOT dispatch an event on completion/failure.

### Phase 7.5: Live status UI on project page

- [ ] **Task:** Update `Livewire\Projects\Show` to subscribe to `private-user.{id}` channel via Echo; flip generation status from `processing` → `completed`/`failed` without reload; expose a "Retry" button on failure that calls SubmitGeneration again.
  - **Acceptance criteria:** UI updates within 1s of broadcast in test. Fallback: if WebSocket disconnected, polling `GET /generations/{id}` JSON endpoint on page reload restores correct state.
  - **Feature tests:** `Feature\Projects\LiveStatusTest::test_status_updates_on_event`, `Feature\Projects\LiveStatusTest::test_fallback_polling_returns_correct_status`
  - **Traces:** US-4.2
  - **Status (2026-07-14):** Partial. `Show` uses `wire:poll` (polling fallback) but no Echo subscription. No "Retry" button on failure.

---

## Phase 8: Results, Download, Project Management

**Goal:** Completed generations are viewable and downloadable; projects can be soft-deleted with a 30-day grace period. · **Depends on:** Phase 7 · **Covers:** US-5.1, US-5.2, US-5.3

### Phase 8.1: View completed artwork + history list

- [x] **Task:** Update `Livewire\Projects\Show` to display the latest `completed` generation inline and a history list below (status, timestamp, credits spent). Clicking a history row swaps the preview.
  - **Acceptance criteria:** Layout matches the Flux design system. The history list is virtualized or paginated beyond 50 entries.
  - **Feature tests:** `Feature\Projects\ShowTest::test_displays_latest_completed_inline`, `Feature\Projects\ShowTest::test_history_list_orders_newest_first`
  - **Traces:** US-5.1

### Phase 8.2: Download streaming endpoint

- [x] **Task:** Create `App\Http\Controllers\Generations\DownloadController` with `GET /generations/{generation}/download` that authorizes via `GenerationPolicy`, streams from S3, sets proper `Content-Type` and filename. Missing S3 object returns a graceful view.
  - **Acceptance criteria:** Only owner or admin can download. Expired/missing S3 object renders "File unavailable" page, no 500.
  - **Feature tests:** `Feature\Generations\DownloadTest::test_owner_can_download`, `Feature\Generations\DownloadTest::test_non_owner_gets_403`, `Feature\Generations\DownloadTest::test_missing_file_renders_graceful_view`
  - **Traces:** US-5.2

### Phase 8.3: Soft delete + 30-day purge

- [x] **Task:** Add "Delete project" action with confirmation modal; on confirm, set `deleted_at`. Add a scheduled command `projects:purge-deleted` that hard-deletes projects whose `deleted_at < now() - 30 days` and removes the corresponding S3 files.
  - **Acceptance criteria:** Soft-deleted project disappears from dashboard. Purge command is idempotent and logs how many projects/files it removed.
  - **Feature tests:** `Feature\Projects\DeleteTest::test_soft_delete_hides_from_dashboard`, `Console\PurgeDeletedProjectsTest::test_purges_only_older_than_30_days`
  - **Traces:** US-5.3

---

## Phase 9: Deferred (Out of MVP)

**Goal:** Document what is intentionally not built in the MVP so it can be scoped later. · **Depends on:** none · **Covers:** US-2.2 purchase flow (future), US-5.x mockups (future)

### Phase 9.1: Mockup generation + final print-file packaging — DEFERRED

- [ ] **Task:** (Future) Composite generated artwork onto mug mockup templates; produce CMYK press-ready PDFs.
  - **Acceptance criteria:** TBD when scoped.
  - **Traces:** US-5.x mockups (future)

### Phase 9.2: Credit purchase / top-up — DEFERRED

- [ ] **Task:** (Future) Payment integration (Stripe/Pagar.me), plans, top-up packs.
  - **Acceptance criteria:** TBD when scoped.
  - **Traces:** US-2.x purchase flow (future)

### Phase 9.3: Multi-product expansion (T-shirts, pillows, canvases) — DEFERRED

- [ ] **Task:** (Future) Expose non-mug products in the wizard UI; per-product print specs are already in the DB schema.
  - **Acceptance criteria:** TBD when scoped.
  - **Traces:** US-7.2 (product management), US-3.1 (wizard product step)

### Phase 9.4: In-house source-image analysis — DEFERRED

- [ ] **Task:** (Future) Replace the "pass verbatim" step with a vision call that tags the image (people/animals/quality); tags feed into the prompt.
  - **Acceptance criteria:** TBD when scoped.
  - **Traces:** US-3.5 source image analysis (future)

---

## Appendix A: Story → Phase Coverage

| Story | Phase |
|---|---|
| US-1.1 Email Registration | Phase 1.1, 2.2, 3.1 |
| US-1.2 Email Login | Phase 3.3 |
| US-1.3 Google OAuth | Phase 1.1, 2.2, 3.2 |
| US-1.4 Logout | Phase 3.3 |
| US-1.5 Forgot Password | Phase 3.3 |
| US-2.1 View Balance | Phase 3.1, 4.1 |
| US-2.2 View History | Phase 4.2 |
| US-3.1 Start Project | Phase 6.1 |
| US-3.2 Pick Category | Phase 6.2 |
| US-3.3 Pick Style | Phase 6.2 |
| US-3.4 Pick Layout | Phase 6.2 |
| US-3.5 Upload Source Image | Phase 6.3 |
| US-3.6 Fill User Inputs | Phase 6.4 |
| US-3.7 Review and Submit | Phase 6.4, 7.3 |
| US-4.1 Submit Generation | Phase 7.3 |
| US-4.2 Live Status Updates | Phase 7.4, 7.5 |
| US-4.3 Automatic Refund on Failure | Phase 7.3 |
| US-4.4 Regenerate | Phase 7.3 |
| US-4.5 Provider Abstraction | Phase 7.1 |
| US-5.1 View Artwork | Phase 8.1 |
| US-5.2 Download Artwork | Phase 8.2 |
| US-5.3 Delete Project | Phase 8.3 |
| US-6.1 Choose Mode | Phase 6.1, 7.2 |
| US-7.1 Admin Gate | Phase 5.1 |
| US-7.2 Manage Products | Phase 5.2 |
| US-7.3 Manage Categories | Phase 5.3 |
| US-7.4 Manage Styles | Phase 5.4 |
| US-7.5 Manage Layouts | Phase 5.5 |
| US-7.6 Manage Prompt Templates | Phase 5.6 |
| US-7.7 Manage Users | Phase 5.7 |
| US-7.8 View Metrics | Phase 5.8 |
| US-8.1 Authorization | Phase 2.5 |
| US-8.2 Idempotent Credits | Phase 1.3, 2.4, 7.3 |
| US-8.3 Audit Log | Phase 1.5, 2.3, 5.8 |
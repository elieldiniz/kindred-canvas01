# Kindred Canvas — Project Phases

<!-- inputs: project-description.md@sha256:891934ac4985 user-stories.md@sha256:649171448264 database-schema.md@sha256:3690db7ca0bd -->

> **Drift corrections (2026-07-15):**
> - Tasks previously marked `[x]` for the wizard (Phase 6) refer to the 7-step wizard that was **replaced** by the single-page configurator in `project-wizard-v2` (separate feature). Phase 6.x is rewritten below as `[x]` reflecting the v2 configurator.
> - Phase 5.2–5.8 are confirmed `[x]` by inspection of `kindrad-canvas/app/Livewire/Admin/{Products,Categories,Styles,Layouts,PromptTemplates,Users,AuditLog}/*`, the corresponding `routes/web.php` admin routes, and the matching `tests/Feature/Admin/*Test.php` files. The 2026-07-14 audit marked several of them `[ ]` but that's stale and overridden here.
> - Phase 7.4 (Reverb broadcasting) remains skipped per the project's vertical-slice decision in `HANDOFF.md`; deferred as `DeferredWork::LiveUpdates`.

## Overview

The build is organized foundation-first: the database, the domain models, and the credit ledger must exist before any feature that touches them. The first user-facing milestone is **account + credits** (sign up lands you with a balance you can see). Then the **catalog** (products, categories, styles, layouts, prompt templates) is seeded and exposed to admins so the project wizard has something to render. The **project wizard (v2 configurator)**, **AI generation pipeline**, **result & download**, and **admin CRUD** are delivered as feature phases, each ~10–15 tasks, sized for a single focused agent session. The recurring-billing surface — **Stripe + Laravel Cashier + admin-managed plans + customer lifecycle + webhook layer** — sits as the new feature phase (Phase 9) between admin CRUD and result/download so the catalog back-office work the billing UI consumes is already in place.

**Conventions:**
- `[ ]` pending · `[x]` done in the codebase (verified by file/directory presence for the current build).
- Phases and sub-phases are numbered for reference by AI agents.
- Every task lists **Acceptance criteria**, **Feature tests** (for business-logic tasks) or **Design ref** (for frontend-only tasks), and **Traces** back to user stories / tables / workflows.
- When a phase straddles a third-party SDK (`laravel/socialite`, `laravel/cashier`), the SDK install is its own first task in the phase so the rest of the phase can `use` it.

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

**Goal:** All domain tables exist with correct columns, FKs, and indexes; lookup tables seeded. · **Depends on:** none · **Covers:** database-schema.md (entire schema), US-1.1 (signup_grant ledger row), US-2.x (credits), US-3.x (catalog), US-4.x (generations), US-8.x, US-11.x

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

- [x] **Task:** Create migrations for the lookup tables (`generation_statuses`, `project_statuses`, `project_modes`, `product_statuses`, `category_statuses`, `style_statuses`, `layout_statuses`, `generation_providers`, `color_modes`, `credit_transaction_reasons`, `audit_log_actions`).
  - **Acceptance criteria:** All tables exist with `(id, name, slug unique, timestamps)` per DBML. Extra fields per DBML (`project_modes.injects_print_specs`, `generation_providers.driver_class`/`is_active`, `credit_transaction_reasons.expected_sign`) are present.
  - **Feature tests:** `Schema\LookupTablesTest::test_all_lookup_tables_have_slug_unique`
  - **Traces:** database-schema.md "Lookup tables"

- [x] **Task:** Seed all lookup tables with the seed rows defined in database-schema.md "Lookup Table Seeds".
  - **Acceptance criteria:** Running the seeder is idempotent. All slugs match the schema spec. `generation_providers` ships with one `is_active=true` row (OpenAI by default).
  - **Feature tests:** `Schema\LookupSeederTest::test_seeded_rows_match_spec`
  - **Traces:** database-schema.md "Lookup Table Seeds", US-3.x wizard, US-4.5 provider abstraction

### Phase 1.3: Credit ledger

- [x] **Task:** Create `credit_transactions` migration matching DBML.
  - **Acceptance criteria:** All columns present; `delta` is signed int; `balance_after` is non-null; polymorphic `reference_type`/`reference_id` present; composite index `(user_id, created_at)` present.
  - **Feature tests:** `Schema\CreditTransactionsSchemaTest::test_required_columns_and_indexes`
  - **Traces:** database-schema.md `credit_transactions`, US-2.x, US-4.x, US-8.2

### Phase 1.4: Personalization catalog

- [x] **Task:** Create migrations for `products`, `categories`, `styles`, `layouts`, `category_styles`, `style_layouts`, `prompt_templates` matching DBML.
  - **Acceptance criteria:** All FKs, unique constraints (`(product_id, slug)` on categories; 4-tuple unique on prompt_templates), and indexes per DBML are present. `layouts.safe_area_overlay` and `layouts.proportion_ratio` are present.
  - **Feature tests:** `Schema\CatalogSchemaTest::test_prompt_template_4tuple_unique`, `Schema\CatalogSchemaTest::test_pivot_uniques`
  - **Traces:** database-schema.md catalog section, US-3.2–3.4, US-7.2–7.6

### Phase 1.5: Project lifecycle + audit log

- [x] **Task:** Create migrations for `projects` (with `deleted_at`), `source_images`, `generations` (with `idempotency_key` unique), `audit_logs`, `project_photos` (new pivot), `pose_statuses`, `poses`, plus the seed for the drop of `projects.source_image_id`.
  - **Acceptance criteria:** `projects` soft-deletes; new `project_photos` table exists; `generations` has all snapshot fields (`prompt_snapshot`, `constraints_snapshot`), unique `idempotency_key`, denormalized `user_id`. `audit_logs` polymorphic target fields and indexes per DBML. `poses` + `pose_statuses` seeded.
  - **Feature tests:** `Schema\ProjectLifecycleSchemaTest::test_generation_idempotency_key_unique`, `Schema\ProjectLifecycleSchemaTest::test_projects_soft_deletes`, `Schema\ProjectLifecycleSchemaTest::test_project_photos_position_index`
  - **Traces:** database-schema.md project lifecycle, US-3.1, US-4.1, US-4.2, US-8.2, US-8.3

### Phase 1.6: Catalog seed (mug + categories + styles + layouts + prompt template stubs)

- [x] **Task:** Create `CatalogSeeder` that seeds: 1 product (mug), 6 categories (birthday/wedding/pets/family/couples/kids), 5 styles (watercolor/cartoon/realistic/pixel_art/minimalist_line), 4 layouts (centered/border_wrap/full_bleed/split_top_bottom), 8 poses, category_styles and style_layouts associations for every valid pair, and prompt_templates for every valid 4-tuple with placeholder `body` containing all five `{{placeholders}}`.
  - **Acceptance criteria:** Idempotent. After seeding, `PromptTemplate::count()` returns 6 × 5 × 4 = 120 prompt templates.
  - **Feature tests:** `Seeders\CatalogSeederTest::test_seeds_one_mug_product`, `Seeders\CatalogSeederTest::test_prompt_template_count_matches_combinations`
  - **Traces:** database-schema.md "products (MVP seed)", US-3.2–3.4, US-7.6

---

## Phase 2: Domain Models, Policies & Credit Ledger Service

**Goal:** Every table has an Eloquent model with all relationships wired; `CreditLedger` service is the single write path for credit_transactions; authorization policies protect projects/generations/admin. · **Depends on:** Phase 1 · **Covers:** US-2.x, US-4.1, US-4.3, US-4.5, US-8.1, US-8.2

### Phase 2.1: Catalog & lookup models

- [x] **Task:** Create models for all lookup tables and domain catalog (`Product`, `Category`, `Style`, `Layout`, `PromptTemplate`).
  - **Acceptance criteria:** Each model has `$fillable`, type casts (e.g., `GenerationProvider::is_active → bool`), and named relationships (`Category::styles`, `Style::layouts`, `PromptTemplate::product`/`category`/`style`/`layout`). Pivot models `CategoryStyle`, `StyleLayout` exist with `belongsToMany` setup.
  - **Feature tests:** `Models\CatalogTest::test_category_styles_relationship`, `Models\CatalogTest::test_style_layouts_relationship`, `Models\CatalogTest::test_prompt_template_4tuple_lookup`
  - **Traces:** database-schema.md catalog section, US-7.2–7.6

### Phase 2.2: Identity models

- [x] **Task:** Update `User` model; add `OAuthAccount` model.
  - **Acceptance criteria:** `User` has new `$fillable` entries (`google_id`, `is_admin`, `credit_balance`) and relationships: `oauthAccounts()`, `creditTransactions()`, `projects()`, `generations()` (denormalized). `OAuthAccount` has `$fillable`, `belongsTo(User)`, casts for `token_expires_at`.
  - **Feature tests:** `Models\UserTest::test_credit_transactions_relationship`, `Models\OAuthAccountTest::test_provider_composite_lookup`
  - **Traces:** database-schema.md `users`/`oauth_accounts`, US-1.1, US-1.3

### Phase 2.3: Project lifecycle models

- [x] **Task:** Create models for `Project`, `ProjectPhoto`, `Pose`, `PoseStatus`, `Generation`, `AuditLog`. Keep the legacy `SourceImage` model only as a no-op relic (the DB now uses `project_photos`; `projects.source_image_id` was dropped in migration `2026_07_15_000001_drop_source_image_id_from_projects_table.php`).
  - **Acceptance criteria:** `Project` uses `SoftDeletes`; casts `inputs` and `first_generated_at`; relationships: `user()`, `product()`, `category()`, `style()`, `layout()`, `mode()`, `status()`, `photos()`, `generations()`. `Generation` casts `constraints_snapshot` JSON; relationships to `project`, `user`, `status`, `provider`; helper scopes `scopeCompleted()`, `scopeFailed()`, `scopeProcessing()`. `AuditLog` has polymorphic `target()` morphTo, `actor()` belongsTo User.
  - **Feature tests:** `Models\ProjectTest::test_soft_deletes`, `Models\ProjectTest::test_photos_relationship`, `Models\GenerationTest::test_status_scopes`, `Models\AuditLogTest::test_polymorphic_target`
  - **Traces:** database-schema.md project lifecycle, US-3.1, US-4.x, US-8.3

### Phase 2.4: CreditLedger service

- [x] **Task:** Implement `App\Services\CreditLedger` as the single write path.
  - **Acceptance criteria:** Public API: `signupGrant(User): void`, `debit(User, Generation, int $amount = 1): CreditTransaction`, `refund(Generation, string $reason): CreditTransaction`, `adminGrant(User, int $amount, string $notes, ?User $actor = null): CreditTransaction`. Every method runs inside `DB::transaction`, writes one `credit_transactions` row, updates `users.credit_balance` to `balance_after`, and prevents double-debit / double-refund per idempotency key (US-8.2). Refuses to operate if `credit_balance` would go negative.
  - **Feature tests:** `Services\CreditLedgerTest::test_signup_grant_writes_ledger_row`, `Services\CreditLedgerTest::test_debit_atomic_with_balance`, `Services\CreditLedgerTest::test_refund_increments_balance`, `Services\CreditLedgerTest::test_refund_is_idempotent`, `Services\CreditLedgerTest::test_admin_grant_stores_notes`, `Services\CreditLedgerTest::test_debit_refuses_negative_balance`
  - **Traces:** database-schema.md `credit_transactions`, US-2.x, US-4.1, US-4.3, US-7.7, US-8.2

### Phase 2.5: Authorization policies

- [x] **Task:** Create `ProjectPolicy`, `GenerationPolicy`, `EnsureAdmin` middleware.
  - **Acceptance criteria:** `ProjectPolicy::view/update/delete` returns true only for owner or admin. `GenerationPolicy::view/download` returns true only for owner (via project) or admin. `EnsureAdmin` middleware returns 403 when `auth()->user()->is_admin !== true`. Middleware alias `admin` is registered.
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

- [x] **Task:** Install Laravel Socialite; add `/auth/{provider}` and `/auth/{provider}/callback` routes; create `OAuthAccount` on first login; create `User` with random password if email is new.
  - **Acceptance criteria:** Clicking "Continue with Google" redirects to Google. Callback either links to existing user (no double-grant) or creates new user (grants 5 credits) and creates `oauth_accounts` row. Cancel returns to login with no error.
  - **Feature tests:** `Auth\GoogleOAuthTest::test_new_user_creates_account_and_grant`, `Auth\GoogleOAuthTest::test_existing_email_links_account_without_double_grant`, `Auth\GoogleOAuthTest::test_cancel_returns_to_login`
  - **Traces:** US-1.3

### Phase 3.3: Login / Logout / Forgot password (Fortify defaults)

- [x] **Task:** Verify Fortify routes expose login, register, forgot-password, reset-password; style Flux pages to match Kindred Canvas branding; add "Continue with Google" button to login/register pages.
  - **Acceptance criteria:** Reset link valid 60 min. Logout invalidates session. "Continue with Google" button visible on both login and register pages.
  - **Feature tests:** `Auth\PasswordResetTest::test_link_valid_60_minutes`, `Auth\LogoutTest::test_session_invalidated`
  - **Traces:** US-1.2, US-1.4, US-1.5

### Phase 3.4: Login throttle coverage

- [ ] **Task:** Add an explicit login-throttle feature test (Fortify's defaults are configured but the test that US-1.2 mentions is missing).
  - **Acceptance criteria:** `Auth\LoginThrottleTest::test_5_failures_throttle` asserts that after 5 failures within 10 min the 6th attempt is rate-limited.
  - **Feature tests:** `Auth\LoginThrottleTest::test_5_failures_throttle`
  - **Traces:** US-1.2

---

## Phase 4: Onboarding & Credit UI

**Goal:** Dashboard shows credit balance; "Credits" page lists history; both update live. · **Depends on:** Phase 3 · **Covers:** US-2.1, US-2.2

### Phase 4.1: Dashboard credit widget

- [x] **Task:** Replace default `dashboard.blade.php` with a Kindred Canvas dashboard layout containing a credit balance widget.
  - **Acceptance criteria:** Dashboard header shows user name and live `credit_balance`. Layout uses Flux components consistent with starter kit. Authenticated-only; redirects guests. Includes out-of-credits copy and a "View history" CTA.
  - **Feature tests:** `Feature\DashboardTest::test_shows_user_name_and_credit_balance`, `Feature\DashboardTest::test_zero_balance_copy_visible`
  - **Traces:** US-2.1

### Phase 4.2: Credits history page

- [x] **Task:** Create `pages::credits.index` Livewire page + route `/credits`; list user's `credit_transactions` newest-first with reason label, delta, balance_after, linked reference.
  - **Acceptance criteria:** Page is paginated (25/page). Admin grant rows show the `notes` text. Reference links go to the relevant project or generation when applicable. Future-proofed so `subscription_credit_grant` rows can be displayed alongside the existing ones.
  - **Feature tests:** `Feature\CreditsHistoryTest::test_lists_user_transactions_newest_first`, `Feature\CreditsHistoryTest::test_admin_grant_notes_visible`, `Feature\CreditsHistoryTest::test_reference_links_to_project`
  - **Traces:** US-2.2

---

## Phase 5: Admin Back-Office

**Goal:** `/admin` is gated, lists metrics, and provides CRUD for every catalog entity and (later) subscription plans. · **Depends on:** Phase 2 · **Covers:** US-7.1–7.8, US-8.3 (admin-side; billing-specific admin views live in Phase 9)

### Phase 5.1: Admin gate + layout + nav

- [x] **Task:** `EnsureAdmin` middleware + `admin` layout + `/admin` route group + conditional nav link.
  - **Acceptance criteria:** `/admin` returns 403 for non-admins. "Admin" link hidden for non-admins. Admin layout uses Flux sidebar with Overview / Catalog / People sections + sticky topbar + "Back to app" link. Admin dashboard renders 4 metric tiles (users, generations, credits in circulation, catalog coverage) and an audit log preview table.
  - **Feature tests:** `Feature\Admin\AccessGateTest::test_non_admin_gets_403`, `Feature\Admin\AccessGateTest::test_admin_link_visibility`, `Feature\Admin\AccessGateTest::test_metrics_render`
  - **Traces:** US-7.1, US-7.8

### Phase 5.2: Admin Products CRUD

- [x] **Task:** `Livewire\Admin\Products\{Index,Create,Edit}` + routes; form captures all print spec fields; `AuditLogger` integration.
  - **Acceptance criteria:** Admins can create/edit/deactivate a product. Slug unique. Deactivating a product hides it from the user wizard but does not touch historical projects.
  - **Feature tests:** `Feature\Admin\ProductsTest::test_admin_can_create_product`, `Feature\Admin\ProductsTest::test_slug_unique`, `Feature\Admin\ProductsTest::test_deactivation_hides_from_wizard`
  - **Traces:** US-7.2

### Phase 5.3: Admin Categories CRUD + style associations

- [x] **Task:** `Livewire\Admin\Categories\{Index,Create,Edit}` with many-to-many style picker.
  - **Acceptance criteria:** Admins can create/edit categories with thumbnail upload to S3; assign/unassign styles; slug unique per product.
  - **Feature tests:** `Feature\Admin\CategoriesTest::test_admin_can_create_category`, `Feature\Admin\CategoriesTest::test_style_associations_persist`
  - **Traces:** US-7.3

### Phase 5.4: Admin Styles CRUD + category associations

- [x] **Task:** `Livewire\Admin\Styles\{Index,Create,Edit}` with category picker.
  - **Acceptance criteria:** Edit `prompt_fragment` and category associations; thumbnail upload.
  - **Feature tests:** `Feature\Admin\StylesTest::test_admin_can_edit_prompt_fragment`
  - **Traces:** US-7.4

### Phase 5.5: Admin Layouts CRUD + safe-area JSON editor

- [x] **Task:** `Livewire\Admin\Layouts\{Index,Create,Edit}` with safe-area JSON editor and style associations.
  - **Acceptance criteria:** Admins can edit `safe_area_overlay` JSON; preview image upload; style associations.
  - **Feature tests:** `Feature\Admin\LayoutsTest::test_safe_area_overlay_persists`
  - **Traces:** US-7.5

### Phase 5.6: Admin Prompt Templates editor

- [x] **Task:** `Livewire\Admin\PromptTemplates\{Index,Create,Edit}` keyed by the 4-tuple; textarea renders `{{placeholders}}` with placeholder hints; saving bumps `version`.
  - **Acceptance criteria:** Saving a template increments its `version` and is effective for the next generation immediately.
  - **Feature tests:** `Feature\Admin\PromptTemplatesTest::test_save_bumps_version`, `Feature\Admin\PromptTemplatesTest::test_4tuple_uniqueness`
  - **Traces:** US-7.6

### Phase 5.7: Admin Users + grant credits

- [x] **Task:** `Livewire\Admin\Users\{Index}` with grant-credits modal and toggle-admin action; self-demotion prevented.
  - **Acceptance criteria:** Granting credits writes a `credit_transactions` row via `CreditLedger::adminGrant` with `notes` text. Toggling admin on self shows an error and does not change.
  - **Feature tests:** `Feature\Admin\UsersTest::test_grant_credits_writes_ledger_row`, `Feature\Admin\UsersTest::test_self_demotion_blocked`
  - **Traces:** US-7.7

### Phase 5.8: Admin Audit Log viewer + Metrics polish

- [x] **Task:** `Livewire\Admin\AuditLog\Index` view + filter by actor/action; admin dashboard tiles augmented with active subscriptions count and MRR (so the panel covers US-7.8 + the billing-aware slice).
  - **Acceptance criteria:** Audit log page lists entries newest-first, filterable. Dashboard shows active subs and MRR when Stripe plans exist (gracefully degrades to "0 plans" otherwise).
  - **Feature tests:** `Feature\Admin\AuditLogTest::test_viewer_lists_entries`, `Feature\Admin\AuditLogWriteTest::test_action_persisted`, `Feature\Admin\MetricsTest::test_subscriptions_and_mrr_render_when_plans_exist`
  - **Traces:** US-7.8, US-8.3

---

## Phase 6: Project Creation (single-page configurator v2)

**Goal:** Authenticated users reach a single-page configurator that exposes Category, Style, Layout, Poses, Photos, Inputs, Mode, Custom Prompt, and a Generate CTA — replacing the v1 7-step wizard. The configurator creates a `projects` row on mount and increments it as the user makes selections; the Generation pipeline is unchanged. · **Depends on:** Phase 1.6, Phase 2.3 · **Covers:** US-3.1–3.7, US-6.1

> **Note:** This phase is delivered by the `project-wizard-v2` feature (PHASES.md at `.spec/features/project-wizard-v2/PHASES.md`). Items below summarize the integration with the canonical build.

### Phase 6.1: Configurator shell

- [x] **Task:** `Livewire\Projects\Configurator` mounted at `/projects/new`; sticky live-preview aside (desktop) / top accordion (mobile); sticky footer with Generate CTA.
  - **Acceptance criteria:** First selection creates a `projects` row in `draft` status with `mode_id`. Live preview updates within ~250 ms of any selection change.
  - **Feature tests:** `Feature\Projects\WizardStartTest::test_creates_draft_project_on_mount`
  - **Traces:** US-3.1, US-6.1

### Phase 6.2: Selector blocks (Category, Style, Layout, Pose)

- [x] **Task:** Card-based selectors for the four blocks; style filtered by category; layout filtered by style; pose picker loaded when `subject_type ∈ {Casal, Família}`.
  - **Acceptance criteria:** Each block shows active rows for the current product; updates `projects` row in the same request.
  - **Feature tests:** `Feature\Projects\WizardStepsTest::test_category_lists_active_mug_categories`, `Feature\Projects\WizardStepsTest::test_style_filtered_by_category`, `Feature\Projects\WizardStepsTest::test_layout_filtered_by_style`
  - **Traces:** US-3.2, US-3.3, US-3.4

### Phase 6.3: Photos, Inputs, Mode, Custom Prompt

- [x] **Task:** Livewire file upload to S3 (jpeg/png/webp ≤ 10MB); inputs JSON persisted; mode selector (Free/Mug) persisted; `custom_prompt` text ≤ 500 chars.
  - **Acceptance criteria:** Skip photo is allowed. Validation rejects oversized/wrong-MIME files with a clear error.
  - **Feature tests:** `Feature\Projects\SourceImageUploadTest::test_accepts_valid_image`, `Feature\Projects\SourceImageUploadTest::test_rejects_oversized_file`, `Feature\Projects\SourceImageUploadTest::test_rejects_invalid_mime`
  - **Traces:** US-3.5, US-3.6

### Phase 6.4: Review, Generate gate, dynamic prompt

- [x] **Task:** The sticky Generate CTA calls `SubmitGeneration` (Phase 7.3); Generate disabled when `credit_balance == 0` with a tooltip offering a path to subscribe (CTA → `/billing/plans`).
  - **Acceptance criteria:** Generate disabled state is honored; clicking the CTA runs the credit-debit pipeline and routes to the new generation.
  - **Feature tests:** `Feature\Projects\WizardReviewTest::test_generate_disabled_when_no_credits`, `Feature\Projects\WizardReviewTest::test_generate_disabled_shows_subscribe_cta_when_no_plan`
  - **Traces:** US-3.7, US-9.1 (cross-link)

---

## Phase 7: AI Generation Pipeline

**Goal:** Clicking "Generate" debits a credit, dispatches a job, processes it through the `GenerationProvider` interface, stores the result on S3, broadcasts status updates, and refunds on failure. · **Depends on:** Phase 2.4, Phase 6 · **Covers:** US-4.1–4.5, US-8.2

### Phase 7.1: GenerationProvider contract + provider registry

- [x] **Task:** `App\Contracts\GenerationProvider` with `generate(prompt, constraints, ?SourceImage): GenerationResult`. Adapters: `OpenAIProvider` (active), `GeminiProvider`, `ReplicateProvider` (stubs).
  - **Acceptance criteria:** Interface has one method; all three adapters implement it. Switching `GENERATION_PROVIDER=gemini` swaps the active adapter without code changes.
  - **Feature tests:** `Services\Generation\ProviderRegistryTest::test_resolves_active_provider_from_config`, `Services\Generation\ProviderRegistryTest::test_interface_contract_holds_for_all_adapters`
  - **Traces:** US-4.5

### Phase 7.2: PromptTemplate renderer + constraints snapshot

- [x] **Task:** `PromptAssembler::assemble(Project): (string $prompt, array $constraints)`. Mug mode includes print specs; Free mode omits.
  - **Acceptance criteria:** Missing placeholder throws a descriptive exception. All five `{{placeholders}}` are substituted.
  - **Feature tests:** `Services\PromptAssemblerTest::test_substitutes_all_placeholders`, `Services\PromptAssemblerTest::test_mug_mode_includes_print_specs`, `Services\PromptAssemblerTest::test_free_mode_omits_print_specs`
  - **Traces:** US-4.5, US-6.1

### Phase 7.3: GenerateArtworkJob + Submission action

- [x] **Task:** `Actions\Generation\SubmitGeneration` (called from the configurator) + `Jobs\GenerateArtworkJob`. SubmitGeneration runs inside DB transaction: creates Generation with `idempotency_key`, calls `CreditLedger::debit`, dispatches the job. Job: success → `markCompleted` + S3 upload; failure → `markFailed` + `CreditLedger::refund`. Job has `tries=3`.
  - **Acceptance criteria:** Refuses if `credit_balance < 1` (HTTP 422). Job is idempotent against the ledger. On success, Generation has `result_path` etc. On failure, the refund ledger row exists exactly once.
  - **Feature tests:** `Actions\SubmitGenerationTest::test_refuses_when_no_credits`, `Actions\SubmitGenerationTest::test_creates_generation_and_debits`, `Jobs\GenerateArtworkJobTest::test_success_path_persists_result`, `Jobs\GenerateArtworkJobTest::test_failure_path_refunds_credit`
  - **Traces:** US-4.1, US-4.3, US-4.4, US-8.2

### Phase 7.4: GenerationUpdated broadcast + Reverb

- [ ] **Task:** `Events\GenerationUpdated` broadcast on `private-user.{userId}` Reverb channel. **DEFERRED** per project's vertical-slice decision in `HANDOFF.md` (Phase 7.5 currently polls).
  - **Acceptance criteria:** When reintroduced, broadcasting uses `config/broadcasting.php` Reverb connection; event implements `ShouldBroadcast`; subscribing user receives the update with the latest Generation state.
  - **Feature tests:** `Events\GenerationUpdatedTest::test_broadcasts_on_private_user_channel`
  - **Traces:** US-4.2
  - **Status (2026-07-15):** Deferred. Note in `DeferredWork::LiveUpdates`.

### Phase 7.5: Live status UI on project page

- [x] **Task:** `Livewire\Projects\Show` displays the latest generation + history list; status updates via `wire:poll` (polling fallback). Retry button (creates a new Generation via `SubmitGeneration`).
  - **Acceptance criteria:** UI shows the latest status; if a generation is `failed`, a Retry button is rendered. Fallback polling returns the correct status on page reload.
  - **Feature tests:** `Feature\Projects\ShowTest::test_displays_latest_completed_inline`, `Feature\Projects\LiveStatusTest::test_fallback_polling_returns_correct_status`
  - **Traces:** US-4.2, US-5.1

---

## Phase 8: Results, Download, Project Management

**Goal:** Completed generations are viewable and downloadable; projects can be soft-deleted with a 30-day grace period. · **Depends on:** Phase 7 · **Covers:** US-5.1, US-5.2, US-5.3

### Phase 8.1: Download streaming endpoint

- [x] **Task:** `Http\Controllers\Generations\DownloadController` with `GET /generations/{generation}/download`; authorizes via `GenerationPolicy`; streams from S3.
  - **Acceptance criteria:** Owner or admin only. Missing S3 object renders "File unavailable" page (no 500).
  - **Feature tests:** `Feature\Generations\DownloadTest::test_owner_can_download`, `Feature\Generations\DownloadTest::test_non_owner_gets_403`, `Feature\Generations\DownloadTest::test_missing_file_renders_graceful_view`
  - **Traces:** US-5.2

### Phase 8.2: Soft delete + 30-day purge

- [x] **Task:** Project delete action with confirmation modal; `projects:purge-deleted` scheduled command.
  - **Acceptance criteria:** Soft-deleted projects vanish from the dashboard. Purge command is idempotent and logs how many projects/files it removed.
  - **Feature tests:** `Feature\Projects\DeleteTest::test_soft_delete_hides_from_dashboard`, `Console\PurgeDeletedProjectsTest::test_purges_only_older_than_30_days`
  - **Traces:** US-5.3

---

## Phase 9: Stripe Recurring Billing (Cashier + Webhooks)

**Goal:** Admins manage subscription plans at `/admin/plans`; users browse `/billing/plans`, subscribe via Stripe Checkout, manage via Stripe's Billing Portal; credits flow back into the existing `credit_transactions` ledger each cycle through verified, idempotent webhooks. · **Depends on:** Phase 1, Phase 2.4, Phase 5.8 · **Covers:** US-8.1–8.4, US-9.1–9.8, US-10.1–10.5, US-11.1 (subs side), US-11.4

> **Note:** This phase consumes the deliverable in `.spec/features/stripe-subscription-billing/PHASES.md` (16 tasks, 11 phases). The numbering below corresponds 1:1 with that PHASES.md; the tasks here are summary stubs so `scripts/ralph.sh` can drive the work end-to-end.

### Phase 9.1: Cashier foundation + lookup tables

- [ ] **Task:** Install `laravel/cashier`; publish Cashier migrations; create migrations for `subscription_intervals` and `subscription_statuses`; seed them; extend `CatalogSeeder` to seed the `subscription_credit_grant` reason and the `edit_plan` audit action.
  - **Acceptance criteria:** `composer.json` requires `laravel/cashier:^15`. New migrations up + down cleanly. Catalog seeder remains idempotent. `SubscriptionStatus` and `SubscriptionInterval` lookups mirror Stripe's strings.
  - **Feature tests:** `Schema\BillingLookupsTest::test_seed_rows_match_spec`, `Models\BillingLookupsTest::test_status_slug_mirrors_stripe_keys`
  - **Traces:** database-schema.md §1 + §6, US-8.2 (audit), US-11.4 (env keys)

### Phase 9.2: Subscription plan + stripe_events schemas

- [ ] **Task:** Migrations for `subscription_plans` (with `stripe_product_id` / `stripe_price_id`, `sort_order`), `subscriptions` (with `pending_plan_id`, `cancel_at_period_end`), `stripe_events` (`stripe_event_id` unique).
  - **Acceptance criteria:** All FKs + unique indexes per DBML. `subscription_plans.slug` is unique; `(is_active, sort_order)` index exists. `stripe_events.stripe_event_id` is unique.
  - **Feature tests:** `Schema\BillingSchemaTest::test_subscription_plans_unique_slug`, `Schema\BillingSchemaTest::test_stripe_event_idempotency_unique`
  - **Traces:** database-schema.md §6, US-8.1, US-10.2

### Phase 9.3: Subscription model + Cashier relations

- [ ] **Task:** `App\Models\Subscription` (resolves the Cashier namespace collision by extending or transparently forwarding to the vendor model — see Open Question in the feature SPEC); `App\Models\SubscriptionPlan`; `App\Models\StripeEvent`. Wire `User` to implement `Laravel\Cashier\Billable` (this is the only allowed edition to the user migration in this phase).
  - **Acceptance criteria:** `$user->subscriptions`, `$user->subscribedToPrice(...)`, `$user->subscription()` all return the same row. `Subscription->plan()` resolves correctly. `Subscription->pendingPlan()` returns the scheduled-downgrade target when set.
  - **Feature tests:** `Models\SubscriptionTest::test_user_subscriptions_relationship`, `Models\SubscriptionPlanTest::test_sort_order_index_used_by_public_listing`
  - **Traces:** database-schema.md §6, US-11.1

### Phase 9.4: CreditLedger::subscriptionGrant

- [ ] **Task:** Extend `App\Services\CreditLedger` with `subscriptionGrant(User $user, Subscription $subscription, int $credits, ?int $periodEndTimestamp = null): CreditTransaction`. Idempotency check on `(subscription_id, period_end_timestamp_or_fallback)` plus a primary guard via `stripe_events` (Phase 9.8). Reason slug `subscription_credit_grant`. Atomic balance update.
  - **Acceptance criteria:** Same-period grant is a no-op (returns existing row). Different-period grant writes one row. Negative balance is never reachable.
  - **Feature tests:** `Services\CreditLedgerTest::test_subscription_grant_writes_ledger_row`, `Services\CreditLedgerTest::test_subscription_grant_idempotent_on_same_period`, `Services\CreditLedgerTest::test_subscription_grant_increments_balance`
  - **Traces:** US-9.3, US-11.2

### Phase 9.5: Checkout + Billing Portal actions

- [ ] **Task:** `StartSubscriptionCheckoutAction` (Cashier `SubscriptionBuilder`, returns Stripe Checkout URL) + `StartBillingPortalAction` (Cashier `portalSession`); wire to two POST endpoints (`/billing/checkout` + `/billing/portal`) guarded by auth.
  - **Acceptance criteria:** Both actions return URLs that redirect immediately. Plan lookup is by slug; missing plan → 422.
  - **Feature tests:** `Actions\StartSubscriptionCheckoutTest::test_returns_checkout_url_for_active_plan`, `Actions\StartSubscriptionCheckoutTest::test_inactive_plan_returns_422`, `Actions\StartBillingPortalTest::test_returns_portal_url`
  - **Traces:** US-9.2, US-9.4

### Phase 9.6: Public Plans Livewire + routes

- [ ] **Task:** `Livewire\Billing\Plans\Index` at `/billing/plans`; `Livewire\Billing\Index` at `/billing`; Flux-UI cards. Livewire `Billing\Index` reads `$user->subscription()` and shows the live balance + next billing date; renders the "Gerenciar assinatura" button.
  - **Acceptance criteria:** Inactive plans hidden. Active plan highlighted if user already subscribes. Guest → redirect to login.
  - **Feature tests:** `Feature\Billing\PlansIndexTest::test_only_active_plans_listed`, `Feature\Billing\PlansIndexTest::test_guest_redirected_to_login`, `Feature\Billing\IndexTest::test_shows_current_subscription_and_balance`
  - **Traces:** US-9.1, US-9.7, US-9.8
  - **Design ref:** `.spec/init/design/screens.md#billing-plans`, `.spec/init/design/screens.md#billing-current`

### Phase 9.7: Webhook controller + dispatcher + handler actions

- [ ] **Task:** Listener-driven webhook handling via Cashier's published events: extend `SubscriptionPaymentSucceeded`, `SubscriptionPaymentFailed`, `CustomerSubscriptionUpdated`, `CustomerSubscriptionDeleted` listeners to (a) call `subscriptionGrant` on success, (b) flip `stripe_status` on failure, (c) update `pending_plan_id` and `current_period_*` on update, (d) mark `canceled` on delete. Each listener records via `StripeEvent` log inside an idempotent guard.
  - **Acceptance criteria:** `Cashier::fake()` + simulated events produce exactly one ledger row per `(subscription, period)` pair and exactly one status flip per Stripe state change.
  - **Feature tests:** `Listeners\HandleSubscriptionPaymentSucceededTest::test_grants_credits_and_advances_period`, `Listeners\HandleSubscriptionPaymentSucceededTest::test_repeated_event_is_noop`, `Listeners\HandleSubscriptionPaymentFailedTest::test_flips_to_past_due`, `Listeners\HandleCustomerSubscriptionUpdatedTest::test_pending_plan_id_synced`, `Listeners\HandleCustomerSubscriptionDeletedTest::test_marks_canceled`
  - **Traces:** US-9.3, US-9.5, US-9.6, US-9.7, US-9.8, US-10.3, US-10.4, US-10.5, US-11.2

### Phase 9.8: Webhook route + CSRF exemption

- [ ] **Task:** Route `POST /stripe/webhook` → Cashier's `WebhookController` (or our thin wrapper that calls our listener pipeline). Exempt from CSRF in `bootstrap/app.php`. Signature verification is Cashier's default; reject unsigned/invalid with HTTP 400 before any DB write.
  - **Acceptance criteria:** `services.stripe.webhook_secret` resolves from `STRIPE_WEBHOOK_SECRET`. Unsigned POST returns 400 + writes nothing. `StripeEvent` row exists for every verified event.
  - **Feature tests:** `Feature\Billing\WebhookSecurityTest::test_unsigned_post_rejected_with_400`, `Feature\Billing\WebhookSecurityTest::test_signed_post_writes_stripe_event`
  - **Traces:** US-10.1, US-10.2, US-11.4

### Phase 9.9: EnsureStripePriceAction + admin Plans CRUD

- [ ] **Task:** `Actions\EnsureStripePriceAction` (creates Stripe Product + Price on first save, stores ids on the row). `Livewire\Admin\Plans\{Index,Create,Edit}` under `/admin/plans`; `AuditLog` integration via existing `AuditLogger` service.
  - **Acceptance criteria:** Create form validates `credits_per_period > 0`, `price_cents > 0`, `currency ∈ {BRL}` (locked). On save, Stripe ids persist. On Stripe API failure, the local row is not committed and the admin sees the Stripe error message. Toggling `is_active = false` hides the plan from `/billing/plans` and never auto-downgrades existing subscribers (US-8.3 / SPEC §Q-04).
  - **Feature tests:** `Actions\EnsureStripePriceTest::test_creates_product_and_price_on_first_save`, `Feature\Admin\PlansTest::test_admin_can_create_plan`, `Feature\Admin\PlansTest::test_create_writes_audit_log`, `Feature\Admin\PlansTest::test_deactivating_plan_hides_from_public_listing`
  - **Traces:** US-8.1, US-8.2, US-8.3, US-9.1
  - **Design ref:** `.spec/init/design/screens.md#admin-plans`

### Phase 9.10: Admin Subscriptions viewer + SubscriptionPolicy

- [ ] **Task:** `Livewire\Admin\Subscriptions\Index` at `/admin/subscriptions` listing every subscriber (user email, plan name, status, period start/end, ends_at). `App\Policies\SubscriptionPolicy` returning false for cross-user access; `EnsureUserOwnsSubscription` middleware on `/billing`, `/billing/checkout`, `/billing/portal`.
  - **Acceptance criteria:** A non-admin probing another user's subscription id gets 403. Status filter narrows the list.
  - **Feature tests:** `Feature\Admin\SubscriptionsTest::test_lists_all_subscriptions_with_user_and_plan`, `Feature\Admin\SubscriptionsTest::test_status_filter_narrows_list`, `Policies\SubscriptionPolicyTest::test_non_admin_cannot_view_another_users_subscription`
  - **Traces:** US-8.4, US-11.1
  - **Design ref:** `.spec/init/design/screens.md#admin-subscriptions`

### Phase 9.11: Pest test suite + final wiring

- [ ] **Task:** End-to-end integration tests covering: subscribe → `invoice.payment_succeeded` → credits land → user spends them → next cycle credits land; past-due path keeps access; cancel path; upgrade → prorate + immediate credit; downgrade → next-cycle credit; double-webhook replay → ledger unchanged; unsigned webhook → 400 + no state change.
  - **Acceptance criteria:** Every acceptance criterion for US-9.x and US-10.x is asserted by at least one Pest test using `Cashier::fake()` / `Stripe::fake()` (no live network).
  - **Feature tests:** `Feature\Billing\EndToEndTest::test_subscribe_grants_first_credits`, `Feature\Billing\EndToEndTest::test_repeated_webhook_is_noop`, `Feature\Billing\EndToEndTest::test_upgrade_applies_immediately`, `Feature\Billing\EndToEndTest::test_downgrade_takes_effect_at_period_end`, `Feature\Billing\EndToEndTest::test_cancel_keeps_access_then_drops_grants`
  - **Traces:** US-9.x, US-10.x, US-11.4

---

## Deferred Work (post-MVP / out of scope)

**Goal:** Capture what is intentionally not built (or built partial) so it can be scoped later. · **Depends on:** none for placement; relevant features are tagged inline below

- [ ] **DeferredWork::LiveUpdates** — Reverb-driven `GenerationUpdated` broadcast (Phase 7.4 is partial; polling fallback exists). Picked back up when project re-enables Reverb.
  - **Traces:** US-4.2
- [ ] **DeferredWork::Mockups** — Composite generated artwork onto mug mockup templates; produce CMYK press-ready PDFs.
  - **Traces:** US-5.x mockups (future)
- [ ] **DeferredWork::TopUpWithoutSubscription** — One-time credit purchases without an active subscription. The subscription in Phase 9 covers the only monetization surface in MVP.
  - **Traces:** US-2.x top-up (future)
- [ ] **DeferredWork::CouponsPromos** — Stripe Coupon / Promotion Code support on Checkout.
  - **Traces:** Future
- [ ] **DeferredWork::AutoTrialUI** — App surfaces the trial that's configured on the Stripe Price; the app does not advertise or auto-apply trials in MVP.
  - **Traces:** Future
- [ ] **DeferredWork::InAppRefund** — Admins perform refunds via the Stripe Dashboard; the app does not offer an in-app refund flow.
  - **Traces:** Future
- [ ] **DeferredWork::MultiCurrency** — `currency` is locked to BRL on `subscription_plans` in MVP. Multi-currency support is deferred.
  - **Traces:** Future
- [ ] **DeferredWork::InvoiceSurface** — Stripe emails the user invoices; the app does not mirror them in-app in MVP.
  - **Traces:** Future
- [ ] **DeferredWork::MultiProduct** — Expose non-mug products in the wizard UI; per-product print specs are already in the DB schema.
  - **Traces:** US-7.2 (product management), US-3.1 (wizard product step)
- [ ] **DeferredWork::SourceImageAnalysis** — Replace the "pass verbatim" step with an in-house vision call that tags the image (people/animals/quality); tags feed into the prompt.
  - **Traces:** US-3.5

---

## Appendix A: Story → Phase Coverage

| Story | Phase |
|---|---|
| US-1.1 Email Registration | Phase 1.1, 2.2, 3.1 |
| US-1.2 Email Login | Phase 3.3, 3.4 |
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
| US-3.6 Fill User Inputs | Phase 6.3 |
| US-3.7 Review and Submit | Phase 6.4, 7.3 |
| US-4.1 Submit Generation | Phase 7.3 |
| US-4.2 Live Status Updates | Phase 7.4, 7.5 |
| US-4.3 Automatic Refund on Failure | Phase 7.3 |
| US-4.4 Regenerate | Phase 7.3 |
| US-4.5 Provider Abstraction | Phase 7.1 |
| US-5.1 View Artwork | Phase 7.5, 8.1 |
| US-5.2 Download Artwork | Phase 8.1 |
| US-5.3 Delete Project | Phase 8.2 |
| US-6.1 Choose Mode | Phase 6.1, 7.2 |
| US-7.1 Admin Gate | Phase 5.1 |
| US-7.2 Manage Products | Phase 5.2 |
| US-7.3 Manage Categories | Phase 5.3 |
| US-7.4 Manage Styles | Phase 5.4 |
| US-7.5 Manage Layouts | Phase 5.5 |
| US-7.6 Manage Prompt Templates | Phase 5.6 |
| US-7.7 Manage Users | Phase 5.7 |
| US-7.8 View Metrics | Phase 5.1, 5.8 |
| US-8.1 View Subscription Plans | Phase 9.9 |
| US-8.2 Create a Plan | Phase 9.9 |
| US-8.3 Edit a Plan | Phase 9.9 |
| US-8.4 View All Subscriptions (Admin) | Phase 9.10 |
| US-9.1 Browse Public Plans | Phase 6.4, 10.6 |
| US-9.2 Subscribe via Stripe Checkout | Phase 9.5 |
| US-9.3 Receive Monthly Credit Grants | Phase 9.4, 10.7 |
| US-9.4 Open the Billing Portal | Phase 9.5 |
| US-9.5 Upgrade Mid-Cycle | Phase 9.7 |
| US-9.6 Downgrade at Cycle End | Phase 9.7 |
| US-9.7 Cancel at Cycle End | Phase 9.7 |
| US-9.8 Past-Due State | Phase 9.7, 10.6 |
| US-10.1 Webhook Signature Verification | Phase 9.8 |
| US-10.2 Webhook Idempotency | Phase 9.2, 10.8 |
| US-10.3 Subscription Created | Phase 9.7 |
| US-10.4 Subscription Updated | Phase 9.7 |
| US-10.5 Subscription Deleted | Phase 9.7 |
| US-11.1 Authorization on All Routes | Phase 2.5, 10.10 |
| US-11.2 Idempotent Credit Operations | Phase 1.3, 2.4, 7.3, 10.4, 10.7 |
| US-11.3 Audit Log for Admin Actions | Phase 1.5, 2.3, 5.8, 10.9 |
| US-11.4 Stripe Sandbox Safe by Default | Phase 9.1, 10.8 |

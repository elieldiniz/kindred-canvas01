# Kindred Canvas — Database Schema

<!-- inputs: project-description.md@sha256:4fb8c4284951 user-stories.md@sha256:880bd7ad3732 -->

## Overview

The data model centers on three pillars: **identity & credits** (users, oauth_accounts, credit_transactions ledger), **the personalization catalog** (products, categories, styles, layouts, prompt_templates — all admin-managed, lookup-table-driven), and **the project lifecycle** (projects → generations → generation_artifacts). Soft deletes are applied only to `projects`; everything else uses status flags so historical data stays intact for audit and analytics. The credit system is ledger-first: `users.credit_balance` is a denormalized cache that is always kept in sync inside the same DB transaction that writes a `credit_transactions` row. AI generation is abstracted through a `generation_provider_id` lookup so swapping OpenAI/Gemini/Replicate is a config change, not a schema change.

All table names are plural snake_case. All tables have `id bigint [pk, increment]`, `created_at timestamp`, `updated_at timestamp`. No DB-level enums — every status/type/reason is a lookup table joined by `*_id`.

## Schema (DBML)

```dbml
// =====================================================
// 1. Lookup tables (no FKs into domain tables)
// =====================================================

Table generation_statuses {
  id bigint [pk, increment]
  name varchar [not null]              // "Waiting", "Processing", "Completed", "Failed"
  slug varchar [unique, not null]      // "waiting", "processing", "completed", "failed"
  created_at timestamp
  updated_at timestamp
}

Table project_statuses {
  id bigint [pk, increment]
  name varchar [not null]              // "Draft", "Active", "Archived"
  slug varchar [unique, not null]      // "draft", "active", "archived"
  created_at timestamp
  updated_at timestamp
}

Table project_modes {
  id bigint [pk, increment]
  name varchar [not null]              // "Free", "Mug"
  slug varchar [unique, not null]      // "free", "mug"
  injects_print_specs boolean [not null, default: false]
  created_at timestamp
  updated_at timestamp
}

Table product_statuses {
  id bigint [pk, increment]
  name varchar [not null]              // "Active", "Inactive"
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table category_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table style_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table layout_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table generation_providers {
  id bigint [pk, increment]
  name varchar [not null]              // "OpenAI", "Google Gemini", "Replicate"
  slug varchar [unique, not null]      // "openai", "gemini", "replicate"
  driver_class varchar [not null]      // FQCN implementing GenerationProvider
  is_active boolean [not null, default: true]
  created_at timestamp
  updated_at timestamp
}

Table color_modes {
  id bigint [pk, increment]
  name varchar [not null]              // "RGB", "CMYK"
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table credit_transaction_reasons {
  id bigint [pk, increment]
  name varchar [not null]              // "Signup Grant", "Generation Debit", "Generation Refund", "Admin Grant"
  slug varchar [unique, not null]      // "signup_grant", "generation_debit", "generation_refund", "admin_grant"
  expected_sign varchar [not null]     // "+", "-" — used by reconciliation
  created_at timestamp
  updated_at timestamp
}

Table audit_log_actions {
  id bigint [pk, increment]
  name varchar [not null]              // "Toggle Admin", "Grant Credits", "Edit Product", "Edit Category", "Edit Style", "Edit Layout", "Edit Prompt Template"
  slug varchar [unique, not null]      // "toggle_admin", "grant_credits", "edit_product", "edit_category", "edit_style", "edit_layout", "edit_prompt_template"
  created_at timestamp
  updated_at timestamp
}

// =====================================================
// 2. Identity & credits
// =====================================================

Table users {
  id bigint [pk, increment]
  name varchar [not null]
  email varchar [unique, not null]
  email_verified_at timestamp [null]
  password varchar [null]               // Nullable: Google-only users have no password
  google_id varchar [unique, null]     // Convenience FK; canonical link in oauth_accounts
  is_admin boolean [not null, default: false]
  credit_balance int [not null, default: 0]   // denormalized cache; source of truth = credit_transactions
  remember_token varchar [null]
  created_at timestamp
  updated_at timestamp

  Note 'Laravel Fortify also creates password_reset_tokens and sessions tables (not modeled here — managed by the framework).'
}

Table oauth_accounts {
  id bigint [pk, increment]
  user_id bigint [not null, ref: > users.id]
  provider varchar [not null]           // "google" — kept string for forward-compat with future providers
  provider_user_id varchar [not null]
  access_token text [null]
  refresh_token text [null]
  token_expires_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (provider, provider_user_id) [unique, name: 'oauth_accounts_provider_unique']
    (user_id) [name: 'oauth_accounts_user_id_idx']
  }
}

Table credit_transactions {
  id bigint [pk, increment]
  user_id bigint [not null, ref: > users.id]
  reason_id bigint [not null, ref: > credit_transaction_reasons.id]
  delta int [not null]                 // signed: negative = debit, positive = credit
  balance_after int [not null]         // snapshot of users.credit_balance after this row
  reference_type varchar [null]        // polymorphic: "App\\Models\\Generation", "App\\Models\\User"
  reference_id bigint [null]
  notes text [null]                    // free-text reason (used for admin_grant)
  created_at timestamp
  updated_at timestamp

  Indexes {
    (user_id, created_at) [name: 'credit_transactions_user_created_idx']
    (reference_type, reference_id) [name: 'credit_transactions_reference_idx']
  }
}

// =====================================================
// 3. Personalization catalog (admin-managed)
// =====================================================

Table products {
  id bigint [pk, increment]
  name varchar [not null]              // "Mug", future "T-Shirt", "Pillow"
  slug varchar [unique, not null]      // "mug", "t_shirt"
  status_id bigint [not null, ref: > product_statuses.id]
  // Print specs (mug defaults below; vary per product)
  print_width_mm decimal(6,2) [not null]
  print_height_mm decimal(6,2) [not null]
  min_dpi int [not null, default: 300]
  safe_area_mm decimal(6,2) [not null, default: 5.0]
  color_mode_id bigint [not null, ref: > color_modes.id]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (status_id) [name: 'products_status_idx']
  }
}

Table categories {
  id bigint [pk, increment]
  product_id bigint [not null, ref: > products.id]
  name varchar [not null]              // "Birthday", "Wedding", "Pets"
  slug varchar [not null]              // "birthday", "wedding", "pets"
  description text [null]
  thumbnail_path varchar [null]        // S3 key
  status_id bigint [not null, ref: > category_statuses.id]
  sort_order int [not null, default: 0]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (product_id, slug) [unique, name: 'categories_product_slug_unique']
    (status_id) [name: 'categories_status_idx']
  }
}

Table styles {
  id bigint [pk, increment]
  name varchar [not null]              // "Watercolor", "Cartoon", "Realistic"
  slug varchar [unique, not null]      // "watercolor", "cartoon", "realistic"
  prompt_fragment text [not null]      // appended to the prompt
  thumbnail_path varchar [null]
  status_id bigint [not null, ref: > style_statuses.id]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (status_id) [name: 'styles_status_idx']
  }
}

Table layouts {
  id bigint [pk, increment]
  name varchar [not null]              // "Centered", "Border Wrap", "Full Bleed", "Split Top-Bottom"
  slug varchar [unique, not null]
  preview_path varchar [null]          // S3 key for the visual preview
  safe_area_overlay json [null]        // { "top_mm": 5, "bottom_mm": 5, ... }
  proportion_ratio varchar [not null]   // e.g., "9:16" — stringly typed for flexibility
  status_id bigint [not null, ref: > layout_statuses.id]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (status_id) [name: 'layouts_status_idx']
  }
}

// Pivots for many-to-many relationships

Table category_styles {
  id bigint [pk, increment]
  category_id bigint [not null, ref: > categories.id]
  style_id bigint [not null, ref: > styles.id]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (category_id, style_id) [unique, name: 'category_styles_unique']
  }
}

Table style_layouts {
  id bigint [pk, increment]
  style_id bigint [not null, ref: > styles.id]
  layout_id bigint [not null, ref: > layouts.id]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (style_id, layout_id) [unique, name: 'style_layouts_unique']
  }
}

Table prompt_templates {
  id bigint [pk, increment]
  product_id bigint [not null, ref: > products.id]
  category_id bigint [not null, ref: > categories.id]
  style_id bigint [not null, ref: > styles.id]
  layout_id bigint [not null, ref: > layouts.id]
  body text [not null]                 // contains {{name}}, {{phrase}}, {{theme}}, {{image_tags}}, {{print_specs}}
  version int [not null, default: 1]    // monotonic; bumped on every save
  created_at timestamp
  updated_at timestamp

  Indexes {
    (product_id, category_id, style_id, layout_id) [unique, name: 'prompt_templates_4tuple_unique']
  }
}

// =====================================================
// 4. Project lifecycle
// =====================================================

Table projects {
  id bigint [pk, increment]
  user_id bigint [not null, ref: > users.id]
  product_id bigint [not null, ref: > products.id]
  category_id bigint [not null, ref: > categories.id]
  style_id bigint [not null, ref: > styles.id]
  layout_id bigint [not null, ref: > layouts.id]
  mode_id bigint [not null, ref: > project_modes.id]
  status_id bigint [not null, ref: > project_statuses.id]
  title varchar [null]                  // user-given or auto-generated
  inputs json [not null, default: `{}`] // { "name": ..., "phrase": ..., "theme": ..., "dedicatoria": ... }
  source_image_id bigint [null, ref: > source_images.id]   // 0..1 source image
  first_generated_at timestamp [null]   // set on first successful generation; mode becomes immutable
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp [null]           // soft delete; 30-day grace period before purge

  Indexes {
    (user_id, deleted_at) [name: 'projects_user_active_idx']
    (status_id) [name: 'projects_status_idx']
  }
}

Table source_images {
  id bigint [pk, increment]
  user_id bigint [not null, ref: > users.id]
  disk varchar [not null, default: 's3']
  path varchar [not null]               // S3 key
  original_filename varchar [not null]
  mime_type varchar [not null]          // image/jpeg | image/png | image/webp
  size_bytes bigint [not null]
  width_px int [null]
  height_px int [null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (user_id, created_at) [name: 'source_images_user_created_idx']
  }
}

Table generations {
  id bigint [pk, increment]
  project_id bigint [not null, ref: > projects.id]
  user_id bigint [not null, ref: > users.id]                  // denormalized for fast credit lookups
  status_id bigint [not null, ref: > generation_statuses.id]
  provider_id bigint [null, ref: > generation_providers.id]    // resolved at submit time
  prompt_snapshot text [not null]                             // exact prompt sent to provider (audit)
  constraints_snapshot json [not null]                        // print specs + safe area at the time
  idempotency_key varchar [unique, not null]                  // US-8.2 — guards double-charge
  result_path varchar [null]                                  // S3 key on completion
  result_mime_type varchar [null]
  result_width_px int [null]
  result_height_px int [null]
  failure_reason text [null]                                  // human-readable on failure
  credits_charged int [not null, default: 1]                  // future-proof: some generations may cost > 1
  started_at timestamp [null]                                 // when the job begins
  completed_at timestamp [null]                               // terminal state timestamp
  created_at timestamp
  updated_at timestamp

  Indexes {
    (project_id, status_id) [name: 'generations_project_status_idx']
    (user_id, created_at) [name: 'generations_user_created_idx']
  }
}

// =====================================================
// 5. Audit log
// =====================================================

Table audit_logs {
  id bigint [pk, increment]
  actor_user_id bigint [not null, ref: > users.id]            // admin who performed the action
  action_id bigint [not null, ref: > audit_log_actions.id]
  target_type varchar [not null]                              // polymorphic target class
  target_id bigint [not null]
  payload json [null]                                         // before/after snapshot
  created_at timestamp
  updated_at timestamp

  Indexes {
    (actor_user_id, created_at) [name: 'audit_logs_actor_created_idx']
    (target_type, target_id) [name: 'audit_logs_target_idx']
  }
}

// =====================================================
// 6. Laravel infrastructure tables (not modeled here,
//    created automatically by Fortify/Sanctum/queue/breeze)
// =====================================================
// - password_reset_tokens
// - sessions
// - jobs                  (database queue driver)
// - job_batches
// - failed_jobs
// - cache
// - cache_locks
```

## Relationships

- **users ↔ oauth_accounts** — one-to-many. A user may have multiple OAuth identities (Google now, more providers later). Each oauth_account links to one provider and stores the provider's `user_id` + tokens.
- **users ↔ credit_transactions** — one-to-many. Every credit movement is appended as a ledger row referencing the user. `balance_after` snapshots the running balance.
- **credit_transaction_reasons ↔ credit_transactions** — one-to-many. Each ledger row is tagged with its reason (signup_grant, generation_debit, generation_refund, admin_grant, future: topup_purchase).
- **products ↔ categories** — one-to-many. Each category belongs to one product (slug is unique per product).
- **products ↔ color_modes** — many-to-one. Print color profile is product-level.
- **categories ↔ styles (via category_styles)** — many-to-many. A category offers a curated set of styles; a style may be reused across categories.
- **styles ↔ layouts (via style_layouts)** — many-to-many. A layout may be valid for multiple styles.
- **products ↔ prompt_templates (with category/style/layout)** — composite FK (4-tuple unique). Exactly one template per Product/Category/Style/Layout combo.
- **users ↔ projects** — one-to-many. A user owns projects; soft-deleted projects are filtered via `deleted_at IS NULL`.
- **projects ↔ source_images** — one-to-one (nullable). A project has 0 or 1 source image; source image is uploaded once and can be replaced.
- **projects ↔ generations** — one-to-many. Each generation belongs to one project; re-runs create new generations (immutability).
- **generations ↔ generation_providers** — many-to-one. Provider is resolved at submit time and snapshotted on the generation row.
- **generations ↔ generation_statuses** — many-to-one. Status flows `waiting → processing → completed | failed`.
- **users ↔ audit_logs (as actor)** — one-to-many. Every admin action is recorded with the acting admin and a polymorphic target.

## Lookup Table Seeds

**generation_statuses** (`slug`, `name`):
- `waiting`, `Waiting`
- `processing`, `Processing`
- `completed`, `Completed`
- `failed`, `Failed`

**project_statuses** (`slug`, `name`):
- `draft`, `Draft`
- `active`, `Active`
- `archived`, `Archived`

**project_modes** (`slug`, `name`, `injects_print_specs`):
- `free`, `Free`, `false`
- `mug`, `Mug`, `true`

**product_statuses** (`slug`, `name`):
- `active`, `Active`
- `inactive`, `Inactive`

**category_statuses / style_statuses / layout_statuses** (`slug`, `name`):
- `active`, `Active`
- `inactive`, `Inactive`

**generation_providers** (`slug`, `name`, `driver_class`, `is_active`):
- `openai`, `OpenAI`, `App\\Services\\Generation\\OpenAIProvider`, `true`
- `gemini`, `Google Gemini`, `App\\Services\\Generation\\GeminiProvider`, `false`
- `replicate`, `Replicate`, `App\\Services\\Generation\\ReplicateProvider`, `false`

(The active provider is selected at runtime via `config('generation.provider')`; only one row ships `is_active = true` by default.)

**color_modes** (`slug`, `name`):
- `rgb`, `RGB`
- `cmyk`, `CMYK`

**credit_transaction_reasons** (`slug`, `name`, `expected_sign`):
- `signup_grant`, `Signup Grant`, `+`
- `generation_debit`, `Generation Debit`, `-`
- `generation_refund`, `Generation Refund`, `+`
- `admin_grant`, `Admin Grant`, `+`

**audit_log_actions** (`slug`, `name`):
- `toggle_admin`, `Toggle Admin`
- `grant_credits`, `Grant Credits`
- `edit_product`, `Edit Product`
- `edit_category`, `Edit Category`
- `edit_style`, `Edit Style`
- `edit_layout`, `Edit Layout`
- `edit_prompt_template`, `Edit Prompt Template`

**products (MVP seed)**:
- `mug`, `Mug`, active, 220×95 mm print area, 300 DPI, 5 mm safe area, RGB

**categories (MVP seed for `mug`)**: `birthday`, `wedding`, `pets`, `family`, `couples`, `kids` — all active.

**styles (MVP seed)**: `watercolor`, `cartoon`, `realistic`, `pixel_art`, `minimalist_line` — all active.

**layouts (MVP seed)**: `centered`, `border_wrap`, `full_bleed`, `split_top_bottom` — all active.

**category_styles / style_layouts / prompt_templates**: seeded by an admin (or by the initial seeder) for every valid 4-tuple; placeholder `body` text ships with `{{name}}`, `{{phrase}}`, `{{theme}}`, `{{image_tags}}`, `{{print_specs}}` placeholders.

## Notes & Conventions

- **No DB-level enums.** All status/type/reason fields reference a lookup table (`generation_statuses`, `project_modes`, `credit_transaction_reasons`, etc.) so admins can add values without a migration. Enums in PHP code mirror the lookup tables but the DB stays flexible.
- **Soft deletes apply only to `projects`** (30-day recovery window per US-5.3). `users` does NOT soft-delete — credit FKs and OAuth identity integrity must survive. Deactivation is via the `is_admin` flag and a future `is_active` column when added.
- **Polymorphic references** (`credit_transactions.reference_*`, `audit_logs.target_*`) use the string `reference_type` / `target_type` matching Laravel's `MorphTo` convention (e.g., `App\\Models\\Generation`). This keeps the schema generic — new reference targets do not require schema changes.
- **Idempotency** — `generations.idempotency_key` is `unique` and must be set BEFORE the credit debit transaction begins. The job handler checks for an existing ledger row referencing this generation with reason `generation_debit` before any write, satisfying US-8.2.
- **Denormalization** — `users.credit_balance` is a cache of the credit_transactions ledger. Every write to the ledger updates `credit_balance` inside the same DB transaction. A scheduled reconciliation command can recompute from the ledger and surface drift to admins.
- **Denormalization** — `generations.user_id` is duplicated from `projects.user_id` to keep credit-history queries (`SELECT * FROM credit_transactions WHERE user_id = ?`) and "my generations" listings fast. Both columns must agree.
- **Snapshot fields** — `generations.prompt_snapshot`, `generations.constraints_snapshot`, and `prompt_templates.version` together provide a full audit trail: even if an admin edits a template after a generation completes, the exact prompt used for that generation is preserved on the row.
- **Indexes** — composite indexes are named explicitly. The most-frequent query patterns are:
  - `users` by `email` (auth lookup) — `unique` index on email.
  - `projects` for "my active projects" — `(user_id, deleted_at)`.
  - `generations` for project history — `(project_id, status_id)`.
  - `credit_transactions` for user history — `(user_id, created_at)`.
  - `audit_logs` for "who did what to whom" — `(target_type, target_id)` and `(actor_user_id, created_at)`.
- **Laravel framework tables** — `password_reset_tokens`, `sessions`, `jobs`, `job_batches`, `failed_jobs`, `cache`, `cache_locks` are created by the framework and not modeled in DBML. They are listed at the bottom of the schema for awareness.
- **Naming** — table names plural snake_case; foreign keys singular with `_id` suffix; pivots named `entity1_entity2` alphabetical; lookup tables named `entity_attribute_types` style (`generation_statuses`, `credit_transaction_reasons`).
- **Timestamps** — every domain table has `created_at` and `updated_at` (Laravel convention). Soft-delete tables add `deleted_at`. Lookup tables also get timestamps so admin edits are traceable.
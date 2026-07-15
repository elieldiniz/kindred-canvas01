# Kindred Canvas — Database Schema

<!-- inputs: project-description.md@sha256:891934ac4985 user-stories.md@sha256:649171448264 -->

## Overview

The data model centers on four pillars: **identity & credits** (users, oauth_accounts, credit_transactions ledger — extended with Cashier's billable trait via `users.stripe_id`), **the personalization catalog** (products, categories, styles, layouts, prompt_templates — all admin-managed, lookup-table-driven), **the project lifecycle** (projects → generations → source_images → project_photos), and **recurring billing** (subscription_plans → users_subscriptions, mirrored from Stripe via Laravel Cashier + a `stripe_events` idempotency table for webhook replay safety). Soft deletes are applied only to `projects`; everything else uses status/active flags so historical data stays intact for audit and analytics. The credit system is ledger-first: `users.credit_balance` is a denormalized cache always kept in sync inside the same DB transaction that writes a `credit_transactions` row. AI generation is abstracted through a `generation_provider_id` lookup so swapping OpenAI/Gemini/Replicate is a config change, not a schema change.

All table names are plural snake_case. Every table has `id bigint [pk, increment]`, `created_at timestamp`, `updated_at timestamp`. No DB-level enums — every status/type/reason is a lookup table joined by `*_id`. MVP currency is BRL (only). Bounded `interval` for subscriptions is `month | year`.

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
  name varchar [not null]              // "Signup Grant", "Generation Debit", "Generation Refund", "Admin Grant", "Subscription Credit Grant"
  slug varchar [unique, not null]      // "signup_grant", "generation_debit", "generation_refund", "admin_grant", "subscription_credit_grant"
  expected_sign varchar [not null]     // "+", "-" — used by reconciliation
  created_at timestamp
  updated_at timestamp
}

Table audit_log_actions {
  id bigint [pk, increment]
  name varchar [not null]              // "Toggle Admin", "Grant Credits", "Edit Product", "Edit Category", "Edit Style", "Edit Layout", "Edit Prompt Template", "Edit Plan"
  slug varchar [unique, not null]      // "toggle_admin", "grant_credits", "edit_product", "edit_category", "edit_style", "edit_layout", "edit_prompt_template", "edit_plan"
  created_at timestamp
  updated_at timestamp
}

Table subscription_intervals {
  id bigint [pk, increment]
  name varchar [not null]              // "Monthly", "Yearly"
  slug varchar [unique, not null]      // "month", "year" — must match Stripe interval values
  created_at timestamp
  updated_at timestamp
}

Table subscription_statuses {
  id bigint [pk, increment]
  name varchar [not null]              // "Active", "Trialing", "Past Due", "Canceled", "Incomplete", "Incomplete Expired", "Unpaid", "Paused"
  slug varchar [unique, not null]      // mirrors Stripe's subscription.status keys
  created_at timestamp
  updated_at timestamp
}

// =====================================================
// 2. Identity & credits (Cashier adds users.stripe_id, etc.)
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
  // --- Cashier billable fields (added via Laravel cashier migrations) ---
  stripe_id varchar [unique, null]     // users.stripe_id — Stripe customer identifier
  pm_type varchar [null]               // Cashier default payment method type
  pm_last_four varchar [null]          // last 4 digits of the saved card
  trial_ends_at timestamp [null]       // Cashier trial support
  remember_token varchar [null]
  created_at timestamp
  updated_at timestamp

  Note 'Laravel Fortify also creates password_reset_tokens and sessions tables (not modeled here — managed by the framework). Cashier adds stripe-related columns and a payment_methods table.'
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
  reference_type varchar [null]        // polymorphic: "App\\Models\\Generation", "App\\Models\\User", "App\\Models\\Subscription"
  reference_id bigint [null]
  notes text [null]                    // free-text reason (used for admin_grant) or period hint (used for subscription_credit_grant)
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
  first_generated_at timestamp [null]   // set on first successful generation; mode becomes immutable
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp [null]           // soft delete; 30-day grace period before purge

  Indexes {
    (user_id, deleted_at) [name: 'projects_user_active_idx']
    (status_id) [name: 'projects_status_idx']
  }
}

// New: a project may attach N photos via this pivot (replaces legacy source_image_id).
Table project_photos {
  id bigint [pk, increment]
  project_id bigint [not null, ref: > projects.id]
  user_id bigint [not null, ref: > users.id]
  disk varchar [not null, default: 's3']
  path varchar [not null]                // S3 key
  original_filename varchar [not null]
  mime_type varchar [not null]           // image/jpeg | image/png | image/webp
  size_bytes bigint [not null]
  width_px int [null]
  height_px int [null]
  position int [not null, default: 0]    // ordering for multi-photo projects
  pose_id bigint [null, ref: > poses.id] // 0..1 pose hint per photo (couple/family projects)
  created_at timestamp
  updated_at timestamp

  Indexes {
    (project_id, position) [name: 'project_photos_project_position_idx']
    (user_id, created_at) [name: 'project_photos_user_created_idx']
  }
}

Table poses {
  id bigint [pk, increment]
  name varchar [not null]                // "Abraçados", "Beijo", "Sentados", ...
  slug varchar [unique, not null]
  status_id bigint [not null, ref: > pose_statuses.id]
  sort_order int [not null, default: 0]
  thumbnail_path varchar [null]
  created_at timestamp
  updated_at timestamp
}

Table pose_statuses {
  id bigint [pk, increment]
  name varchar [not null]
  slug varchar [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table generations {
  id bigint [pk, increment]
  project_id bigint [not null, ref: > projects.id]
  user_id bigint [not null, ref: > users.id]                  // denormalized for fast credit lookups
  status_id bigint [not null, ref: > generation_statuses.id]
  provider_id bigint [null, ref: > generation_providers.id]    // resolved at submit time
  prompt_snapshot text [not null]                             // exact prompt sent to provider (audit)
  constraints_snapshot json [not null]                        // print specs + safe area at the time
  idempotency_key varchar [unique, not null]                  // US-11.2 — guards double-charge
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
// 6. Recurring billing (Laravel Cashier + Stripe)
// =====================================================

Table subscription_plans {
  id bigint [pk, increment]
  name varchar [not null]               // "Starter", "Pro", "Business"
  description text [null]               // short copy shown on /billing/plans
  slug varchar [unique, not null]       // "starter", "pro", "business"
  credits_per_period int [not null]      // 50, 200, 1000, ...
  price_cents int [not null]            // 1990 = R$19,90
  currency char(3) [not null, default: 'BRL']
  interval_id bigint [not null, ref: > subscription_intervals.id]
  is_active boolean [not null, default: true]
  sort_order int [not null, default: 0] // display order on /billing/plans
  // Stripe-synced fields (populated by EnsureStripePriceAction on first save)
  stripe_product_id varchar [null]
  stripe_price_id varchar [unique, null]
  created_at timestamp
  updated_at timestamp

  Indexes {
    (is_active, sort_order) [name: 'subscription_plans_active_sort_idx']
    (slug) [unique, name: 'subscription_plans_slug_unique']
  }
}

Table subscriptions {
  id bigint [pk, increment]
  user_id bigint [not null, ref: > users.id]
  subscription_plan_id bigint [not null, ref: > subscription_plans.id] // current plan
  type varchar [not null, default: 'default']               // Cashier's `type` discriminator
  stripe_id varchar [unique, not null]                        // Cashier's `stripe_id` — Stripe Subscription id
  stripe_status varchar [not null]                            // mirrors Stripe's `subscription.status` verbatim
  current_period_start timestamp [null]
  current_period_end timestamp [null]
  ends_at timestamp [null]                                    // set when cancel_at_period_end takes effect
  cancel_at_period_end boolean [not null, default: false]
  pending_plan_id bigint [null, ref: > subscription_plans.id] // scheduled downgrade (US-9.6)
  created_at timestamp
  updated_at timestamp

  Indexes {
    (user_id, stripe_status) [name: 'subscriptions_user_status_idx']
    (subscription_plan_id) [name: 'subscriptions_plan_idx']
    (stripe_id) [unique, name: 'subscriptions_stripe_id_unique']
  }
}

// Stripe webhook idempotency: every verified event lands here keyed by Stripe `event.id`.
Table stripe_events {
  id bigint [pk, increment]
  stripe_event_id varchar [unique, not null]  // == Stripe `event.id`, e.g., "evt_..."
  type varchar [not null]                     // == Stripe `event.type`, e.g., "invoice.payment_succeeded"
  payload json [not null]                     // the entire verified payload, kept for audit
  processed_at timestamp [null]               // null while enqueued / processing; set after successful handler run
  created_at timestamp
  updated_at timestamp

  Indexes {
    (type, created_at) [name: 'stripe_events_type_created_idx']
  }
}

// =====================================================
// 7. Laravel infrastructure tables (not modeled here,
//    created automatically by Fortify/Cashier/queue/framework)
// =====================================================
// - password_reset_tokens
// - sessions
// - jobs                  (database queue driver)
// - job_batches
// - failed_jobs
// - cache
// - cache_locks
// - cashier subscriptions_items (Cashier internal)
// - cashier webhook_calls     OR stripe_events (we mirror this manually on top)
```

## Relationships

- **users ↔ oauth_accounts** — one-to-many. A user may have multiple OAuth identities (Google now, more providers later). Each oauth_account links to one provider and stores the provider's `user_id` + tokens.
- **users ↔ credit_transactions** — one-to-many. Every credit movement is appended as a ledger row referencing the user. `balance_after` snapshots the running balance. Subscription-driven grants reference `App\\Models\\Subscription` polymorphically.
- **credit_transaction_reasons ↔ credit_transactions** — one-to-many. Each ledger row is tagged with its reason (`signup_grant`, `generation_debit`, `generation_refund`, `admin_grant`, `subscription_credit_grant`).
- **products ↔ categories** — one-to-many. Each category belongs to one product (slug is unique per product).
- **products ↔ color_modes** — many-to-one. Print color profile is product-level.
- **categories ↔ styles (via category_styles)** — many-to-many. A category offers a curated set of styles; a style may be reused across categories.
- **styles ↔ layouts (via style_layouts)** — many-to-many. A layout may be valid for multiple styles.
- **styles/layouts ↔ poses (via project_photos.pose_id)** — many-to-one per photo. A photo may carry a pose hint (`Abraçados`, `Beijo`, etc.) for couple/family projects.
- **products ↔ prompt_templates (with category/style/layout)** — composite FK (4-tuple unique). Exactly one template per Product/Category/Style/Layout combo.
- **users ↔ projects** — one-to-many. A user owns projects; soft-deleted projects are filtered via `deleted_at IS NULL`.
- **projects ↔ project_photos** — one-to-many. A project can have multiple photos (`position` orders them). The legacy `source_image_id` column was dropped in favor of this pivot.
- **project_photos ↔ poses** — many-to-one optional. Poses are admin-curated cues for AI generation in couple/family projects.
- **projects ↔ generations** — one-to-many. Each generation belongs to one project; re-runs create new generations (immutability).
- **generations ↔ generation_providers** — many-to-one. Provider is resolved at submit time and snapshotted on the generation row.
- **generations ↔ generation_statuses** — many-to-one. Status flows `waiting → processing → completed | failed`.
- **users ↔ audit_logs (as actor)** — one-to-many. Every admin action is recorded with the acting admin and a polymorphic target.
- **subscription_plans ↔ subscription_intervals** — many-to-one. Each plan declares its billing interval (`month` or `year`).
- **subscription_plans ↔ subscriptions** — one-to-many. A plan may have many subscribers at any given moment.
- **subscriptions ↔ subscription_plans (current vs pending)** — two FKs: `subscription_plan_id` is the **current** plan; `pending_plan_id` is a scheduled downgrade target (US-9.6).
- **users ↔ subscriptions** — one-to-many. A user may, in lifetime, have only one active subscription at a time, but the table is shaped as one-to-many to keep past-canceled history (audit, downgrade mechanics).
- **stripe_events ↔ subscriptions / users / subscription_plans** — implicit, via the stored `payload` JSON. The app re-parses the payload inside the webhook handler to update the right rows.
- **subscriptions (via Cashier) ↔ users.stripe_id** — Cashier writes the `stripe_id` on `users` at first subscription; the column is the canonical link between a user and a Stripe Customer.

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

**category_statuses / style_statuses / layout_statuses / pose_statuses** (`slug`, `name`):
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
- `subscription_credit_grant`, `Subscription Credit Grant`, `+`

**audit_log_actions** (`slug`, `name`):
- `toggle_admin`, `Toggle Admin`
- `grant_credits`, `Grant Credits`
- `edit_product`, `Edit Product`
- `edit_category`, `Edit Category`
- `edit_style`, `Edit Style`
- `edit_layout`, `Edit Layout`
- `edit_prompt_template`, `Edit Prompt Template`
- `edit_plan`, `Edit Plan`

**subscription_intervals** (`slug`, `name`):
- `month`, `Monthly`
- `year`, `Yearly`

**subscription_statuses** (`slug`, `name`) — mirrors Stripe's `subscription.status` enumeration:
- `active`, `Active`
- `trialing`, `Trialing`
- `past_due`, `Past Due`
- `canceled`, `Canceled`
- `incomplete`, `Incomplete`
- `incomplete_expired`, `Incomplete Expired`
- `unpaid`, `Unpaid`
- `paused`, `Paused`

The canonical Stripe status string is stored verbatim on `subscriptions.stripe_status`; the lookup table is used by the admin UI as a friendly label.

**products (MVP seed)**:
- `mug`, `Mug`, active, 220×95 mm print area, 300 DPI, 5 mm safe area, RGB

**categories (MVP seed for `mug`)**: `birthday`, `wedding`, `pets`, `family`, `couples`, `kids` — all active.

**styles (MVP seed)**: `watercolor`, `cartoon`, `realistic`, `pixel_art`, `minimalist_line` — all active.

**layouts (MVP seed)**: `centered`, `border_wrap`, `full_bleed`, `split_top_bottom` — all active.

**poses (MVP seed)**: `abracados`, `beijo`, `sentados`, `caminhando`, `natal`, `praia`, `sofa`, `flores`.

**category_styles / style_layouts / prompt_templates**: seeded by an admin (or by the initial seeder) for every valid 4-tuple; placeholder `body` text ships with `{{name}}`, `{{phrase}}`, `{{theme}}`, `{{image_tags}}`, `{{print_specs}}` placeholders.

**subscription_plans (MVP seed, USD-equivalent examples, BRL-only in MVP)**:
- `starter`, `Starter`, 50cr, 1990¢, `BRL`, `month`, active, sort_order=10
- `pro`, `Pro`, 200cr, 5990¢, `BRL`, `month`, active, sort_order=20
- `business`, `Business`, 1000cr, 19990¢, `BRL`, `month`, active, sort_order=30
- `pro_yearly`, `Pro Anual`, 2400cr, 59900¢, `BRL`, `year`, active, sort_order=25

(Seeded inactive plans — sort_order is preserved so re-activating them doesn't change their position.)

## Notes & Conventions

- **No DB-level enums.** All status/type/reason fields reference a lookup table (`generation_statuses`, `project_modes`, `credit_transaction_reasons`, `subscription_intervals`, `subscription_statuses`) so admins can add values without a migration. Enums in PHP code mirror the lookup tables but the DB stays flexible.
- **Soft deletes apply only to `projects`** (30-day recovery window per US-5.3). `users` does NOT soft-delete — credit FKs and OAuth identity integrity must survive. Plan deactivation is a flag (`is_active = false`), not a delete; existing subscribers remain on the (now-invisible) plan per US-8.3 / SPEC §Q-04.
- **Polymorphic references** (`credit_transactions.reference_*`, `audit_logs.target_*`) use the string `reference_type` / `target_type` matching Laravel's `MorphTo` convention (e.g., `App\\Models\\Generation`, `App\\Models\\Subscription`). This keeps the schema generic — new reference targets do not require schema changes.
- **Idempotency — generations** — `generations.idempotency_key` is `unique` and must be set BEFORE the credit debit transaction begins. The job handler checks for an existing ledger row referencing this generation with reason `generation_debit` before any write, satisfying US-11.2.
- **Idempotency — webhook grants** — the webhook handler writes subscription-driven grants with `reference_type = "App\\Models\\Subscription"`, `reference_id = subscriptions.id`, plus the period-end timestamp encoded in `notes`. A secondary uniqueness check `(subscription_id, current_period_end)` is enforced in the service layer, because Postgres/SQLite don't index on a JSON column deterministically. The combined effect is: Stripe may retry the same `invoice.payment_succeeded` event freely, but only one `credit_transactions` row is written per cycle per subscription.
- **Idempotency — `stripe_events`** — every verified Stripe event is logged once via `stripe_events.stripe_event_id` (`unique`). On retry, the handler is a no-op. This complements the per-subscription ledger idempotency above.
- **Cashier relationship** — Laravel Cashier stores its own model by default (`Laravel\\Cashier\\Subscription`). To keep application code aligned, the project's `App\\Models\\Subscription` Eloquent model can either `extends CashierSubscription` or transparently re-use Cashier's table. The naming collision should be resolved during implementation (US-10.x open question in SPEC); the schema stays correct either way.
- **Denormalization — credit balance** — `users.credit_balance` is a cache of the credit_transactions ledger. Every write to the ledger updates `credit_balance` inside the same DB transaction (matches `CreditLedger::lockForUpdate()` pattern already in `app/Services/CreditLedger.php`). A scheduled reconciliation command can recompute from the ledger and surface drift to admins.
- **Denormalization — generations.user_id** — duplicated from `projects.user_id` to keep credit-history queries (`SELECT * FROM credit_transactions WHERE user_id = ?`) and "my generations" listings fast. Both columns must agree.
- **Snapshot fields** — `generations.prompt_snapshot`, `generations.constraints_snapshot`, and `prompt_templates.version` together provide a full audit trail: even if an admin edits a template after a generation completes, the exact prompt used for that generation is preserved on the row.
- **Subscription `pending_plan_id`** — a single nullable FK. A scheduled downgrade is set on the source subscription by mutating `pending_plan_id`; Cashier does not natively expose this slot, so we maintain it as an app-level concern (a webhook reconciliation step maps Stripe's `pending_update` to this column at `customer.subscription.updated`). Cancelling a pending downgrade (US-9.6) clears the column.
- **Stripe webhook `payload` storage** — the JSON blob on `stripe_events.payload` is the raw, verified bytes from Stripe. Replaying an old event re-runs the handler against the same payload, which is also how tests assert side-effect idempotency.
- **Indexes** — composite indexes are named explicitly. The most-frequent query patterns are:
  - `users` by `email` (auth lookup) — `unique` index on email.
  - `users` by `stripe_id` (Cashier lookup) — `unique` index.
  - `projects` for "my active projects" — `(user_id, deleted_at)`.
  - `project_photos` for ordered display — `(project_id, position)`.
  - `generations` for project history — `(project_id, status_id)`.
  - `credit_transactions` for user history — `(user_id, created_at)`.
  - `subscription_plans` for the public plans page — `(is_active, sort_order)`.
  - `subscriptions` for the user's billing page and admin filter — `(user_id, stripe_status)` and `(subscription_plan_id)`.
  - `stripe_events` for retries / debugging — `(type, created_at)`.
  - `audit_logs` for "who did what to whom" — `(target_type, target_id)` and `(actor_user_id, created_at)`.
- **Laravel framework tables** — `password_reset_tokens`, `sessions`, `jobs`, `job_batches`, `failed_jobs`, `cache`, `cache_locks` are created by the framework; Cashier adds `subscriptions` (or co-exists with our `subscriptions`), `subscription_items`, `payment_methods`, `webhook_calls` (we mirror selected fields on our own `subscriptions` / `stripe_events` to keep app logic independent of Cashier internals). They are listed at the bottom of the schema for awareness.
- **Naming** — table names plural snake_case; foreign keys singular with `_id` suffix; pivots named `entity1_entity2` alphabetical; lookup tables named `entity_attribute_types` style (`generation_statuses`, `credit_transaction_reasons`, `subscription_intervals`).
- **Timestamps** — every domain table has `created_at` and `updated_at` (Laravel convention). Soft-delete tables add `deleted_at`. Lookup tables also get timestamps so admin edits are traceable.

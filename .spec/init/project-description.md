# Kindred Canvas — Project Description

## Overview

Kindred Canvas is a SaaS platform that turns the complex, technical craft of personalized-mug artwork into a guided, automated experience. It is aimed at two audiences: **end users** (individuals who want a personalized mug without learning Photoshop or prompt engineering) and **personalization businesses** (small print shops that need professional, print-ready artwork at volume). The core loop is: sign up → upload or describe → pick a style and layout → AI generates print-ready art → download.

The MVP ships only the **mug** vertical, but the domain model (Product / Category / Style / Layout / PromptTemplate) is built so that adding t-shirts, pillows, canvases, and other printable items later is a configuration change, not a rewrite. Two creation modes are supported from day one: **Free mode** (no print constraints, ideal for casual users) and **Mug mode** (sublimation-ready output — correct proportions, safe area, print resolution). AI generation runs asynchronously through Laravel queues with **live status broadcasting**, and each generation consumes credits tracked in a dedicated **credit_transactions ledger** with automatic refund on failure. Mockup compositing and final print-file packaging are explicitly **deferred** to a post-MVP phase.

To keep the platform viable beyond the 5-credit signup grant, the system now supports **Stripe-powered recurring subscriptions**: users subscribe to an admin-managed plan, are billed monthly by Stripe, and automatically receive the plan's credits each cycle. Subscription state, plan changes, billing-portal access, and credit grants are driven by Stripe webhooks and the **Laravel Cashier** library; credits remain the single consumption unit and are still recorded through the existing `credit_transactions` ledger.

The project is built on a Laravel 13 / Livewire 4 / Flux UI / Tailwind 4 stack already present in the workspace at `kindrad-canvas/`. Authentication is delivered by **Laravel Fortify** with email-and-password as the primary flow and **Google OAuth** as a fast-onboarding secondary path. Image uploads use an **S3-compatible disk** from day one (production-ready). A minimal **admin back-office** (gated by an `is_admin` flag) provides CRUD for the configuration entities (categories, styles, layouts, prompt templates, mockup models, **subscription plans**) so the platform can be operated without re-seeding. The AI provider itself is hidden behind a `GenerationProvider` contract — the MVP ships with one adapter (decision pending: OpenAI / Google / Replicate), and additional providers can be added without touching domain code.

### Key Concepts

- **Product:** The printable item the artwork targets. MVP scope: `mug`. Future: `t_shirt`, `pillow`, `canvas`, etc. Each Product defines its print specs (dimensions, DPI, safe area, color profile).
- **Category:** A thematic bucket within a Product (e.g., for mugs: `birthday`, `wedding`, `pets`, `family`, `couples`, `kids`). A Category owns the base prompt skeleton and the list of allowed Styles.
- **Style:** The visual treatment applied to the artwork (e.g., `watercolor`, `cartoon`, `realistic`, `pixel_art`, `minimalist_line`). Each Style contributes a prompt fragment and visual constraints.
- **Layout:** The spatial composition of the artwork (e.g., `centered`, `border_wrap`, `full_bleed`, `split_top_bottom`). Layouts encode the safe-area and proportion rules for the target Product.
- **PromptTemplate:** A configurable prompt-builder that stitches together Product + Category + Style + Layout rules plus the user's inputs (names, phrases, themes) into the final prompt sent to the AI. Stored in the DB, editable by admins.
- **Project:** A user's in-progress or completed personalization. Has one Product, one Category, one Style, one Layout, an optional source image, user-supplied text fields, and a generation history.
- **Generation:** A single attempt to produce artwork for a Project. Has a status (`waiting`, `processing`, `completed`, `failed`), consumes credits, and produces a final image asset. Generations are **immutable** once completed; re-runs create new generations.
- **Credit:** Unit of consumption. One credit = one generation attempt. Credits live in a **ledger** (`credit_transactions`) that records every debit and refund with a reason, so the running balance can always be reconstructed. Credits originate from three sources: the `signup_grant`, manual `admin_grant`s, and **subscription_credit_grant** rows written by the Stripe-billing webhook handler.
- **User:** Authenticated account. Holds `email`, `password` (hashed, Fortify), optional Google-linked identity, `is_admin` flag, `credit_balance` (denormalized cache of the ledger, recomputable), and a **Stripe customer id** (`users.stripe_id` via Cashier's billable trait).
- **GenerationProvider:** Interface that abstracts the AI service (`generate(prompt, constraints, sourceImage): Promise<Result>`). MVP ships one concrete adapter. Swapping providers does not touch domain code.
- **Print Specs:** Per-product technical constraints — aspect ratio, minimum DPI, safe area (mm from edges), color mode (RGB for screen / CMYK for press). Encoded on the Product and enforced via Layouts and PromptTemplates.
- **Source Image:** An optional user-uploaded photo attached to a Project. The system analyzes it (people, animals, image quality) and preserves key features when generating derivative artwork.
- **Subscription Plan:** An admin-defined recurring offering (`subscription_plans`): name, description, credits-per-period, price in cents, currency (BRL), billing interval (month or year), display order, active flag. Each plan maps 1:1 to a Stripe Product + Price created on save.
- **Subscription:** A user's enrollment in a plan, mirrored from Stripe via **Laravel Cashier** (`users.subscriptions()`). Tracks Stripe status (`active`, `trialing`, `past_due`, `canceled`, `incomplete`), the local plan it points to, the current billing period (start/end), and a scheduled-change (downgrade) target.
- **Stripe Webhook Event:** A delivery from Stripe (checkout.session.completed, customer.subscription.{created,updated,deleted}, invoice.payment_{succeeded,failed}) consumed at `POST /stripe/webhook`. Each event is signature-verified and stored in `stripe_events` for idempotency; the handler then writes a `subscription_credit_grant` ledger row through `CreditLedger::subscriptionGrant`.
- **Billing Portal:** Stripe-hosted self-service surface (no UI built in-app). A signed `billing_portal_url` is generated server-side and opened from `/billing` for plan changes, card updates, cancellation, and invoice download.

## Configuration: Stripe in Dev vs Prod

The app is hermetic against Stripe by default — if `STRIPE_SECRET` is empty, `EnsureStripePriceAction` short-circuits, admin plans land only in the local DB (their `stripe_product_id` / `stripe_price_id` columns stay `null`), and the public `/billing/plans` page still renders (but clicking "Assinar" fails because no Customer is created). This makes local development and CI work without network egress.

| Stage | `STRIPE_KEY` | `STRIPE_SECRET` | `STRIPE_WEBHOOK_SECRET` | What happens on admin plan save | What happens on user "Assinar" |
|---|---|---|---|---|---|
| **Dev offline** (default) | empty | empty | empty | Local-only; no Stripe API calls | 500 from Checkout (no Customer) |
| **Dev with Stripe test mode** | `pk_test_…` | `sk_test_…` | `whsec_…` from `stripe listen --forward-to localhost/stripe/webhook` | Creates real Product + Price on Stripe **test** mode | Real Checkout Session against test cards (`4242 4242 4242 4242`) |
| **Prod** | `pk_live_…` | `sk_live_…` | `whsec_…` from Dashboard → Webhooks → Add endpoint (`https://your-domain/stripe/webhook`, select the six events listed below) | Creates real Product + Price on Stripe live | Real Checkout Session, real charges |

The six events the webhook endpoint must be subscribed to (Dashboard → Webhooks → endpoint → "Select events"):

- `checkout.session.completed`
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.payment_succeeded`
- `invoice.payment_failed`

Pest tests stub Stripe entirely with `Cashier::fake()` + `Stripe::fake()` so CI never hits the live API.

> The full step-by-step walkthrough (where to grab keys, how to run `stripe listen`, how to create the webhook in production, the verification checklist) lives in `kindrad-canvas/docs/stripe-setup.md`. That file is the source of truth for operations; this section is the design summary.

## Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Runtime | PHP | 8.4 |
| Framework | Laravel | 13 |
| Auth | Laravel Fortify | 1.x |
| OAuth | Laravel Socialite (Google provider) | latest stable |
| Subscriptions / Billing | Laravel Cashier (stripe) | 15.x (latest compatible with Laravel 13) |
| Payment provider | Stripe | live + test mode, BRL (default) |
| Frontend reactivity | Livewire | 4.x |
| UI components | Livewire Flux | 2.13.x |
| CSS | Tailwind CSS | 4.x |
| Bundler | Vite | 8.x |
| Queue driver | Laravel database queue (default) | — |
| Broadcasting | Laravel Reverb (self-hosted WebSockets) | latest stable |
| Testing | Pest | 4.x |
| Static analysis | Larastan (PHPStan) | 3.x |
| Code style | Laravel Pint | 1.x |
| Database | SQLite (dev) → MySQL/PostgreSQL (prod) | — |
| Object storage | S3-compatible (AWS S3 / Cloudflare R2 / MinIO) | — |
| AI generation | `GenerationProvider` interface + one concrete adapter (OpenAI / Gemini / Replicate — TBD) | — |

## Core Workflows

### 1. Account Creation and Login

A new user signs up via email + password (Fortify) or clicks "Continue with Google" (Socialite). On successful auth, a `users` row is created (or matched by email) with a starter credit grant (5 credits) recorded as the first `credit_transactions` entry. Sessions are persisted to the database; Livewire components re-hydrate server-side state on every request.

```http
POST /register       { name, email, password, password_confirmation }
POST /login          { email, password }
GET  /auth/google    → redirect to Google
GET  /auth/google/callback → exchange code, find-or-create user
```

### 2. Project Creation

An authenticated user clicks "New Project" from the dashboard, picks a Product (only `mug` in MVP), then steps through a wizard: pick Category → pick Style → pick Layout → optionally upload a Source Image → fill user inputs (names, phrases, theme, dedicatória). At the end, the system creates a `Project` row (status `draft`) and shows the configured generation settings for review.

### 3. Prompt Assembly and Generation Submission

When the user clicks "Generate", the server: (1) loads the PromptTemplate for the chosen Product/Category/Style/Layout combination, (2) substitutes the user's inputs and any source-image analysis tags, (3) **reserves credits** by writing a `credit_transactions` debit row and decrementing `users.credit_balance` atomically inside a DB transaction, (4) creates a `Generation` row (status `processing`), (5) dispatches a `GenerateArtworkJob` onto the queue. Returns immediately with the generation id.

```php
// Pseudocode of the prompt-assembly pipeline
$template = PromptTemplate::resolve($project->product, $project->category, $project->style, $project->layout);
$prompt   = $template->render([
    'name'        => $project->inputs['name'],
    'phrase'      => $project->inputs['phrase'],
    'theme'       => $project->inputs['theme'],
    'image_tags'  => $sourceImage?->analysis_tags ?? [],
    'print_specs' => $project->product->print_specs,
]);
```

### 4. Asynchronous AI Processing and Live Status

The queued job calls the configured `GenerationProvider` with the assembled prompt and constraints (Product print specs, Layout safe area, source image if present). On success it stores the resulting image on the S3 disk, updates the Generation to `completed`, and **broadcasts** a `GenerationUpdated` event over Reverb. On failure it: marks the Generation `failed`, writes a **refund** `credit_transactions` row (negative debit, reason `generation_failed`), increments `users.credit_balance`, and broadcasts the failure event. The Livewire dashboard component subscribes to the channel and updates the UI without polling.

```php
// Job lifecycle
class GenerateArtworkJob implements ShouldQueue
{
    public function handle(GenerationProvider $ai): void
    {
        try {
            $result = $ai->generate($this->prompt, $this->constraints, $this->sourceImage);
            $this->generation->markCompleted($result->storeOnS3());
            broadcast(new GenerationUpdated($this->generation));
        } catch (Throwable $e) {
            $this->generation->markFailed($e);
            CreditLedger::refund($this->generation, $e->getMessage());
            broadcast(new GenerationUpdated($this->generation));
        }
    }
}
```

### 5. Result Display and Download

Once a Generation reaches `completed`, the user sees the artwork inline. MVP delivers the **raw AI output file** as a download (no mockup compositing yet). Each completed Generation records the final asset key on S3 and the user can re-download from the project history at any time. The history view lists all generations for a Project with timestamps, status, and credits spent.

### 6. Admin Back-Office

Users with `is_admin = true` see an extra `/admin` section that provides CRUD screens (Livewire + Flux) for: Products, Categories, Styles, Layouts, PromptTemplates, MockupModels (deferred UI; data model seeded), Users (view, grant/revoke credits, toggle admin), and **Subscription Plans** (CRUD over `subscription_plans` + read-only view of every `users_subscriptions` row). All admin routes are gated by a single middleware that checks `is_admin`.

### 7. Credit Ledger Operations

The `credit_transactions` table is the source of truth. Every operation — signup grant, generation debit, generation refund, admin grant, top-up purchase, **subscription_credit_grant** — appends a row with `user_id`, `delta` (signed integer), `reason`, `reference_type` (polymorphic to Project / Generation / Subscription / etc.), `reference_id`, `created_at`. The user's current `credit_balance` is a denormalized cache kept consistent inside the same transaction that writes the ledger row. A reconciliation job can recompute `credit_balance` from the ledger to detect drift.

```sql
-- Schema sketch (final schema belongs in database-schema.md)
CREATE TABLE credit_transactions (
    id              BIGINT PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id),
    delta           INT NOT NULL,             -- negative = debit, positive = credit
    reason          VARCHAR(64) NOT NULL,     -- signup_grant, generation_debit, generation_refund, admin_grant, subscription_credit_grant
    reference_type  VARCHAR(64) NULL,         -- polymorphic
    reference_id    BIGINT NULL,
    balance_after   INT NOT NULL,             -- snapshot for audit
    created_at      TIMESTAMP NOT NULL
);
```

### 8. Subscription Plans (Admin)

An admin opens `/admin/plans` to manage the catalog. A plan has: name (displayed to the user), description, credits-per-period (integer), price in cents (integer), currency (fixed to BRL for MVP), billing interval (`month` or `year`), display order (`sort_order`), and an `is_active` toggle. On save, the system ensures a matching Stripe Product + Price exist (`stripe_product_id`, `stripe_price_id`); only `is_active = true` plans appear in the user-facing `/billing/plans` page. Deactivating a plan never cancels active subscribers (decision locked in SPEC §Q-04); existing subscribers keep their plan until they cancel via the Billing Portal.

```http
GET    /admin/plans                  Livewire index
GET    /admin/plans/create           Livewire create
GET    /admin/plans/{id}/edit        Livewire edit
PATCH  /admin/plans/{id}             soft-update (deactivate is just `is_active = false`)
```

### 9. User Subscription Lifecycle

A logged-in user visits `/billing/plans`, sees active plans sorted by `sort_order`, and clicks "Assinar [plan]". The server creates a Stripe Checkout Session (via Cashier) and redirects. After Stripe confirms payment, the user is sent to `/billing?success=1`; the page reads the fresh `credit_balance` (which already reflects the first month's grant, written on the `checkout.session.completed` + `invoice.payment_succeeded` webhooks). The user can also click "Gerenciar assinatura" which opens Stripe's hosted **Billing Portal** for card changes, plan switches, cancellation, and invoice download.

```http
GET    /billing/plans                public plans page (auth required)
POST   /billing/checkout             {plan_slug} → redirect to Stripe Checkout
GET    /billing                      current plan, remaining credits, next billing
POST   /billing/portal               redirect to Stripe Billing Portal
POST   /stripe/webhook               signature-verified Stripe event sink (CSRF exempt)
```

**Plan changes from the Billing Portal:**

- **Upgrade (e.g. Starter → Pro)** is applied immediately, Stripe charges the prorated difference, and the user receives the new plan's `credits_per_period` right away. Prorated remainder of the previous plan is **not** carried over (matches the "credits do not accumulate" ledger rule).
- **Downgrade (e.g. Pro → Starter)** is scheduled to take effect at `current_period_end`; new credits of the lower plan arrive at the next cycle.
- **Cancel** keeps access through the end of the paid period; afterwards the subscription becomes `canceled` and no further grants are written.

### 10. Stripe Webhook Handling

Every Stripe push goes to `/stripe/webhook`:

```http
POST /stripe/webhook
Stripe-Signature: t=...,v1=...
Content-Type: application/json

{ "id": "evt_...", "type": "invoice.payment_succeeded", "data": { "object": { ... } } }
```

The endpoint is exempted from CSRF, verifies the signature against `services.stripe.webhook_secret`, and writes the event into `stripe_events` (idempotency key = `event.id`). On a retry, the same `event.id` is detected and the handler is a no-op. After recording, the handler dispatches to a Cashier listener:

| Event | Local effect |
|---|---|
| `checkout.session.completed` | mark `Subscription` as `active`, persist initial `stripe_id` |
| `customer.subscription.created` | insert `Subscription` row pointing to local plan by `stripe_price_id` lookup |
| `customer.subscription.updated` | update `stripe_status`, `current_period_start/end`, plan reference, scheduled downgrade target |
| `customer.subscription.deleted` | mark `canceled`, stop future grants (already-credited balance remains) |
| `invoice.payment_succeeded` | write `subscription_credit_grant` ledger row via `CreditLedger::subscriptionGrant` (idempotent on `(subscription_id, period_end)`) |
| `invoice.payment_failed` | update `stripe_status` → `past_due`; user keeps using existing balance until Stripe finally gives up (`customer.subscription.deleted` follows) |

The whole flow is exercised by Pest tests using `Cashier::fakeCustomer()` + `Stripe::fake()` — no live Stripe traffic in CI.

### 11. Failure & Recovery States

- **Past-due** — the user still has the credits already granted; they can keep generating. Stripe retries the charge automatically.
- **Soft-delete of plan** — existing subscribers stay on the (now invisible) plan indefinitely (SPEC §Q-04). No automatic downgrade.
- **Webhook verification failure** — an unsigned or invalid-signature payload returns HTTP 400/403 and is **never** written to `stripe_events` (idempotency table is reserved for verified events).

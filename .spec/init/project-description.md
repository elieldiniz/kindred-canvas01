# Kindred Canvas — Project Description

## Overview

Kindred Canvas is a SaaS platform that turns the complex, technical craft of personalized-mug artwork into a guided, automated experience. It is aimed at two audiences: **end users** (individuals who want a personalized mug without learning Photoshop or prompt engineering) and **personalization businesses** (small print shops that need professional, print-ready artwork at volume). The core loop is: sign up → upload or describe → pick a style and layout → AI generates print-ready art → download.

The MVP ships only the **mug** vertical, but the domain model (Product / Category / Style / Layout / PromptTemplate) is built so that adding t-shirts, pillows, canvases, and other printable items later is a configuration change, not a rewrite. Two creation modes are supported from day one: **Free mode** (no print constraints, ideal for casual users) and **Mug mode** (sublimation-ready output — correct proportions, safe area, print resolution). AI generation runs asynchronously through Laravel queues with **live status broadcasting**, and each generation consumes credits tracked in a dedicated **credit_transactions ledger** with automatic refund on failure. Mockup compositing and final print-file packaging are explicitly **deferred** to a post-MVP phase.

The project is built on a Laravel 13 / Livewire 4 / Flux UI / Tailwind 4 stack already present in the workspace at `kindrad-canvas/`. Authentication is delivered by **Laravel Fortify** with email-and-password as the primary flow and **Google OAuth** as a fast-onboarding secondary path. Image uploads use an **S3-compatible disk** from day one (production-ready). A minimal **admin back-office** (gated by an `is_admin` flag) provides CRUD for the configuration entities (categories, styles, layouts, prompt templates, mockup models) so the platform can be operated without re-seeding. The AI provider itself is hidden behind a `GenerationProvider` contract — the MVP ships with one adapter (decision pending: OpenAI / Google / Replicate), and additional providers can be added without touching domain code.

### Key Concepts

- **Product:** The printable item the artwork targets. MVP scope: `mug`. Future: `t_shirt`, `pillow`, `canvas`, etc. Each Product defines its print specs (dimensions, DPI, safe area, color profile).
- **Category:** A thematic bucket within a Product (e.g., for mugs: `birthday`, `wedding`, `pets`, `family`, `couples`, `kids`). A Category owns the base prompt skeleton and the list of allowed Styles.
- **Style:** The visual treatment applied to the artwork (e.g., `watercolor`, `cartoon`, `realistic`, `pixel_art`, `minimalist_line`). Each Style contributes a prompt fragment and visual constraints.
- **Layout:** The spatial composition of the artwork (e.g., `centered`, `border_wrap`, `full_bleed`, `split_top_bottom`). Layouts encode the safe-area and proportion rules for the target Product.
- **PromptTemplate:** A configurable prompt-builder that stitches together Product + Category + Style + Layout rules plus the user's inputs (names, phrases, themes) into the final prompt sent to the AI. Stored in the DB, editable by admins.
- **Project:** A user's in-progress or completed personalization. Has one Product, one Category, one Style, one Layout, an optional source image, user-supplied text fields, and a generation history.
- **Generation:** A single attempt to produce artwork for a Project. Has a status (`waiting`, `processing`, `completed`, `failed`), consumes credits, and produces a final image asset. Generations are **immutable** once completed; re-runs create new generations.
- **Credit:** Unit of consumption. One credit = one generation attempt. Credits live in a **ledger** (`credit_transactions`) that records every debit and refund with a reason, so the running balance can always be reconstructed.
- **User:** Authenticated account. Holds `email`, `password` (hashed, Fortify), optional Google-linked identity, `is_admin` flag, and `credit_balance` (denormalized cache of the ledger, recomputable).
- **GenerationProvider:** Interface that abstracts the AI service (`generate(prompt, constraints, sourceImage): Promise<Result>`). MVP ships one concrete adapter. Swapping providers does not touch domain code.
- **Print Specs:** Per-product technical constraints — aspect ratio, minimum DPI, safe area (mm from edges), color mode (RGB for screen / CMYK for press). Encoded on the Product and enforced via Layouts and PromptTemplates.
- **Source Image:** An optional user-uploaded photo attached to a Project. The system analyzes it (people, animals, image quality) and preserves key features when generating derivative artwork.

## Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Runtime | PHP | 8.4 |
| Framework | Laravel | 13 |
| Auth | Laravel Fortify | 1.x |
| OAuth | Laravel Socialite (Google provider) | latest stable |
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

A new user signs up via email + password (Fortify) or clicks "Continue with Google" (Socialite). On successful auth, a `users` row is created (or matched by email) with a starter credit grant (e.g., 5 free credits) recorded as the first `credit_transactions` entry. Sessions are persisted to the database; Livewire components re-hydrate server-side state on every request.

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

Users with `is_admin = true` see an extra `/admin` section that provides CRUD screens (Livewire + Flux) for: Products, Categories, Styles, Layouts, PromptTemplates, MockupModels (deferred UI; data model seeded), and Users (view, grant/revoke credits, toggle admin). All admin routes are gated by a single middleware that checks `is_admin`.

### 7. Credit Ledger Operations

The `credit_transactions` table is the source of truth. Every operation — signup grant, generation debit, generation refund, admin grant, future top-up purchase — appends a row with `user_id`, `delta` (signed integer), `reason`, `reference_type` (polymorphic to Project / Generation / etc.), `reference_id`, `created_at`. The user's current `credit_balance` is a denormalized cache kept consistent inside the same transaction that writes the ledger row. A reconciliation job can recompute `credit_balance` from the ledger to detect drift.

```sql
-- Schema sketch (final schema belongs in database-schema.md)
CREATE TABLE credit_transactions (
    id              BIGINT PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES users(id),
    delta           INT NOT NULL,             -- negative = debit, positive = credit
    reason          VARCHAR(64) NOT NULL,     -- signup_grant, generation_debit, generation_refund, admin_grant
    reference_type  VARCHAR(64) NULL,         -- polymorphic
    reference_id    BIGINT NULL,
    balance_after   INT NOT NULL,             -- snapshot for audit
    created_at      TIMESTAMP NOT NULL
);
```
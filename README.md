# Kindred Canvas

AI mug design generator — personalized print artwork for mugs, end-to-end.

## What this is

A Laravel 13 application where authenticated users with credits describe a piece of personalized art via a 7-step wizard (mode → category → style → layout → source image → inputs → review), submit it for AI generation, watch it process, and download the final PNG. Built on Livewire 4 + Flux UI + Tailwind 4. Reference designs and full SPEC live in `.spec/`.

## Current state

Phase 6 (Project Wizard — 7 steps) + Phase 7 (AI Generation pipeline, polling-only) + Phase 8 (project show, download, soft-delete, 30-day purge) are merged into `main`. Test suite: 194 tests, 193 pass + 1 intentional skip on the CI-only perf timing assertion. Pint is clean. Recent work commit: `a764485`.

Phase 9 work — mockup composition, payments, multi-product, in-house vision analysis — is intentionally deferred.

## What's in the repo

```
.
├── .spec/                Specs, plans, phase breakdowns for project-wizard
├── .agents/              Local skill instructions (Flux UI, Livewire, Pest, etc.)
├── kindrad-canvas/       The Laravel application
├── stitch_kindred_canvas_ai_interface/  Reference HTML mockups
├── scripts/              Workspace scripts
└── README.md             You are here
```

## Stack

- PHP 8.4 · Laravel 13 · Livewire 4 · Flux UI (free) · Tailwind 4
- SQLite for the local dev DB (in-memory for tests)
- S3 for source images and generated artwork (uses `Storage::fake('s3')` in tests)
- OpenAI DALL-E 3 as the active generation provider; Gemini + Replicate stubbed
- Pest 4 · Larastan 3 · Pint 1

## Quick start

```bash
cd kindrad-canvas
composer install
npm install
cp .env.example .env
php artisan key:generate

# Database (uses sqlite for dev):
php artisan migrate --seed --seeder=DatabaseSeeder
# CatalogSeeder creates the lookup tables, the 'mug' product,
# 6 categories, 5 styles, 4 layouts, all pivots, and the full
# 6x5x4=120 prompt templates.

# Local dev server:
php artisan serve        # API + PHP
npm run dev              # Vite for Tailwind/Flux

# Tests + lint:
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

Visiting `/login` → create a user → magic-link or Fortify default. Signup grants **5 credits** (wired via `App\Listeners\GrantSignupCredits` on the `Registered` event → `App\Services\CreditLedger::signupGrant`).

## Architecture cheat sheet

### Auth & credits
- `App\Services\CreditLedger` is the **single write path** for `credit_transactions`. Wrapped in `DB::transaction` + `lockForUpdate` on `users` so `users.credit_balance` and the ledger row stay in sync. Idempotent on `signupGrant` and `refund`.
- Reasons seeded as lookup rows (`signup_grant`, `generation_debit`, `generation_refund`, `admin_grant`). Use `CreditLedger::reasonId('…')` to resolve slugs.
- ProjectPolicy + GenerationPolicy both gate on **owner-or-`is_admin`**. `EnsureAdmin` middleware is registered as `admin` in `bootstrap/app.php`.

### Project wizard
- `App\Livewire\Projects\Wizard` (parent) holds state (`step`, `projectId`, `modeId`, `categoryId`, `styleId`, `layoutId`, `sourceImageId`, `inputs`). Creates the draft row on `mount()` and re-authorizes on every request via `hydrate()` calling `authorizeOrAbort()`.
- Each step is a child component under `App\Livewire\Projects\Wizard\Steps\*` that dispatches an `*-selected` event; the parent's `#[On('…')]` listener owns authz + persistence. Forward-step jumps in the Review screen require all intermediate FKs.
- Step 5 owns the file upload (`use WithFileUploads`); the `source_image_id` is then saved on the project by the parent's `#[On('source-image-uploaded')] saveSourceImage`.

### AI generation
- `App\Contracts\GenerationProvider` + `App\Services\Generation\{OpenAI,Gemini,Replicate}Provider`. Registry resolves the active one via `config('generation.provider')` cross-referenced with the `generation_providers.is_active` lookup row.
- `App\Services\PromptAssembler::assemble(Project)` renders the prompt template's `{{name}}/{{phrase}}/{{theme}}/…/{{print_specs}}` and snapshots constraints (mm → pixels). `{{print_specs}}` substitutes only for `mode.slug = 'mug'`.
- `App\Actions\Generation\SubmitGeneration::execute($user, $project)` is atomic: auth check → assemble → create Generation with `idempotency_key` (UUID) → debit 1 credit → dispatch `GenerateArtworkJob` → return.
- `App\Jobs\GenerateArtworkJob` (`tries=3`, queue `sync` in tests): idempotency-checks the debit row before provider call, marks `processing`, calls provider, on success marks `completed` and sets `project.first_generated_at`, on terminal failure marks `failed` and calls `CreditLedger::refund`.
- Polling-based UI for now (no Reverb/Echo); the project show Livewire polls every 2s and the download endpoint is `GET /generations/{generation}/download` (gated by `GenerationPolicy::download`).

### Soft delete + purge
- `Project` uses Laravel's `SoftDeletes`. The Show component's Delete modal calls `Project::delete()` (soft) and redirects to the dashboard, which filters out trashed projects from the Recent Projects section.
- `php artisan projects:purge-deleted` (scheduled daily via `routes/console.php`) hard-deletes projects whose `deleted_at` is older than 30 days, removing their source-image and generation-result files from S3 first.

## Test layout

```
tests/Feature/
├── Auth/                                Signup-grants-credits, registration flows
├── Foundation/                          Catalog seeder, EnsureAdmin middleware, GenerationPolicy, schema smoke
├── Projects/                            WizardStartTest, WizardModeStepTest …, WizardEndToEndTest, ShowTest, DeleteTest, WizardAuthorizationTest
├── Services/CreditLedgerTest, PromptAssemblerTest, Generation/ProviderRegistryTest
├── Actions/Generation/SubmitGenerationTest
├── Jobs/GenerateArtworkJobTest
├── Generations/DownloadTest
└── Console/PurgeDeletedProjectsTest
```

Wizard slice alone: `php artisan test --filter=Projects` (87 tests, 86 pass + 1 skip).

## Test config notes

- `phpunit.xml` sets `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` so tests don't depend on Postgres.
- `LARAVEL_STORAGE_PATH=/home/eliel/.local/laravel-storage-testing` redirects `storage_path()` to a writable non-root location (the default `storage/framework/testing/` had been left root-owned in prior sessions and broke `Storage::fake('s3')`). If you wipe `/home/eliel/.local/laravel-storage-testing` you can recreate it from scratch with `mkdir -p …/{app/public,framework/cache/data,framework/sessions,framework/views,framework/testing,logs}` and `chmod -R 755`.
- Coverage thresholds aren't set — the suite is the contract.

## Environment variables you'll touch

```
APP_KEY                                   (php artisan key:generate)
DB_CONNECTION=sqlite|pgsql
OPENAI_API_KEY=sk-…                       (only needed for real image generation)
AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY  (only for real S3; tests fake it)
GENERATION_PROVIDER=openai                (default; or 'gemini'/'replicate' once those are implemented)
```

In tests none of the AWS/* need real values; `Storage::fake('s3')` covers everything.

## Open follow-ups (in priority order)

1. **Phase 7.4 — Broadcasting.** Replace `wire:poll.2s` with `Reverb` + Echo on `private-user.{id}` for sub-second UI updates.
2. **Phase 5.6/5.7 — Admin CRUD.** Prompt-template editor + admin credit grant UI; both are supported by the existing `prompt_templates` and `credit_transactions` tables.
3. **Phase 3 — Google OAuth.** The `users.google_id` column exists; Socialite wiring remains.
4. **Phase 4 — Dashboard widgets.** Real credit balance widget + paginated history page.
5. **Phase 9.** Mockup composition, Stripe top-up, multi-product expansion — explicitly deferred from MVP.

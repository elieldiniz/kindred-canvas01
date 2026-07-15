# Kindred Canvas — User Stories

<!-- inputs: project-description.md@sha256:891934ac4985 -->

## Overview

Kindred Canvas is a SaaS platform that turns personalized-mug artwork into a guided, AI-driven experience. It serves two audiences: **end users** who want a custom mug without learning design tools, and **personalization businesses** that need print-ready artwork at volume. The MVP delivers the **mug** vertical end-to-end (sign up → upload/describe → pick style and layout → AI generates → download) and lays the data-model groundwork for adding t-shirts, pillows, and other printable items later without code changes.

Beyond the 5-credit signup grant, the platform now offers **Stripe-powered recurring subscriptions** so end users can keep generating without manual credit top-ups. Plans are defined by admins; users subscribe via Stripe Checkout, are billed automatically each cycle, and receive their plan's credits each period through a webhook-driven grant that flows back through the existing `credit_transactions` ledger.

**User Types:**
- **Visitor** — Unauthenticated person browsing the marketing surface or auth screens.
- **End User** — Authenticated individual creating personalized mugs for personal use or gifting.
- **Subscriber** — End User actively enrolled in a Stripe subscription plan; receives periodic credit grants.
- **Business User** — Authenticated user producing artwork at higher volume; behaves like an End User in MVP (same features), distinguished only by future plans.
- **Administrator** — Authenticated user with `is_admin = true`; manages configuration (products, categories, styles, layouts, prompt templates, users, credits, subscription plans).

---

## 1. Authentication & Account

### US-1.1: Email + Password Registration
**As a** Visitor
**I want to** create an account with my name, email, and password
**So that** I can start personalizing mugs.

**Acceptance Criteria:**
- [ ] Registration form requires name, email, password (min 8 chars), password confirmation.
- [ ] Email must be unique; duplicate email shows a validation error.
- [ ] On successful registration, a `users` row is created and a starter credit grant (5 credits) is written to `credit_transactions` with reason `signup_grant`.
- [ ] User is logged in immediately after registration and redirected to the dashboard.
- [ ] Password is stored hashed (Fortify default); never logged or returned in any response.

**Expected Result:** A new user can register in under a minute and lands on the dashboard with a visible 5-credit balance.

### US-1.2: Email + Password Login
**As a** registered User
**I want to** log in with my email and password
**So that** I can access my projects.

**Acceptance Criteria:**
- [ ] Login form accepts email + password; wrong credentials show a generic error ("invalid credentials").
- [ ] After 5 failed attempts within 10 minutes, login is throttled (Fortify rate limiter).
- [ ] On success, session is created (DB driver) and user is redirected to the dashboard.
- [ ] "Remember me" checkbox extends session lifetime.

**Expected Result:** Returning users can sign in reliably; brute-force attempts are throttled.

### US-1.3: Google OAuth Sign-In
**As a** Visitor
**I want to** click "Continue with Google"
**So that** I can sign up or sign in without creating a password.

**Acceptance Criteria:**
- [ ] Clicking "Continue with Google" redirects to Google's OAuth consent screen.
- [ ] On callback, if no user exists with that Google email, a new user is created (name from Google profile), password set to a random unguessable value, and the user receives the 5-credit signup grant.
- [ ] If a user with that email already exists, the Google identity is linked to the existing account (no duplicate row, no double-grant).
- [ ] On success the user lands on the dashboard; on denial/cancel the user lands on the login screen with no error.

**Expected Result:** A visitor can be onboarded in two clicks without typing a password.

### US-1.4: Logout
**As a** logged-in User
**I want to** log out
**So that** my session ends on shared or public devices.

**Acceptance Criteria:**
- [ ] Clicking "Logout" invalidates the session and redirects to the homepage.
- [ ] Subsequent requests to authenticated routes redirect to the login screen.

**Expected Result:** No residual access after logout.

### US-1.5: Forgot Password
**As a** User who forgot my password
**I want to** request a password-reset link by email
**So that** I can regain access to my account.

**Acceptance Criteria:**
- [ ] "Forgot password?" link on the login form sends a reset email when the address exists.
- [ ] Reset link is valid for 60 minutes and can be used exactly once.
- [ ] Resetting the password hashes the new value and invalidates all existing sessions for that user (Fortify behavior).
- [ ] If the email is unknown, the response is identical (no enumeration leak).

**Expected Result:** A user can recover their account via email even after losing their password.

---

## 2. Onboarding & Credits

### US-2.1: View Credit Balance
**As a** User
**I want to** see my current credit balance on the dashboard
**So that** I know how many generations I can still run.

**Acceptance Criteria:**
- [ ] Dashboard shows `credit_balance` prominently (header widget).
- [ ] Balance updates live when a generation completes or fails (broadcast-driven).
- [ ] Balance is the denormalized cache; a drift indicator is visible only to admins.

**Expected Result:** Users always see an accurate credit count.

### US-2.2: View Credit History
**As a** User
**I want to** view a history of my credit movements
**So that** I understand where my credits went.

**Acceptance Criteria:**
- [ ] A "Credits" page lists all `credit_transactions` rows for the logged-in user, newest first.
- [ ] Each row shows date, delta (signed), reason label, and a link to the referenced project/generation/subscription when applicable.
- [ ] Admin grants show the reason text provided by the admin.
- [ ] `subscription_credit_grant` rows display the plan name and the billing period (month/year + date).

**Expected Result:** Every credit change is traceable from the user's perspective.

---

## 3. Project Creation Wizard

### US-3.1: Start a New Project
**As a** User
**I want to** click "New Project" from the dashboard
**So that** I can begin creating a personalization.

**Acceptance Criteria:**
- [ ] "New Project" button is visible on the dashboard for any authenticated user.
- [ ] Clicking it creates a `projects` row with status `draft` and redirects to step 1 of the wizard.
- [ ] The wizard exposes only the `mug` product in MVP.

**Expected Result:** A user can start a new project in one click.

### US-3.2: Pick a Category
**As a** User creating a project
**I want to** choose a category (e.g., Birthday, Wedding, Pets)
**So that** the AI is steered toward the right subject matter.

**Acceptance Criteria:**
- [ ] Step shows all active categories that belong to the `mug` product.
- [ ] Each category displays a thumbnail and a short description.
- [ ] Selecting a category advances to the Style step and persists the choice on the project.
- [ ] Only one category may be selected.

**Expected Result:** The category narrows the generation context without locking the user in.

### US-3.3: Pick a Style
**As a** User creating a project
**I want to** choose a visual style (e.g., Watercolor, Cartoon, Realistic)
**So that** the artwork matches the mood I want.

**Acceptance Criteria:**
- [ ] Step lists all styles available for the chosen category.
- [ ] Each style displays a thumbnail.
- [ ] Selecting a style advances to the Layout step and persists on the project.
- [ ] If the category has no associated styles, the user is shown a friendly empty state and cannot proceed (admin must add styles).

**Expected Result:** Style selection drives the visual treatment of the final artwork.

### US-3.4: Pick a Layout
**As a** User creating a project
**I want to** choose a layout (e.g., Centered, Border Wrap, Full Bleed)
**So that** the artwork fits the mug's printable area correctly.

**Acceptance Criteria:**
- [ ] Step lists all layouts available for the chosen style.
- [ ] Each layout shows a preview that demonstrates the safe-area and proportion rules.
- [ ] Selecting a layout advances to the Source Image step and persists on the project.

**Expected Result:** The chosen layout encodes the safe-area and proportion rules sent to the AI.

### US-3.5: Upload an Optional Source Image
**As a** User creating a project
**I want to** optionally upload a photo to inspire the artwork
**So that** the AI can incorporate elements from my image.

**Acceptance Criteria:**
- [ ] Step accepts jpeg/png/webp files up to 10MB; other MIME types are rejected with a clear error.
- [ ] On accept, the file is uploaded to the S3 disk and a `source_images` row is created referencing it.
- [ ] The user can skip this step.
- [ ] After upload, a thumbnail preview is shown with a "Replace" and "Remove" action.

**Expected Result:** A user can attach an inspiration photo or proceed without one.

### US-3.6: Fill User Inputs
**As a** User creating a project
**I want to** enter text inputs (names, phrases, theme)
**So that** the AI personalizes the artwork with my content.

**Acceptance Criteria:**
- [ ] Step shows fields configured for the chosen category (e.g., name, phrase, theme, dedicatória).
- [ ] Field-level validation matches category rules (max length, required/optional).
- [ ] On "Next", inputs are persisted on the project (JSON column) and the user lands on the review screen.

**Expected Result:** User-supplied content is captured and will be substituted into the prompt.

### US-3.7: Review and Submit
**As a** User creating a project
**I want to** review my selections before generating
**So that** I can correct mistakes before spending a credit.

**Acceptance Criteria:**
- [ ] Review screen shows: product, mode, category, style, layout, source image (if any), and all user inputs.
- [ ] An "Edit" button per section returns the user to that wizard step.
- [ ] A "Generate" button is disabled if the user has 0 credits, with a tooltip explaining why.

**Expected Result:** The user has one last chance to correct inputs before credit is consumed.

---

## 4. AI Generation

### US-4.1: Submit Generation
**As a** User
**I want to** click "Generate"
**So that** the AI creates artwork for my project.

**Acceptance Criteria:**
- [ ] Clicking "Generate" runs inside a DB transaction: (1) writes a `credit_transactions` debit row with reason `generation_debit`, (2) decrements `users.credit_balance`, (3) creates a `generations` row with status `processing`, (4) dispatches `GenerateArtworkJob` on the queue.
- [ ] If the user has fewer credits than 1, no row is written and the request returns 422 with a clear message.
- [ ] The user is redirected to the project page with the new generation visible.

**Expected Result:** Credit is reserved atomically before any AI work is attempted.

### US-4.2: Live Status Updates
**As a** User
**I want to** see my generation's status update in real time
**So that** I know when it is ready without refreshing.

**Acceptance Criteria:**
- [ ] While the job runs, the generation row shows `processing` and the UI displays a spinner.
- [ ] On completion, a `GenerationUpdated` event is broadcast over Reverb and the UI flips to `completed` with the artwork inline, without a page reload.
- [ ] On failure, the event flips the UI to `failed` with a "Retry" button (creates a new Generation, debits a credit again).
- [ ] If the WebSocket connection drops, a fallback polling endpoint (`GET /generations/{id}`) keeps the UI accurate once the page reloads.

**Expected Result:** Users always see the latest status with sub-second latency when the connection is healthy.

### US-4.3: Automatic Refund on Failure
**As a** User
**I want to** be refunded automatically if the AI generation fails
**So that** I am not charged for an error I did not cause.

**Acceptance Criteria:**
- [ ] When `GenerateArtworkJob` throws an exception, a `credit_transactions` row with reason `generation_refund` and positive `delta` is written inside a DB transaction.
- [ ] `users.credit_balance` is incremented by the same amount.
- [ ] The generation's failure reason is stored and visible to the user in plain language.
- [ ] Refund is idempotent: if the job is retried after partial refund, no double-refund occurs.

**Expected Result:** A failed generation returns exactly one credit to the user.

### US-4.4: Regenerate (Create New Generation)
**As a** User
**I want to** click "Regenerate" after a result is ready
**So that** I can get a different version of the artwork.

**Acceptance Criteria:**
- [ ] "Regenerate" creates a NEW `generations` row (status `processing`) and debits 1 credit.
- [ ] Prior generations remain in the history list unchanged.
- [ ] "Regenerate" is disabled when balance is 0.

**Expected Result:** Each generation is a separate, immutable entry in the history; the user can produce as many variations as their credits allow.

### US-4.5: Generation Provider Abstraction
**As a** maintainer
**I want** the AI provider to be hidden behind a `GenerationProvider` interface
**So that** we can swap providers without touching domain code.

**Acceptance Criteria:**
- [ ] A `GenerationProvider` interface defines `generate(prompt, constraints, ?SourceImage): GenerationResult`.
- [ ] One concrete adapter ships with the MVP (OpenAI, Gemini, or Replicate — to be chosen).
- [ ] Switching the active provider is a config value (`config('generation.provider')`); no service-container changes required.
- [ ] Adding a new provider requires only implementing the interface and registering it in the service provider.

**Expected Result:** Provider choice is a configuration decision, not a refactor.

---

## 5. Generation Results

### US-5.1: View Completed Artwork
**As a** User
**I want to** see the generated artwork inline on my project page
**So that** I can decide whether to download or regenerate.

**Acceptance Criteria:**
- [ ] The latest `completed` generation is shown large at the top of the project page.
- [ ] A history list below shows every generation (status, timestamp, credits spent) newest-first.
- [ ] Clicking any history row swaps the inline preview to that generation's image.

**Expected Result:** The user can browse their full generation history visually.

### US-5.2: Download Artwork
**As a** User
**I want to** download a completed generation as a file
**So that** I can send it to a printer or save it locally.

**Acceptance Criteria:**
- [ ] A "Download" button is visible on each `completed` generation.
- [ ] The button streams the file from S3 to the user's browser with the original AI-output format (PNG/JPEG).
- [ ] Only the owner of the project (or an admin) can download; ownership is enforced server-side.
- [ ] Expired or missing S3 objects show a graceful "File unavailable" message (no 500).

**Expected Result:** Any completed artwork can be retrieved as long as the file exists in S3.

### US-5.3: Delete a Project
**As a** User
**I want to** delete a project I no longer need
**So that** my dashboard stays tidy.

**Acceptance Criteria:**
- [ ] "Delete project" requires a confirmation modal.
- [ ] On confirm, the project is soft-deleted (`deleted_at` set); it disappears from the dashboard.
- [ ] Soft-deleted projects remain recoverable for 30 days; after that a cleanup job purges S3 files and hard-deletes the row.
- [ ] Deleting a project does not refund credits; credits already spent are not returned.

**Expected Result:** Users can clean up their workspace without losing history immediately.

---

## 6. Free Mode vs Mug Mode

### US-6.1: Choose Mode at Project Creation
**As a** User
**I want to** choose between Free mode and Mug mode when starting a project
**So that** the artwork either ignores or respects sublimation print specs.

**Acceptance Criteria:**
- [ ] The "New Project" flow exposes a mode selector as the first choice after product.
- [ ] Mug mode injects the product's print specs (aspect ratio, DPI, safe area) into the prompt template and constrains the AI provider call.
- [ ] Free mode omits print-spec instructions from the prompt.
- [ ] Mode is stored on the project and cannot be changed after the first generation.
- [ ] All generations within one project share the same mode.

**Expected Result:** A business user picks Mug mode for production; a casual user picks Free mode and gets more creative freedom.

---

## 7. Admin Back-Office

### US-7.1: Admin Access Gate
**As an** Administrator
**I want to** access the admin section
**So that** I can manage platform configuration.

**Acceptance Criteria:**
- [ ] `/admin` routes are protected by an `EnsureAdmin` middleware that checks `users.is_admin`.
- [ ] Non-admins get a 403 response when probing `/admin/*`.
- [ ] An "Admin" link is shown in the user menu only when `is_admin = true`.

**Expected Result:** Only admins reach admin screens.

### US-7.2: Manage Products
**As an** Administrator
**I want to** create, edit, and deactivate Products
**So that** the catalog reflects what we offer.

**Acceptance Criteria:**
- [ ] Admin Products index lists all products with name, slug, status, print specs summary.
- [ ] Create/Edit form captures: name, slug, status, print specs (aspect ratio, DPI, safe area in mm, color mode).
- [ ] Deactivating a Product hides it from the user-facing wizard but does not delete historical projects.

**Expected Result:** New printable items can be added without code changes.

### US-7.3: Manage Categories
**As an** Administrator
**I want to** create and edit Categories
**So that** users can pick a thematic bucket for their artwork.

**Acceptance Criteria:**
- [ ] Admin Categories index lists categories with name, slug, product, thumbnail, status.
- [ ] Each category supports associating allowed styles (many-to-many).
- [ ] Create/Edit form validates uniqueness of `slug` per product.

**Expected Result:** Categories steer users toward the right subject matter.

### US-7.4: Manage Styles
**As an** Administrator
**I want to** create and edit Styles
**So that** users can pick visual treatments.

**Acceptance Criteria:**
- [ ] Admin Styles index lists styles with name, prompt fragment, thumbnail, status.
- [ ] Create/Edit form captures name, prompt fragment, thumbnail upload, and which categories this style belongs to.

**Expected Result:** New visual treatments can be added or retired at any time.

### US-7.5: Manage Layouts
**As an** Administrator
**I want to** create and edit Layouts
**So that** users can pick spatial compositions that respect print specs.

**Acceptance Criteria:**
- [ ] Admin Layouts index lists layouts with name, preview image, safe-area spec, allowed styles (many-to-many).
- [ ] Create/Edit form captures name, preview, safe-area overlay spec, and style associations.

**Expected Result:** New layouts can be added without code changes.

### US-7.6: Manage Prompt Templates
**As an** Administrator
**I want to** edit the PromptTemplate used for a Product/Category/Style/Layout combination
**So that** the AI output quality can be tuned without a deployment.

**Acceptance Criteria:**
- [ ] Admin Prompt Templates index lists templates keyed by the 4-tuple (product, category, style, layout).
- [ ] Edit form renders a templated prompt body with `{{placeholders}}` for `name`, `phrase`, `theme`, `image_tags`, `print_specs`.
- [ ] Saving the template updates the next generations immediately (no cache invalidation step required).

**Expected Result:** Admins can iterate on prompt quality without a code deploy.

### US-7.7: Manage Users
**As an** Administrator
**I want to** view users, toggle admin status, and grant credits
**So that** I can support and moderate the user base.

**Acceptance Criteria:**
- [ ] Admin Users index lists users with email, name, balance, `is_admin`, created_at.
- [ ] "Grant credits" modal requires a positive integer amount and a free-text reason; submitting writes a `credit_transactions` row with reason `admin_grant` and the reason text.
- [ ] "Toggle admin" flips `is_admin`; admins cannot demote themselves (self-protection).
- [ ] All admin actions are written to the audit log (created_at, admin_user_id, action, target_user_id).

**Expected Result:** Admins can support users and recover from mistakes (e.g., refund a wrong charge).

### US-7.8: View Platform Metrics
**As an** Administrator
**I want to** see high-level platform metrics
**So that** I can understand adoption and cost.

**Acceptance Criteria:**
- [ ] Admin dashboard shows: total users, new users (last 7 days), total generations, generations by status, total credits in circulation, total credits spent.
- [ ] Metrics are computed from existing tables (no separate analytics store in MVP).
- [ ] Metrics panel also shows active subscriptions count and Monthly Recurring Revenue (sum of `price_cents × active_subscriptions` for the default currency).

**Expected Result:** Admins have a single-page snapshot of platform health.

---

## 8. Subscription Plans (Admin)

### US-8.1: View Subscription Plans
**As an** Administrator
**I want to** see all subscription plans in one place
**So that** I can audit what the catalog offers.

**Acceptance Criteria:**
- [ ] Admin Plans index lists every plan (active and inactive) with name, price, currency, interval, credits-per-period, sort order, status, Stripe Price id.
- [ ] Inactive plans are visually flagged and not editable for "is_active" via the same screen as "delete" (no soft delete on plans).
- [ ] Listings are sorted by `sort_order` ascending, then `id` descending as tie-breaker.

**Expected Result:** The admin sees the full plan catalog at a glance.

### US-8.2: Create a Plan
**As an** Administrator
**I want to** create a new subscription plan
**So that** users can subscribe to it.

**Acceptance Criteria:**
- [ ] Create form captures: name, description, credits-per-period (positive int), price in cents (positive int), currency (locked to BRL in MVP), interval (`month` or `year`), `sort_order` (int ≥ 0), `is_active` (default true).
- [ ] On save, a matching Stripe Product + Price are created via the `EnsureStripePriceAction`; the returned ids are persisted on the row.
- [ ] Validation blocks zero/negative prices and zero/negative credits.
- [ ] If Stripe returns an error during creation, the local row is not committed and the admin sees the Stripe error message.
- [ ] Creating a plan writes an `audit_log` entry with action `edit_plan`.

**Expected Result:** A new plan appears on `/admin/plans` and (if active) on `/billing/plans` once saved.

### US-8.3: Edit a Plan
**As an** Administrator
**I want to** edit a plan's name, description, `sort_order`, and `is_active`
**So that** I can correct copy and toggle visibility without touching Stripe.

**Acceptance Criteria:**
- [ ] Edit form lets the admin change non-Stripe-tracked fields: name, description, credits-per-period, `sort_order`, `is_active`.
- [ ] Toggling `is_active` to false immediately hides the plan from `/billing/plans`; existing subscribers stay on the plan (no auto-downgrade).
- [ ] Changing `credits_per_period` does **not** retroactively alter credits already granted in current or prior periods.
- [ ] Every change writes an `audit_log` row with `before/after` snapshot for changed fields.

**Expected Result:** Catalog copy and visibility can be corrected without support tickets.

### US-8.4: View All Subscriptions (Admin)
**As an** Administrator
**I want to** see every active subscription in the platform
**So that** I can support users and audit recurring revenue.

**Acceptance Criteria:**
- [ ] Admin Subscriptions index lists every `users_subscriptions` row with: user email, plan name, status, current period start/end, ends_at (if scheduled to cancel), created_at.
- [ ] Status filter (active, trialing, past_due, canceled, incomplete) narrows the list.
- [ ] Clicking a row opens a detail panel with the full Stripe status history.

**Expected Result:** Admins can answer "who is on which plan" in under a minute.

---

## 9. User Subscription Lifecycle

### US-9.1: Browse Public Plans
**As an** authenticated User
**I want to** visit `/billing/plans` and see what plans are available
**So that** I can pick one that matches my usage.

**Acceptance Criteria:**
- [ ] `/billing/plans` shows only plans where `is_active = true`, ordered by `sort_order` ascending.
- [ ] Each plan card displays: name, description, credits-per-period, formatted price (e.g., "R$19,90/mês"), and the interval.
- [ ] If the user already has an active subscription, the active plan is highlighted as "Plano atual" with a "Gerenciar assinatura" link instead of "Assinar".
- [ ] Guests visiting `/billing/plans` are redirected to login.

**Expected Result:** Any authenticated user can see and compare plans without contacting support.

### US-9.2: Subscribe via Stripe Checkout
**As a** User
**I want to** click "Assinar [plan]" and pay with my card
**So that** I can start receiving credits every cycle.

**Acceptance Criteria:**
- [ ] Clicking "Assinar [plan]" creates a Stripe Checkout Session via `StartSubscriptionCheckoutAction` (Cashier) and redirects the browser.
- [ ] On Stripe success, Stripe redirects to `/billing?success=1`.
- [ ] On Stripe cancel, Stripe redirects to `/billing?canceled=1`; the page shows a non-blocking "Checkout cancelado" message.
- [ ] The user's `users.stripe_id` is set the first time they subscribe (handled by Cashier).
- [ ] First credits are visible on `/billing` immediately after Stripe confirms payment (driven by `invoice.payment_succeeded` webhook).

**Expected Result:** A user finishes subscribing in under two minutes and sees the credited balance updated.

### US-9.3: Receive Monthly Credit Grants
**As a** Subscriber
**I want to** receive the plan's credits automatically each billing cycle
**So that** I can keep generating without manual top-ups.

**Acceptance Criteria:**
- [ ] On `invoice.payment_succeeded`, the webhook handler writes a `credit_transactions` row with reason `subscription_credit_grant`, `delta = plan.credits_per_period`, and `reference_type = Subscription`.
- [ ] The grant is idempotent on `(subscription_id, current_period_end)`: if Stripe retries the same event, only one row is written.
- [ ] Credits **do not** accumulate: when the next cycle begins, the user starts with the new grant + the already-spent balance (no rollover of leftover from the prior cycle beyond what was effectively unused — the ledger simply appends a positive delta at each cycle).
- [ ] When the grant is written, `users.credit_balance` is incremented atomically inside the same DB transaction.
- [ ] The user sees "Próxima cobrança: dd/mm/aaaa" on `/billing` after the first successful payment.

**Expected Result:** A subscriber who consumes 50 of their 200 monthly credits sees their balance restored to 200 at the next renewal without doing anything.

### US-9.4: Open the Billing Portal
**As a** Subscriber
**I want to** click "Gerenciar assinatura" and reach Stripe's portal
**So that** I can update my card, change plan, or cancel without contacting support.

**Acceptance Criteria:**
- [ ] The button calls `StartBillingPortalAction` and redirects to the Stripe Billing Portal URL.
- [ ] The Stripe Portal returns the user to `/billing?portal_return=1` after any change.
- [ ] Webhook updates (`customer.subscription.updated`, `customer.subscription.deleted`) keep the local `users_subscriptions` row in sync after the user returns.
- [ ] The user is not required to log in again when returning from the portal during the same session.

**Expected Result:** Users self-serve everything subscription-related through Stripe; no admin involvement needed.

### US-9.5: Upgrade Mid-Cycle
**As a** Subscriber
**I want to** upgrade to a higher-tier plan from the Billing Portal
**So that** I can produce more aggressively this month.

**Acceptance Criteria:**
- [ ] Stripe charges the prorated difference immediately (`proration_behavior: create_prorations`).
- [ ] On `customer.subscription.updated` + `invoice.payment_succeeded`, the user receives `credits_per_period` of the **new** plan as a `subscription_credit_grant` row.
- [ ] The prorated remainder of the **old** plan is **not** carried over (matches the "credits do not accumulate" ledger rule).
- [ ] The user sees the upgraded plan reflected on `/billing` after Stripe redirects back.

**Expected Result:** An upgrade takes effect within seconds, and the user can keep generating at the new rate immediately.

### US-9.6: Downgrade at Cycle End
**As a** Subscriber
**I want to** downgrade from the Billing Portal at the next renewal
**So that** I can reduce my spend without losing the credits I already paid for.

**Acceptance Criteria:**
- [ ] The downgrade is recorded as a scheduled plan change on the local `Subscription` row (e.g., `pending_plan_id`).
- [ ] At `current_period_end`, the `customer.subscription.updated` webhook flips the local row to the new plan.
- [ ] The **first** grant under the new plan is written at the next `invoice.payment_succeeded`.
- [ ] Downgrading back-to-back (e.g., Pro → Starter → Pro) keeps exactly one pending target at a time; cancelling a pending downgrade returns to the current plan immediately.

**Expected Result:** A downgrade lands exactly when the user expects it (at cycle end) and the new credits arrive the moment Stripe bills the new amount.

### US-9.7: Cancel at Cycle End
**As a** Subscriber
**I want to** cancel my subscription from the Billing Portal
**So that** I stop being charged next month.

**Acceptance Criteria:**
- [ ] Cancel marks the subscription `cancel_at_period_end = true`; the user keeps access until `current_period_end`.
- [ ] On `customer.subscription.deleted` (received after Stripe fully cancels), the local row's `stripe_status` flips to `canceled` and no further grants are written.
- [ ] Already-credited but unspent credits **remain** on the user's balance (the user can still generate with them).
- [ ] The `/billing` page shows a banner: "Sua assinatura termina em dd/mm/aaaa".

**Expected Result:** Cancellation honors what the user already paid for and stops future grants predictably.

### US-9.8: Past-Due State
**As a** Subscriber whose payment failed
**I want to** keep generating with my existing balance while Stripe retries
**So that** an isolated card decline doesn't lock me out.

**Acceptance Criteria:**
- [ ] `invoice.payment_failed` flips the local row to `stripe_status = past_due`.
- [ ] The user can still spend **already-credited** credits (no new grants are written until payment succeeds).
- [ ] `/billing` shows a warning banner: "Seu último pagamento falhou — atualize seu cartão no portal".
- [ ] When Stripe recovers (`invoice.payment_succeeded` arrives), `stripe_status` returns to `active` and the next grant runs normally.
- [ ] If Stripe gives up entirely (`customer.subscription.deleted`), the row becomes `canceled` and the user is downgraded to a pay-as-you-go model — i.e., whatever unspent balance they have remains, but no further subscription grants arrive.

**Expected Result:** A past-due card never blocks legitimate use of already-paid credits.

---

## 10. Stripe Webhooks

### US-10.1: Webhook Signature Verification
**As a** maintainer
**I want** every incoming Stripe webhook to be signature-verified
**So that** a forged event cannot impersonate Stripe.

**Acceptance Criteria:**
- [ ] The endpoint `POST /stripe/webhook` verifies the `Stripe-Signature` header against `services.stripe.webhook_secret` before any DB write.
- [ ] Invalid or missing signatures return HTTP 400 (or 403, per Cashier's convention) with no body and no state change.
- [ ] The endpoint is exempt from CSRF.
- [ ] In tests, `Cashier::fake()` + `Stripe::fake()` simulate signatures; unsigned posts fail the test.

**Expected Result:** No forged webhook can mark a subscription `active` or grant credits.

### US-10.2: Webhook Idempotency
**As a** maintainer
**I want** retried Stripe events to be processed exactly once
**So that** Stripe's automatic retries never duplicate grants or transitions.

**Acceptance Criteria:**
- [ ] Every verified event is recorded in `stripe_events` keyed by the unique Stripe `event.id`.
- [ ] If an event with the same id arrives again, the handler returns 2xx without re-running the side effects.
- [ ] Both retry-by-Stripe and retry-by-our-queue are covered by the same `(stripe_event_id)` uniqueness constraint.
- [ ] Pest test: posting the same `evt_...` payload twice results in exactly one `credit_transactions` row.

**Expected Result:** Stripe can retry at will without ever double-granting or double-flipping a status.

### US-10.3: Subscription Created
**As a** Subscriber, when Stripe confirms first payment
**I expect** the local `users_subscriptions` row to be created with `stripe_status = active`.

**Acceptance Criteria:**
- [ ] `customer.subscription.created` (and `checkout.session.completed`) insert/update the local row, mapping `stripe_price_id` → local `subscription_plans.id`.
- [ ] `current_period_start` and `current_period_end` are persisted on the row.
- [ ] If the local plan lookup fails (e.g., Stripe Price was deleted), the event is recorded but flagged for admin review rather than crashing the webhook.

**Expected Result:** Once Stripe confirms the first invoice, `/billing` reflects the active subscription within one webhook round-trip.

### US-10.4: Subscription Updated
**As a** Subscriber, after a plan change or payment status update in the Stripe Portal
**I expect** the local row to mirror the new state.

**Acceptance Criteria:**
- [ ] `customer.subscription.updated` updates: `stripe_status`, `current_period_start`, `current_period_end`, plan reference (when Stripe Price changes), scheduled plan reference (when a downgrade is queued).
- [ ] Status transitions past_due → active flip the row back and resume grants on the next `invoice.payment_succeeded`.
- [ ] The webhook responds 2xx before any heavy work (heavy work runs in queued listeners).

**Expected Result:** The user always sees up-to-date plan and period info after returning from the portal.

### US-10.5: Subscription Deleted
**As a** Subscriber whose subscription has been fully canceled
**I expect** my local row to read `canceled` and no future grants.

**Acceptance Criteria:**
- [ ] `customer.subscription.deleted` sets `stripe_status = canceled` and clears any scheduled plan.
- [ ] The user's already-credited balance is untouched.
- [ ] Any future `invoice.*` event for the deleted subscription is a no-op (event logged, no grant written).

**Expected Result:** After Stripe finishes canceling, the local system stops granting but the user's existing credits are honored.

---

## 11. Security & Reliability

### US-11.1: Authorization on All Routes
**As a** maintainer
**I want** every action to check ownership or admin status server-side
**So that** users cannot read or modify each other's data.

**Acceptance Criteria:**
- [ ] Project/Generation routes use a Policy that grants access only to the owner or an admin.
- [ ] Admin routes use the `EnsureAdmin` middleware.
- [ ] Authorization is enforced in controllers/actions, not just hidden in the UI.
- [ ] A non-admin user probing another user's subscription via `/billing` (e.g., a forged id parameter) gets 403.
- [ ] A non-admin user opening a Billing Portal session for a subscription they do not own gets 403.

**Expected Result:** No user can access another user's projects, generations, credits, or subscription via direct URL probing.

### US-11.2: Idempotent Credit Operations
**As a** maintainer
**I want** every credit ledger write to be safe under retry
**So that** a job retried after a partial failure cannot double-charge or double-refund.

**Acceptance Criteria:**
- [ ] Each generation has a unique idempotency key stored on the generation row.
- [ ] Debit/refund operations check for an existing ledger row referencing that generation with the same reason before writing.
- [ ] Subscription grants check for an existing ledger row referencing `(subscription_id, current_period_end)` with reason `subscription_credit_grant` before writing.
- [ ] The job is safe to retry up to 3 times (Laravel `tries`) without ledger inconsistency.

**Expected Result:** Concurrent or retried jobs never corrupt the user's balance.

### US-11.3: Audit Log for Admin Actions
**As an** Administrator
**I want** admin actions to be recorded
**So that** I can audit who changed what.

**Acceptance Criteria:**
- [ ] Every admin mutation (toggle admin, grant credits, edit product/category/style/layout/prompt template, **create/edit plan**) writes an `audit_log` entry with actor, action, target, before/after snapshot, timestamp.
- [ ] Audit log is viewable in the admin section and is append-only.

**Expected Result:** Admin actions are traceable.

### US-11.4: Stripe Sandbox Safe by Default
**As a** maintainer
**I want** the application to run against Stripe test mode and never hit live Stripe in dev/test
**So that** CI is safe and developers don't accidentally charge real cards.

**Acceptance Criteria:**
- [ ] `config('services.stripe.secret')` resolves from `STRIPE_SECRET`, defaulting to a documented test key in `.env.example`.
- [ ] `config('services.stripe.webhook_secret')` resolves from `STRIPE_WEBHOOK_SECRET`.
- [ ] When `APP_ENV` is `production`, the README documents how to swap test keys for live keys out-of-band.
- [ ] The Pest suite uses `Cashier::fake()` / `Stripe::fake()`; no live network is touched.

**Expected Result:** The whole test suite is hermetic against Stripe.

---

## Appendix: User Story Status

| ID | Story | Priority | Status |
|----|-------|----------|--------|
| US-1.1 | Email + Password Registration | High | Pending |
| US-1.2 | Email + Password Login | High | Pending |
| US-1.3 | Google OAuth Sign-In | High | Pending |
| US-1.4 | Logout | High | Pending |
| US-1.5 | Forgot Password | Medium | Pending |
| US-2.1 | View Credit Balance | High | Pending |
| US-2.2 | View Credit History | Medium | Pending |
| US-3.1 | Start a New Project | High | Pending |
| US-3.2 | Pick a Category | High | Pending |
| US-3.3 | Pick a Style | High | Pending |
| US-3.4 | Pick a Layout | High | Pending |
| US-3.5 | Upload an Optional Source Image | High | Pending |
| US-3.6 | Fill User Inputs | High | Pending |
| US-3.7 | Review and Submit | High | Pending |
| US-4.1 | Submit Generation | High | Pending |
| US-4.2 | Live Status Updates | High | Pending |
| US-4.3 | Automatic Refund on Failure | High | Pending |
| US-4.4 | Regenerate (Create New Generation) | High | Pending |
| US-4.5 | Generation Provider Abstraction | High | Pending |
| US-5.1 | View Completed Artwork | High | Pending |
| US-5.2 | Download Artwork | High | Pending |
| US-5.3 | Delete a Project | Medium | Pending |
| US-6.1 | Choose Mode at Project Creation | High | Pending |
| US-7.1 | Admin Access Gate | High | Pending |
| US-7.2 | Manage Products | High | Pending |
| US-7.3 | Manage Categories | High | Pending |
| US-7.4 | Manage Styles | High | Pending |
| US-7.5 | Manage Layouts | High | Pending |
| US-7.6 | Manage Prompt Templates | High | Pending |
| US-7.7 | Manage Users | High | Pending |
| US-7.8 | View Platform Metrics | Medium | Pending |
| US-8.1 | View Subscription Plans | High | Pending |
| US-8.2 | Create a Plan | High | Pending |
| US-8.3 | Edit a Plan | High | Pending |
| US-8.4 | View All Subscriptions (Admin) | Medium | Pending |
| US-9.1 | Browse Public Plans | High | Pending |
| US-9.2 | Subscribe via Stripe Checkout | High | Pending |
| US-9.3 | Receive Monthly Credit Grants | High | Pending |
| US-9.4 | Open the Billing Portal | High | Pending |
| US-9.5 | Upgrade Mid-Cycle | High | Pending |
| US-9.6 | Downgrade at Cycle End | High | Pending |
| US-9.7 | Cancel at Cycle End | High | Pending |
| US-9.8 | Past-Due State | High | Pending |
| US-10.1 | Webhook Signature Verification | High | Pending |
| US-10.2 | Webhook Idempotency | High | Pending |
| US-10.3 | Subscription Created | High | Pending |
| US-10.4 | Subscription Updated | High | Pending |
| US-10.5 | Subscription Deleted | High | Pending |
| US-11.1 | Authorization on All Routes | High | Pending |
| US-11.2 | Idempotent Credit Operations | High | Pending |
| US-11.3 | Audit Log for Admin Actions | Medium | Pending |
| US-11.4 | Stripe Sandbox Safe by Default | High | Pending |

**Out of MVP scope (explicitly deferred):**
- Mockup generation and final print-file packaging (post-MVP).
- One-time top-up credit purchases without a subscription (the recurring subscription is the only monetization surface in MVP).
- Multi-product expansion beyond `mug` (data model supports it; UI does not yet).
- Vision-based in-house source-image analysis (image is passed verbatim to the AI).
- Free-form "describe your artwork" mode without a chosen Category (every project picks a Category).
- Coupon / promo-code support on Stripe Checkout.
- Auto-applied free trial on the user-facing plans page (admin may configure trial on the Stripe Price itself, but the app does not advertise it).
- Refund flow in the app (admin issues refunds from the Stripe Dashboard).
- Multi-currency support (BRL is locked as the only currency in MVP).
- Invoice / billing history surface inside the app (Stripe emails the user; we don't mirror it in-app).

# Stripe Setup — Dev, Test, and Production

This guide explains how to wire Stripe into Kindred Canvas in each environment. The application is **hermetic against Stripe by default**: if `STRIPE_SECRET` is empty, the app skips all Stripe API calls and admin plans are saved only in the local database (their `stripe_product_id` / `stripe_price_id` columns stay `NULL`). This lets you run dev and CI without any network egress.

The same six events are listened to in every environment:

- `checkout.session.completed`
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.payment_succeeded`
- `invoice.payment_failed`

---

## The three environments at a glance

| Stage | `STRIPE_KEY` | `STRIPE_SECRET` | `STRIPE_WEBHOOK_SECRET` | Admin plan save | User "Assinar" click |
|---|---|---|---|---|---|
| **Dev offline** (default) | empty | empty | empty | Local-only; no Stripe API calls | 500 from Checkout (no Customer) |
| **Dev with Stripe test mode** | publishable test key | secret test key | webhook signing secret from `stripe listen` | Creates real Product + Price on Stripe **test** | Real Checkout Session against test cards (`4242 4242 4242 4242`) |
| **Production** | publishable live key | secret live key | webhook signing secret from Dashboard webhook config | Creates real Product + Price on Stripe **live** | Real Checkout Session, real charges |

---

## 1. Dev offline (default — works without any keys)

Leave all three Stripe variables empty in `.env`:

```dotenv
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

What you can do:

- Create, edit, toggle, and delete plans in `/admin/plans`. They persist in your local database with `stripe_product_id` and `stripe_price_id` both `NULL`.
- View plans on `/billing/plans` (the page renders, but every "Assinar" button throws because the Checkout action cannot create a Stripe Customer without `STRIPE_SECRET`).

What you **cannot** do:

- Finish a real Checkout flow.
- Receive real webhooks.

This mode is great for UI work, admin QA, and CI smoke tests.

---

## 2. Dev with Stripe test mode (recommended before staging)

Use Stripe's **test mode** so you exercise the full pipeline without spending real money or charging real cards.

### 2.1 Get the API keys

1. Open https://dashboard.stripe.com/test/apikeys (sign in if needed; switch the test/live toggle in the top-right to **Test**).
2. Copy the **Publishable key** and **Secret key** from that page.
3. Paste them into `.env`:

```dotenv
STRIPE_KEY=<your-stripe-publishable-test-key>
STRIPE_SECRET=<your-stripe-secret-test-key>
```

### 2.2 Get a webhook signing secret

Stripe's webhook secret is **ephemeral** — it changes every time you start `stripe listen`. Plan to rotate it often.

1. Install the Stripe CLI: https://stripe.com/docs/stripe-cli.
2. Login once: `stripe login`.
3. Forward events to your local app:

   ```bash
   stripe listen --forward-to localhost/stripe/webhook
   ```

   On startup the CLI prints a line like:

   ```
   > Ready! Your webhook signing secret is <the-whsec-value-from-stripe-listen> (this is a test secret, do not use it in production)
   ```

4. Copy that <webhook signing secret> value into `.env`:

   ```dotenv
   STRIPE_WEBHOOK_SECRET=<the-whsec-value-from-stripe-listen>
   ```

5. Leave `stripe listen` running while you exercise the app. If you restart it, paste the new secret into `.env` and reload the Laravel config (`php artisan config:clear`).

### 2.3 Test cards

Use `4242 4242 4242 4242` with any future expiry and any CVC. See the full list of test cards at https://docs.stripe.com/testing#cards.

### 2.4 Verify

1. Visit `/billing/plans` and click "Assinar" — you should be redirected to `https://checkout.stripe.com/…`.
2. Complete the checkout using a test card.
3. You should land on `/billing?success=1` and see your `credit_balance` increased by the plan's `credits_per_period`.
4. Open `stripe listen` output — you should see events like `checkout.session.completed` and `invoice.payment_succeeded` flowing through.
5. Check the `stripe_events` table in your local DB:

   ```sql
   SELECT event_id, type, processed_at FROM stripe_events ORDER BY id DESC LIMIT 5;
   ```

---

## 3. Production

### 3.1 Get the API keys

1. Open https://dashboard.stripe.com/apikeys.
2. Switch the test/live toggle (top-right) to **Live**.
3. Reveal and copy the **Secret key**. The publishable key is already visible.
4. Paste both into your production `.env`:

```dotenv
STRIPE_KEY=<your-stripe-publishable-live-key>
STRIPE_SECRET=<your-stripe-secret-live-key>
```

> ⚠️ **Never commit a live key.** `.env` is in `.gitignore`, but if you ever copy values out of it (for a screenshot, a support ticket, etc.), scrub them before sharing. Treat your production secret key like a database password.

### 3.2 Create the webhook endpoint

1. Open https://dashboard.stripe.com/webhooks.
2. Click **Add endpoint**.
3. Set **Endpoint URL** to `https://your-domain.com/stripe/webhook` (use HTTPS, no trailing slash, exact path).
4. Under **Listen for**, click **Select events** and check exactly these six:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
5. Save.
6. Open the endpoint you just created, click **Reveal** under **Signing secret**, copy the <webhook signing secret> value.
7. Paste it into your production `.env`:

   ```dotenv
   STRIPE_WEBHOOK_SECRET=<the-whsec-value-from-stripe-listen>
   ```

8. From your production server, run `php artisan config:clear` so the new value takes effect.

### 3.3 Verify

1. Create a plan in `/admin/plans` — open Stripe Dashboard → Products; you should see a matching Product with a recurring Price.
2. Subscribe as a user with a **real** card (test mode is now off; this will charge money).
3. Verify the webhook delivery: Stripe Dashboard → Webhooks → your endpoint → **Logs**. Each of the six events from your test should show `200 OK`.
4. Confirm `credit_transactions` rows:

   ```sql
   SELECT delta, balance_after, reason_id, reference_type, reference_id
   FROM credit_transactions
   WHERE user_id = ?
   ORDER BY id DESC LIMIT 5;
   ```

   You should see one row with `reason_id` pointing to `subscription_credit_grant` (slug) and `reference_type = 'App\\Models\\Subscription'`.

---

## 4. Where each variable is read

| Variable | Laravel config path | Used by |
|---|---|---|
| `STRIPE_KEY` | `services.stripe.key` | Frontend (Cashier JS publishable key) — and `config/cashier.php` reads the same env |
| `STRIPE_SECRET` | `services.stripe.secret` and `config/cashier.php` `secret` | All server-side Stripe calls: `EnsureStripePriceAction`, Checkout, Billing Portal |
| `STRIPE_WEBHOOK_SECRET` | `services.stripe.webhook_secret` and `config/cashier.php` `webhook.secret` | `Laravel\Cashier\Http\Middleware\VerifyWebhookSignature` (applied to `POST /stripe/webhook`) |

These two config paths (`services.stripe.*` and `config/cashier.php`) both read the same env vars, so setting them once is enough. The duplicated reads are intentional — `services.stripe.*` is our app convention (matches `services.stripe` in `config/services.php`), and `cashier.php` is the library's own configuration namespace.

## 5. Webhook signature verification

`POST /stripe/webhook` is registered **without** the `VerifyCsrfToken` middleware (so Stripe can post to it from outside the browser) but **with** `Laravel\Cashier\Http\Middleware\VerifyWebhookSignature` (which checks the `Stripe-Signature` header against `STRIPE_WEBHOOK_SECRET`). Forged or unsigned requests are rejected with HTTP 400 before any DB write.

For this to work, `STRIPE_WEBHOOK_SECRET` **must** be set in every environment that receives real webhooks. In dev offline mode it can stay empty — without it, Stripe will simply not deliver events to your machine (which is fine; there's nothing to deliver).

## 6. Pest test environment

`tests/Feature/Billing/*Test.php` and `tests/Feature/Admin/Plans/*Test.php` use `Cashier::fake()` + `Stripe::fake()`. These fakes:

- Don't make real network calls.
- Let you assert on what *would* have been sent to Stripe.
- Short-circuit signature verification.

CI therefore never hits `api.stripe.com`, even when Stripe-related code is exercised.

---

## 7. Quick checklist

Before any deployment:

- [ ] `STRIPE_SECRET` set in production `.env` (live key).
- [ ] `STRIPE_WEBHOOK_SECRET` set in production `.env` (live secret).
- [ ] Production webhook endpoint created in Stripe Dashboard pointing to `https://your-domain.com/stripe/webhook` with the six events selected.
- [ ] `php artisan config:clear` run after changing env vars.
- [ ] Smoke test: create a real plan via `/admin/plans`, confirm it appears in Stripe Dashboard → Products with a recurring Price.

For local dev with Stripe test mode:

- [ ] `STRIPE_KEY` and `STRIPE_SECRET` set to the publishable + secret test keys from your Stripe dashboard.
- [ ] `stripe listen --forward-to localhost/stripe/webhook` running.
- [ ] `STRIPE_WEBHOOK_SECRET` set to the <webhook signing secret> printed by `stripe listen`.
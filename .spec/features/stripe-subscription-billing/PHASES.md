# Phases: stripe-subscription-billing

Gerado por /plan a partir de PLAN.md — view executável para `./ralph.sh .spec/features/stripe-subscription-billing/PHASES.md`.

## Phase 1: Cashier foundation + lookup tables

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-03, RF-04, RF-11, RNF-02; metadata de RF-01..RF-12 que dependem de lookup)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — superfície REST do feature (CT-01)

- [ ] T01 — Install `laravel/cashier` and configure Stripe environment
      Arquivos: `kindrad-canvas/composer.json`, `kindrad-canvas/.env.example`, `kindrad-canvas/config/services.php`, `kindrad-canvas/config/cashier.php`, `kindrad-canvas/app/Models/User.php`
      Mudança: require `laravel/cashier`; publicar `cashier.php`; adicionar `services.stripe.{key,secret,webhook_secret}`; preencher `.env.example`; adicionar trait `Billable` ao `User` mantendo fillable/casts intactos; rodar `php artisan migrate` para criar tabelas do Cashier (`customers`, `subscriptions`, `subscription_items`, `webhook_events`).
      Cobre: RF-03, RF-04, RF-11, RNF-02
      Acceptance criteria: `composer show laravel/cashier` retorna versão instalada; `services.stripe.webhook_secret` lê de `STRIPE_WEBHOOK_SECRET`; `User::factory()->create()` retorna instância com método `subscriptions()` (verificável via `method_exists($user, 'subscriptions') === true`); `cashier.webhook_secret` existe no config publicado.
      Testes: `tests/Feature/Billing/UserIsBillableTest.php` — `expect($user->subscriptions())->toBeInstanceOf(HasMany::class)` e `expect(config('services.stripe.webhook_secret'))->toBe(env('STRIPE_WEBHOOK_SECRET'))`.
- [ ] T03 — Create subscription lookup tables + extend `CatalogSeeder`
      Arquivos: `kindrad-canvas/database/migrations/2026_07_15_120001_create_subscription_intervals_table.php` (novo), `kindrad-canvas/database/migrations/2026_07_15_120002_create_subscription_statuses_table.php` (novo), `kindrad-canvas/database/migrations/2026_07_15_120003_add_subscription_credit_grant_reason.php` (novo), `kindrad-canvas/app/Models/SubscriptionInterval.php` (novo), `kindrad-canvas/app/Models/SubscriptionStatus.php` (novo), `kindrad-canvas/database/seeders/CatalogSeeder.php` (alterado)
      Mudança: migrations criam `subscription_intervals` (id, slug unique, name, timestamps) e `subscription_statuses` (id, slug unique, name, timestamps); estender `CatalogSeeder` com `$subscriptionIntervals = ['month' => 'Mensal', 'year' => 'Anual']`, `$subscriptionStatuses = ['active'=>'Ativo','trialing'=>'Em trial','past_due'=>'Pagamento atrasado','canceled'=>'Cancelado','incomplete'=>'Incompleto','incomplete_expired'=>'Incompleto expirado','unpaid'=>'Não pago','paused'=>'Pausado']`, e adicionar `subscription_credit_grant` aos `creditTransactionReasons`; criar método privado `seedBillingLookups()` invocado de `run()`.
      Cobre: RF-04, RF-05, RF-06, RF-08
      Acceptance criteria: `php artisan migrate` aplica as três migrations sem erro; `php artisan db:seed --class=CatalogSeeder` é idempotente; `SubscriptionInterval::where('slug','month')->exists()` é true após seed; `SubscriptionStatus::whereIn('slug',['active','trialing','past_due','canceled','incomplete','incomplete_expired','unpaid','paused'])->count() === 8`; `CreditTransactionReason::where('slug','subscription_credit_grant')->exists()` é true.
      Testes: `tests/Feature/Seeders/CatalogSeederBillingTest.php` — asserta as 8 statuses + 2 intervals + o reason existem após rodar `CatalogSeeder`.

## Phase 2: Subscription plan + stripe_events schemas

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-01, RF-02, UI-01, UI-02, UI-03; RNF-01; CT-02)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — schema `SubscriptionPlan` e `SubscriptionInterval`

- [ ] T02 — Create `subscription_plans` table, model, factory
      Arquivos: `kindrad-canvas/database/migrations/2026_07_15_120000_create_subscription_plans_table.php` (novo), `kindrad-canvas/app/Models/SubscriptionPlan.php` (novo), `kindrad-canvas/database/factories/SubscriptionPlanFactory.php` (novo)
      Mudança: migration cria `subscription_plans` com colunas conforme PLAN T02 (id, name, slug unique, description, credits_per_period, price_cents, currency default BRL, interval_id FK, is_active, sort_order, stripe_product_id, stripe_price_id, timestamps; índice composto `(is_active, sort_order, id)`); model com `HasFactory`, `$fillable`, casts, `interval()` BelongsTo, `scopeActive`, `scopeOrdered`; factory com states `inactive()` e `withInterval('month')`.
      Cobre: RF-01, RF-02, UI-01, UI-02, UI-03
      Acceptance criteria: `php artisan migrate` aplica a migration; `SubscriptionPlan::factory()->create()` retorna instância válida; `SubscriptionPlan::active()->ordered()->pluck('id')->toArray()` retorna IDs em ordem `sort_order ASC, id ASC`; `SubscriptionPlan::factory()->inactive()->create()->is_active === false`.
      Testes: `tests/Feature/Models/SubscriptionPlanTest.php` — cobre `scopeActive` filtra inativos e `scopeOrdered` ordena por `sort_order`.
- [ ] T05 — Create `stripe_events` table for webhook dedup
      Arquivos: `kindrad-canvas/database/migrations/2026_07_15_120005_create_stripe_events_table.php` (novo), `kindrad-canvas/app/Models/StripeEvent.php` (novo)
      Mudança: migration cria `stripe_events` com id, event_id (unique), type, payload (json), processed_at (nullable), timestamps; índice `(type, created_at)`; model `App\Models\StripeEvent` com `$fillable = ['event_id','type','payload','processed_at']`, casts para json/datetime.
      Cobre: RNF-01, CT-02
      Acceptance criteria: `php artisan migrate` aplica; `StripeEvent::create(['event_id'=>'evt_x','type'=>'invoice.payment_succeeded','payload'=>['k'=>'v']])` persiste; segunda inserção com mesmo `event_id` viola constraint unique (capturável via `expect(fn() => ...)->toThrow(QueryException::class)`).
      Testes: `tests/Feature/Models/StripeEventTest.php` — uniqueness constraint.

## Phase 3: Subscription model + Cashier relations

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-04, RF-05, RF-06, RF-07, RF-08, RF-10)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — schema `SubscriptionRow` e `SubscriptionStatus`

- [ ] T04 — Create `subscriptions` table + model + relations
      Arquivos: `kindrad-canvas/database/migrations/2026_07_15_120004_create_subscriptions_table.php` (novo), `kindrad-canvas/app/Models/Subscription.php` (novo), `kindrad-canvas/database/factories/SubscriptionFactory.php` (novo)
      Mudança: migration cria `subscriptions` com colunas conforme PLAN T04; model estende `Laravel\Cashier\Subscription` (ou usa wrapper se conflito de namespace — verificar `vendor/laravel/cashier/src/Subscription.php` durante implementação); relações `subscriptionPlan()`, `scheduledPlan()`, `status()`; casts para booleans/dates; factory gera subscription relacionada a `User` e `SubscriptionPlan`.
      Cobre: RF-04, RF-05, RF-06, RF-07, RF-08, RF-10
      Acceptance criteria: `php artisan migrate` aplica; `(new Subscription)->getTable() === 'subscriptions'`; `(new Subscription)->getMorphClass()` retorna o FQCN correto; `$user->subscriptions()->save(Subscription::factory()->for($user)->create())` persiste e `$user->fresh()->subscriptions->count() === 1`.
      Testes: `tests/Feature/Models/SubscriptionRelationTest.php` — asserts `$user->subscriptions()` é HasMany e a relação inclui `subscriptionPlan` e `status`.

## Phase 4: CreditLedger::subscriptionGrant

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-05, RF-07, RF-10, RNF-01)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — não impacta o contrato REST; leitura opcional

- [ ] T06 — Extend `CreditLedger` with `subscriptionGrant`
      Arquivos: `kindrad-canvas/app/Services/CreditLedger.php` (alterado)
      Mudança: adicionar método público `subscriptionGrant(Subscription $subscription, int $amount): CreditTransaction` espelhando `adminGrant` em `app/Services/CreditLedger.php:138-160`: idempotência via lookup em `(reason_id, reference_type=Subscription::class, reference_id=$subscription->id)`; em hit, retorna row existente; em miss, `DB::transaction` + `User::whereKey($subscription->user_id)->lockForUpdate()->firstOrFail()` + write em `credit_balance` + `CreditTransaction::create([...,'reference_type' => Subscription::class, 'reference_id' => $subscription->id, 'notes' => null])`. Resolve reason via `$this->reasonId('subscription_credit_grant')`. Reusa `assertPositive`.
      Cobre: RF-05, RF-07, RF-10, RNF-01
      Acceptance criteria: chamada com `(subscription, 200)` em usuário com balance 0 resulta em `credit_balance === 200` e exatamente 1 row em `credit_transactions` com `reason_id` resolvendo para slug `subscription_credit_grant` e `reference_type=Subscription::class`; segunda chamada com mesma subscription retorna a mesma row e balance permanece 200; chamada com `amount=0` lança `\InvalidArgumentException`.
      Testes: `tests/Feature/Services/CreditLedgerSubscriptionGrantTest.php` — cobre primeira chamada, idempotência, e idempotência sob `DB::transaction`.

## Phase 5: Checkout + Billing Portal actions

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-03, RF-09, UI-04)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — schemas `CheckoutStartResponse` e `PortalSessionResponse`

- [ ] T07 — Implement `StartCheckoutAction`
      Arquivos: `kindrad-canvas/app/Actions/Billing/StartCheckoutAction.php` (novo)
      Mudança: Action invokable com constructor DI; método público `handle(User $user, SubscriptionPlan $plan): string` chamando `$user->createOrGetStripeCustomer()` + `$user->newSubscription('default', $plan->stripe_price_id)->checkout([...])` com `success_url` = `route('billing.index', ['success' => 1])` e `cancel_url` = `route('billing.plans.index')`; lança `LogicException` quando `stripe_price_id` é null.
      Cobre: RF-03
      Acceptance criteria: sob `Cashier::fake()`, primeira chamada em usuário sem `stripe_id` invoca `customers.create` e persiste `stripe_id`; segunda chamada pula a criação; URL retornada é string começando com `https://checkout.stripe.com/`; plano sem `stripe_price_id` lança `LogicException`.
      Testes: `tests/Feature/Actions/Billing/StartCheckoutActionTest.php` — cobre primeira/segunda chamada e ausência de price.
- [ ] T09 — Implement `CreateBillingPortalSessionAction` + `/billing` dashboard Livewire
      Arquivos: `kindrad-canvas/app/Actions/Billing/CreateBillingPortalSessionAction.php` (novo), `kindrad-canvas/app/Livewire/Billing/Index.php` (novo), `kindrad-canvas/resources/views/livewire/billing/index.blade.php` (novo), `kindrad-canvas/routes/web.php` (alterado)
      Mudança: Action `handle(User $user): string` chamando `$user->billingPortalUrl(route('billing.index'))`; lança exception mapeada para 404 quando `$user->stripe_id` é null; Livewire `Billing\Index` lê `?success=1`, carrega subscription mais recente do usuário com `subscriptionPlan.interval` e `status`, lê `credit_balance`; método `openPortal()` invoca action e redireciona; view renderiza banner de sucesso, plano atual, balance e botão "Gerenciar assinatura" (habilitado quando `status_id` ∈ {active, trialing, past_due}); rota `Route::livewire('billing', Index::class)->middleware(['auth','verified'])->name('billing.index')` dentro do grupo `auth` em `routes/web.php`.
      Cobre: RF-09, UI-04
      Acceptance criteria: GET `/billing` como guest redireciona para login; usuário sem subscription vê placeholder "Você ainda não tem uma assinatura" e nenhum botão "Gerenciar assinatura"; usuário com subscription `active` vê botão habilitado; clique no botão sob `Stripe::fake()` redireciona para URL do portal mockado; usuário sem `stripe_id` recebe HTTP 404 ao tentar abrir portal; query `?success=1` renderiza banner de sucesso.
      Testes: `tests/Feature/Billing/BillingDashboardTest.php` — cobre todos os cenários acima.

## Phase 6: Public Plans Livewire + routes

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-02, RF-03, UI-03)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — schemas `SubscriptionPlan` e `CheckoutStartResponse`

- [ ] T08 — Public `/billing/plans` Livewire page
      Arquivos: `kindrad-canvas/app/Livewire/Billing/Plans.php` (novo), `kindrad-canvas/resources/views/livewire/billing/plans.blade.php` (novo), `kindrad-canvas/routes/web.php` (alterado)
      Mudança: Livewire com `mount()` abortando 401 para guests (espelha `app/Livewire/Credits/Index.php:17-22`); `render()` retorna view com `SubscriptionPlan::active()->ordered()->with('interval')->get()`; blade renderiza card por plano com botão "Assinar [name]" chamando `subscribe(int $planId)` que invoca `StartCheckoutAction` e redireciona; rota `Route::livewire('billing/plans', Plans::class)->middleware(['auth','verified'])->name('billing.plans.index')` dentro do grupo `auth` em `routes/web.php`.
      Cobre: RF-02, RF-03, UI-03
      Acceptance criteria: GET `/billing/plans` como guest redireciona para login; usuário autenticado vê apenas planos com `is_active=true` ordenados por `sort_order` ASC, `id` ASC; plano com `is_active=false` não aparece no markup; clique em "Assinar [plan]" sob `Cashier::fake()` produz redirect para `https://checkout.stripe.com/...`.
      Testes: `tests/Feature/Billing/PlansPageTest.php` — cobre todos os cenários acima.

## Phase 7: Webhook controller + dispatcher + handler actions

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-04, RF-05, RF-06, RF-07, RF-08, RF-10, RF-11, RNF-01, RNF-02, CT-02)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — schema `StripeEvent` e rota `POST /stripe/webhook`

- [ ] T10 — Webhook controller + dispatcher + signature verification + idempotency + handler actions
      Arquivos: `kindrad-canvas/app/Http/Controllers/Billing/StripeWebhookController.php` (novo), `kindrad-canvas/app/Billing/StripeWebhookDispatcher.php` (novo), `kindrad-canvas/app/Actions/Billing/SubscriptionCreditGrantAction.php` (novo), `kindrad-canvas/app/Actions/Billing/MarkSubscriptionPastDueAction.php` (novo), `kindrad-canvas/app/Actions/Billing/SyncSubscriptionAction.php` (novo)
      Mudança: controller single-action `__invoke(Request)` que verifica `Stripe-Signature` via Cashier (API exata a confirmar durante implementação) e abort 400 em falha; persiste `StripeEvent::firstOrCreate(['event_id' => $event->id], [...])` antes de qualquer side effect; em hit do firstOrCreate, retorna 200 (idempotência); chama `Context::add('stripe_event_id', $event->id)`; dispatcha por `$event->type` via `StripeWebhookDispatcher` mapeando: `invoice.payment_succeeded` → `SubscriptionCreditGrantAction`, `invoice.payment_failed` → `MarkSubscriptionPastDueAction`, `customer.subscription.{created,updated,deleted}` → `SyncSubscriptionAction`, tipos desconhecidos → log info + 200. Ações: `SubscriptionCreditGrantAction::handle(array $invoice)` resolve subscription via `Subscription::where('stripe_id', $invoice['subscription'])->firstOrFail()` e chama `CreditLedger::subscriptionGrant($sub, $sub->subscriptionPlan->credits_per_period)`, atualiza `current_period_start/end`. `MarkSubscriptionPastDueAction` seta `status_id` para slug `past_due`. `SyncSubscriptionAction` upserts por `stripe_id`, resolve `subscription_plan_id` via `stripe_price_id`, atualiza `status_id`, `current_period_start/end`, `cancel_at_period_end`; detecta upgrade (nova plan via `stripe_price_id` + `proration_behavior=create_prorations`) → chama `SubscriptionCreditGrantAction` com credits da nova plan; em `deleted`, seta `status_id` para `canceled`.
      Cobre: RF-04, RF-05, RF-06, RF-07, RF-08, RF-10, RF-11, RNF-01, RNF-02, CT-02
      Acceptance criteria: POST `/stripe/webhook` sem `Stripe-Signature` retorna 400 com zero rows em `stripe_events` e `credit_transactions`; POST com signature inválida retorna 400/403 sem DB write; POST com signature válida e `invoice.payment_succeeded` retorna 200, cria exatamente 1 row em `credit_transactions` com `+credits_per_period` e avança `current_period_end`; mesmo `event_id` re-despachado retorna 200 com zero rows adicionais em `stripe_events`, `credit_transactions`, `subscriptions`; `invoice.payment_failed` seta `status_id` para `past_due` sem alterar balance; `customer.subscription.deleted` seta `status_id` para `canceled` e `invoice.payment_succeeded` subsequente para o mesmo `stripe_id` escreve 0 rows; `customer.subscription.updated` com novo `stripe_price_id` resolvendo para plan superior e `proration_behavior=create_prorations` produz exatamente 1 row `subscription_credit_grant` para a nova plan na mesma invocação; tipo desconhecido → 200 sem DB write.
      Testes: `tests/Feature/Billing/StripeWebhookTest.php` — cobre todos os cenários acima com `Cashier::fake()` (ou `Stripe::fake()`).

## Phase 8: Webhook route + CSRF exemption

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-11, RNF-02, CT-01)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — rota `POST /stripe/webhook`

- [ ] T11 — Add webhook route + CSRF exemption wiring
      Arquivos: `kindrad-canvas/routes/web.php` (alterado)
      Mudança: registrar `Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);` fora do grupo `auth`, antes de `require __DIR__.'/settings.php'`.
      Cobre: RF-11, RNF-02, CT-01
      Acceptance criteria: `php artisan route:list --name=stripe.webhook` mostra a rota POST com middleware reduzido (sem `auth`, sem `verified`, sem CSRF); `route('stripe.webhook')` retorna URL absoluta terminando em `/stripe/webhook`.
      Testes: `tests/Feature/Billing/StripeWebhookTest.php` — já cobre via cenários de T10 que dependem da rota existir.

## Phase 9: EnsureStripePriceAction + admin Plans CRUD

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-01, RF-02, RF-03, UI-01, UI-02, RNF-04)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — schemas `SubscriptionPlan`, `PlanUpsertRequest`, e rotas admin

- [ ] T12 — Auto-create Stripe Product + Price on admin plan save
      Arquivos: `kindrad-canvas/app/Actions/Billing/EnsureStripePriceAction.php` (novo), `kindrad-canvas/app/Livewire/Admin/Plans/Create.php` (alterado), `kindrad-canvas/app/Livewire/Admin/Plans/Edit.php` (alterado)
      Mudança: action `handle(SubscriptionPlan $plan): SubscriptionPlan` que quando `$plan->stripe_price_id` é null cria Stripe Product + Price; wired em `save()` dos forms admin; wrapped em `DB::transaction`; em Pest, swap via `$this->app->bind(EnsureStripePriceAction::class, ...)` para retornar IDs determinísticos.
      Cobre: RF-01, RF-03
      Acceptance criteria: sob fake, após `Create::save()` o plan criado tem `stripe_product_id` e `stripe_price_id` não-nulos; falha da API Stripe em produção é surfaced via `$this->addError()` no Livewire (não silenciosa).
      Testes: extensão em `tests/Feature/Admin/Plans/CreateTest.php` — asserta `stripe_product_id` e `stripe_price_id` não-nulos após save.
- [ ] T13 — Admin Plans CRUD (Index / Create / Edit Livewire)
      Arquivos: `kindrad-canvas/app/Livewire/Admin/Plans/Index.php` (novo), `kindrad-canvas/app/Livewire/Admin/Plans/Create.php` (novo), `kindrad-canvas/app/Livewire/Admin/Plans/Edit.php` (novo), `kindrad-canvas/resources/views/livewire/admin/plans/{index,create,edit}.blade.php` (novo), `kindrad-canvas/routes/web.php` (alterado)
      Mudança: cada Livewire tem `mount()` com `abort_unless(auth()->user()?->is_admin === true, 403)` (espelha `app/Livewire/Admin/Products/Index.php:17`); `Index` usa `SubscriptionPlan::with('interval')->ordered()->get()`, expõe `create()`, `edit(int $id)`, `toggleActive(int $id, AuditLogger $audit)` que flipa `is_active` e escreve audit row com action slug `edit_subscription_plan`; `Create::save(AuditLogger $audit, EnsureStripePriceAction $price)` valida campos conforme PLAN T13 (incluindo `currency` ∈ BRL e `credits_per_period` ≥ 1), chama action, escreve audit; `Edit::save(AuditLogger $audit, EnsureStripePriceAction $price)` idem com `unique:subscription_plans,slug,{id}` e payload before/after diff (espelha `app/Livewire/Admin/Products/Edit.php:60-82`); layout `components.layouts.admin`; rotas `Route::livewire('plans', Index::class)->name('plans.index')`, `Route::livewire('plans/create', Create::class)->name('plans.create')`, `Route::livewire('plans/{plan}/edit', Edit::class)->name('plans.edit')` todas dentro do grupo `admin` em `routes/web.php`.
      Cobre: RF-01, RF-02, UI-01, UI-02
      Acceptance criteria: GET `/admin/plans` como guest redireciona para login; como usuário não-admin retorna 403; como admin renderiza tabela com colunas `name`, `price_cents` formatado `R$ 19,90`, `credits_per_period`, `interval` label, badge `is_active`, `sort_order`; POST com `credits_per_period=0` mostra validation error e persiste 0 rows; POST com `currency=USD` mostra validation error (RNF-04); POST válido persiste 1 row em `subscription_plans` e 1 row em `audit_logs` com `action_id` resolvendo para slug `edit_subscription_plan`; toggle em plan ativo muda `is_active` para false e reflete no badge após redirect.
      Testes: `tests/Feature/Admin/Plans/{IndexTest,CreateTest,EditTest,ToggleActiveTest}.php` — cobre todos os cenários acima.

## Phase 10: Admin Subscriptions viewer + SubscriptionPolicy

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RF-12, RF-13, UI-05)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — schema `SubscriptionRow` e rota `GET /admin/subscriptions`

- [ ] T14 — Admin Subscriptions viewer
      Arquivos: `kindrad-canvas/app/Livewire/Admin/Subscriptions/Index.php` (novo), `kindrad-canvas/resources/views/livewire/admin/subscriptions/index.blade.php` (novo), `kindrad-canvas/routes/web.php` (alterado)
      Mudança: `Index::mount()` aborta 403 para não-admin; `render()` retorna view com `Subscription::with(['user','subscriptionPlan','status'])->latest('id')->paginate(25)`; view renderiza colunas `user.email`, `subscriptionPlan.name`, `status.name` label, `current_period_end` formatado (`->format('Y-m-d')`), badge Flux para `cancel_at_period_end`; layout `components.layouts.admin`; rota `Route::livewire('subscriptions', Index::class)->name('subscriptions.index')` dentro do grupo `admin`.
      Cobre: RF-12, UI-05
      Acceptance criteria: GET `/admin/subscriptions` como guest redireciona para login; como usuário não-admin retorna 403; como admin renderiza todas as subscriptions com 5 colunas visíveis; subscription com `cancel_at_period_end=true` mostra badge `Cancelará em fim do período`.
      Testes: `tests/Feature/Admin/Subscriptions/IndexTest.php` — cobre todos os cenários acima.
- [ ] T15 — `SubscriptionPolicy` (cross-tenant access guard)
      Arquivos: `kindrad-canvas/app/Policies/SubscriptionPolicy.php` (novo)
      Mudança: policy com `view(User $actor, Subscription $sub): bool` retornando `$actor->id === $sub->user_id || $actor->is_admin === true`; `manage(...)` mesmo corpo; auto-discovery Laravel 13 em `app/Policies`; Livewire de billing que carrega subscription chama `abort_unless(Gate::allows('view', $sub), 403)` quando o id vem de input externo.
      Cobre: RF-13
      Acceptance criteria: `$owner->can('view', $subscription)` é true; `$admin->can('view', $subscription)` é true; `$other->can('view', $subscription)` é false (Policy::deny); tentativa via Livewire de carregar subscription de outro usuário retorna HTTP 403.
      Testes: `tests/Feature/Policies/SubscriptionPolicyTest.php` — owner/admin/other scenarios.

## Phase 11: Pest test suite + final wiring

Antes de implementar, leia:
1. `.spec/features/stripe-subscription-billing/SPEC.md` — requisitos RIGID que esta fase cobre (RNF-01, RNF-02, RNF-03, RNF-04 e validação de cada AC das tabelas RF/UI)
2. `.spec/features/stripe-subscription-billing/PLAN.md` — decomposição completa, dependências e riscos
3. `.spec/features/stripe-subscription-billing/openapi.yaml` — referência cruzada dos endpoints

- [ ] T16 — Pest test suite covering every acceptance criterion
      Arquivos: testes já criados nas fases anteriores (`tests/Feature/Billing/StripeWebhookTest.php`, `tests/Feature/Billing/PlansPageTest.php`, `tests/Feature/Billing/BillingDashboardTest.php`, `tests/Feature/Admin/Plans/*Test.php`, `tests/Feature/Admin/Subscriptions/*Test.php`, `tests/Feature/Actions/Billing/*Test.php`, `tests/Feature/Services/CreditLedgerSubscriptionGrantTest.php`, `tests/Feature/Models/{SubscriptionPlanTest,SubscriptionRelationTest,StripeEventTest}.php`, `tests/Feature/Seeders/CatalogSeederBillingTest.php`, `tests/Feature/Policies/SubscriptionPolicyTest.php`, `tests/Feature/Billing/UserIsBillableTest.php`)
      Mudança: tarefa de consolidação — rodar `php artisan test --compact --filter=Stripe` (RNF-03 verificável: zero network egress para `api.stripe.com`); rodar `php artisan test` full e corrigir regressões; rodar `vendor/bin/pint --dirty --format agent`; rodar `phpstan analyse` (tipos limpos).
      Cobre: RNF-01, RNF-02, RNF-03, RNF-04 + cada AC de RF-01..RF-13 e UI-01..UI-05
      Acceptance criteria: `php artisan test --compact` retorna 0 failures; `php artisan test --filter=Stripe` retorna 0 failures; `vendor/bin/pint --dirty` não reporta diffs; `phpstan analyse` retorna 0 errors na área alterada; nenhum teste referencia `api.stripe.com` real (verificável por grep em `tests/` retornando apenas usos de `Cashier::fake()`/`Stripe::fake()`/fixtures locais).
      Testes: este task é a verificação final; os testes que ele cobre são os criados em T01..T15.
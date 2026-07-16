# Phases: dunning-and-communication

Gerado por /plan a partir de PLAN.md — view executável para `./ralph.sh .spec/features/dunning-and-communication/PHASES.md`.

## Phase 1: Foundation — migration, model, config, exception, guard

Antes de implementar, leia:
1. `.spec/features/dunning-and-communication/SPEC.md` — requisitos RIGID RF-03, RF-04, RF-05 e CT-03/CT-05 cobertos nesta fase
2. `.spec/features/dunning-and-communication/PLAN.md` — decomposição completa, dependências e riscos (especialmente Risks "Migration `notifications` ausente" e "`BillingAccessGuard` injeta latência")

- [ ] T02 — Criar migration `payment_failures`, model `PaymentFailure`, factory e helper `Subscription::isPastDueAndExpired`
      Arquivos: `database/migrations/2026_07_16_000001_create_payment_failures_table.php` (novo, via `php artisan make:migration --no-interaction --create=payment_failures create_payment_failures_table`), `database/factories/PaymentFailureFactory.php` (novo), `app/Models/PaymentFailure.php` (novo), `app/Models/Subscription.php` (alterado)
      Mudança: migration com colunas `id`, `user_id` (FK), `subscription_id` (FK), `stripe_invoice_id` (string nullable index), `stripe_charge_id` (string nullable index), `attempted_at` (timestamp nullable), `reason` (string nullable), `payload` (json nullable), `created_at` (sem `updated_at`); index composto `(subscription_id, attempted_at)`; model com `$fillable` conforme CT-03 e `casts(['attempted_at' => 'datetime', 'payload' => 'array'])`; relations `user()`/`subscription()` belongsTo; factory com estados `forSubscription`/`forUser`; adicionar método público `isPastDueAndExpired(?int $graceDays = null): bool` em `Subscription` calculando `$expiry = $this->ends_at ?? $this->current_period_end?->copy()->addDays($graceDays ?? (int) config('billing.grace_days', 7))` e retornando `now()->greaterThan($expiry)` somente quando `statusSlug() === 'past_due'`.
      Cobre: RF-03, RF-04, RF-05, CT-03
      Acceptance criteria: `php artisan migrate:status` (ou `php artisan migrate --pretend`) mostra a tabela `payment_failures` com todas as colunas de CT-03; `Subscription::factory()->pastDue()->create()` → `(new Subscription)->refresh()->isPastDueAndExpired(0)` retorna `false` com `current_period_end` futuro e `true` com `current_period_end` no passado; factory `PaymentFailure::factory()->forSubscription($sub)->create()` persiste todos os 7 campos exigidos.
      Testes: `tests/Feature/Billing/PaymentFailureModelTest.php` — casts + fills + relation.
- [ ] T07 — Criar `config/billing.php` com `grace_days`
      Arquivos: `config/billing.php` (novo)
      Mudança: arquivo único retornando `['grace_days' => (int) env('BILLING_GRACE_DAYS', 7)]`.
      Cobre: RF-03 (default 7), RF-04 (configurabilidade)
      Acceptance criteria: `php artisan config:show billing.grace_days` retorna `7`; `config(['billing.grace_days' => 3])` é refletido por `Subscription::isPastDueAndExpired()`.
      Testes: indireto via AC3 e AC4 em `DunningTest`.
- [ ] T03 — Criar `BillingAccessDeniedException` e `BillingAccessGuard` e hook em `SubmitGeneration`
      Arquivos: `app/Exceptions/BillingAccessDeniedException.php` (novo, raiz `app/Exceptions/`, espelha `app/Services/Exceptions/CreditInsufficientException.php`), `app/Services/Billing/BillingAccessGuard.php` (novo, espelha `app/Services/CreditLedger.php`), `app/Actions/Generation/SubmitGeneration.php` (alterado em `:32`/após checagem de autorização)
      Mudança: exception `extends RuntimeException` com factory `for(User $user): self` e mensagem contendo `past_due` + data de expiração; service com `ensureCanSubmit(User $user): void` que carrega a assinatura mais recente e lança `BillingAccessDeniedException::for($user)` quando `isPastDueAndExpired()` for `true`; `SubmitGeneration` recebe `BillingAccessGuard` no constructor (readonly promoted) e chama `$this->guard->ensureCanSubmit($user)` entre a checagem de `AuthorizationException` em `:32` e a verificação de `credit_balance` em `:35`.
      Cobre: RF-04, CT-05
      Acceptance criteria: chamada direta de `app(BillingAccessGuard::class)->ensureCanSubmit($userSemAssinatura)` não lança; com subscription `past_due` e `current_period_end` no passado lança `BillingAccessDeniedException`; com subscription `past_due` e `current_period_end` futuro não lança; `SubmitGeneration::execute` propaga a mesma exception sem criar `Generation` (verificar `assertDatabaseCount('generations', 0)`).
      Testes: `tests/Feature/Billing/DunningTest.php` — AC4.

## Phase 2: Notifications, email e wiring do dispatcher/action

Antes de implementar, leia:
1. `.spec/features/dunning-and-communication/SPEC.md` — requisitos RIGID RF-01, RF-05, RF-07 e RNF-01
2. `.spec/features/dunning-and-communication/PLAN.md` — Tasks T01 e T06, especialmente Risks "Alterar assinatura de `MarkSubscriptionPastDueAction`" e "Migration `notifications` ausente"
3. (sem contrato formal nesta feature — convenção interna: shape `{subscription_id, stripe_status, grace_expires_at, message}` em `toDatabase`)

- [ ] T06 — Criar `PaymentFailedNotification`, `PaymentFailedMail` (e opcional `PaymentActionRequiredMail`) + migration `notifications`
      Arquivos: `app/Notifications/PaymentFailedNotification.php` (novo), `app/Mail/PaymentFailedMail.php` (novo), `app/Mail/PaymentActionRequiredMail.php` (novo, FLEXIBLE — pode ser placeholder), `resources/views/mail/payment-failed.blade.php` (novo, opcional), `database/migrations/2026_07_16_000002_create_notifications_table.php` (novo, via `php artisan notifications:table --no-interaction`), `.env.example` (edição — bloco Brevo: MAIL_MAILER=smtp, MAIL_HOST=smtp-relay.brevo.com, MAIL_PORT=587, MAIL_USERNAME=<login>, MAIL_PASSWORD=<key>, MAIL_ENCRYPTION=tls, MAIL_FROM_ADDRESS=noreply@kindredcanvas.com, MAIL_FROM_NAME="Kindred Canvas")
      Mudança: `PaymentFailedNotification extends Notification` com `via(): array => ['database']` e `toDatabase($notifiable): array` retornando shape `{subscription_id, stripe_status, grace_expires_at, message}`; `PaymentFailedMail extends Mailable implements ShouldQueue` com `build()` usando `$this->subject(__('Atualize seu método de pagamento'))->markdown('mail.payment-failed', ['subscription' => $this->subscription])`; `PaymentActionRequiredMail` segue o mesmo padrão com assunto __('Autenticação de pagamento necessária') (FLEXIBLE); migration `notifications` no formato Laravel 13 padrão (id, type, notifiable_type/morphs, data json, read_at, timestamps).
      Cobre: RF-01, RF-07
      Acceptance criteria: `Notification::fake()` ou `auth()->user()->notify(new PaymentFailedNotification($sub))` cria uma linha em `notifications` com `notifiable_id = $user->id` e `type = PaymentFailedNotification`; `Mail::fake()` + `Mail::to($u)->queue(new PaymentFailedMail($sub))` é captado por `Mail::assertQueued(...)`; migration `notifications` aparece em `php artisan migrate:status`.
      Testes: `tests/Feature/Billing/DunningTest.php` — AC1 (notification persistida) e AC7 (Mail fake).
- [ ] T01 — Estender `StripeWebhookDispatcher` e `MarkSubscriptionPastDueAction`
      Arquivos: `app/Billing/StripeWebhookDispatcher.php` (alterado), `app/Actions/Billing/MarkSubscriptionPastDueAction.php` (alterado)
      Mudança: dispatcher adiciona case `invoice.payment_action_required` no `match` que reusa `MarkSubscriptionPastDueAction::handle((string) ($object['subscription'] ?? ''), $object, 'invoice.payment_action_required')`; o case existente `invoice.payment_failed` passa a chamar `handle(..., $object, 'invoice.payment_failed')`; action ganha assinatura `handle(string $stripeSubscriptionId, array $invoice = [], string $eventType = 'invoice.payment_failed'): ?Subscription`; dentro do bloco com subscription encontrada: (a) mantém transição para `past_due`; (b) `PaymentFailure::create([...campos CT-03...])` extraídos de `$invoice`; (c) `$subscription->user->notify(new PaymentFailedNotification($subscription))`; (d) `if (config('mail.default') !== 'log') { Mail::to($subscription->user)->queue(new PaymentFailedMail($subscription, $eventType)); }`. Preserva retorno `?Subscription` (CT-02) e RNF-01 (uma notification + um `PaymentFailure` por invocação, sem loops).
      Cobre: RF-01, RF-05, RF-07, RNF-01, CT-01, CT-02, CT-03
      Acceptance criteria: `app(StripeWebhookDispatcher::class)->dispatch(['type' => 'invoice.payment_failed', 'data' => ['object' => [...]]])` com subscription existente resulta em `subscription->refresh()->stripe_status === 'past_due'`, uma linha em `payment_failures`, uma notificação database, e (quando `mail.default !== 'log'`) um `Mail::queued`; mesmo evento com subscription desconhecida retorna sem efeitos colaterais (apenas `Log::info`); `invoice.payment_action_required` produz os mesmos efeitos.
      Testes: `tests/Feature/Billing/DunningTest.php` — AC1, AC5, AC7.

## Phase 3: UI banners e listagem administrativa

Antes de implementar, leia:
1. `.spec/features/dunning-and-communication/SPEC.md` — requisitos RIGID RF-02, RF-03, RF-05 e CT-04
2. `.spec/features/dunning-and-communication/PLAN.md` — Tasks T04 e T05, Risk "Banner dashboard quebra layout mobile"

- [ ] T04 — Banner persistente em `/dashboard` com CTA literal
      Arquivos: `resources/views/dashboard.blade.php` (alterado, inserir bloco logo após `@if (session('status'))` em `:34`)
      Mudança: dentro do wrapper `<div class="flex flex-col gap-section p-margin-page">`, antes da `dashboard-hero`, carregar `$pastDueSubscription = App\Models\Subscription::where('user_id', auth()->id())->whereHas('status', fn ($q) => $q->where('slug', 'past_due'))->latest('id')->first()` (também cobrir o caso `status_id` nulo mas `stripe_status = past_due`); se presente, renderizar `<a href="{{ route('billing.index') }}" data-test="dashboard-dunning-banner" class="…">{{ __('Atualizar método de pagamento') }}</a>` envolvendo o texto contextual.
      Cobre: RF-02, CT-04
      Acceptance criteria: `get('/dashboard')` autenticado com subscription `past_due` retorna 200 contendo `data-test="dashboard-dunning-banner"`, o texto literal `Atualizar método de pagamento` e `href` igual a `route('billing.index')`; sem subscription `past_due` o atributo `data-test="dashboard-dunning-banner"` está ausente.
      Testes: `tests/Feature/Billing/DunningTest.php` — AC2.
- [ ] T05 — Banner de grace em `/billing` e listagem de falhas em `admin.subscriptions.index`
      Arquivos: `app/Livewire/Billing/Index.php` (alterado, propriedade computada `?CarbonInterface $graceExpiresAt`), `resources/views/livewire/billing/index.blade.php` (alterado, inserir banner acima do `<header>` em `:19`), `app/Livewire/Admin/Subscriptions/Index.php` (alterado, eager load `paymentFailures`), `resources/views/livewire/admin/subscriptions/index.blade.php` (alterado, nova coluna `Falhas recentes` ou expansão)
      Mudança: `Billing\Index::getGraceExpiresAtProperty()` retorna `$this->subscription?->ends_at ?? $this->subscription?->current_period_end?->copy()->addDays((int) config('billing.grace_days', 7))` apenas quando `statusSlug() === 'past_due'`; view billing renderiza `<flux:callout variant="warning" icon="exclamation-triangle" data-test="billing-dunning-banner">` com heading `__('Atualize seu pagamento até :date', ['date' => $graceExpiresAt->format('d/m/Y')])`; `Admin\Subscriptions\Index::render()` adiciona `->with(['paymentFailures' => fn ($q) => $q->latest('attempted_at')->limit(5)])`; view admin ganha coluna/seção `Falhas recentes` listando `attempted_at` (formatado) e `reason` das últimas 5 falhas com `data-test="admin-subscription-failures-{{ $sub->id }}"`.
      Cobre: RF-03, RF-05, CT-04
      Acceptance criteria: `Livewire::test(Billing\Index::class)` com subscription `past_due` + `ends_at` vê `data-test="billing-dunning-banner"` e a data formatada de `ends_at`; sem `ends_at` mas com `current_period_end` futuro e `config(['billing.grace_days' => 5])` mostra `current_period_end + 5 dias`; `get('/admin/subscriptions')` autenticado como admin mostra `attempted_at` e `reason` das falhas da assinatura na coluna `Falhas recentes`.
      Testes: `tests/Feature/Billing/DunningTest.php` — AC3, AC5.

## Phase 4: Tests Pest + lint + polimento

Antes de implementar, leia:
1. `.spec/features/dunning-and-communication/SPEC.md` — RF-06 (suite executável) e Traceability Summary
2. `.spec/features/dunning-and-communication/PLAN.md` — Task T08, Risk "`Mail::queue` enfileira em produção mas CI usa `sync`"

- [ ] T08 — Suíte Pest `tests/Feature/Billing/DunningTest.php` + lint
      Arquivos: `tests/Feature/Billing/DunningTest.php` (novo), `tests/Feature/Billing/PaymentFailureModelTest.php` (novo, opcional)
      Mudança: arquivo Pest cobrindo os 7 ACs nomeados na SPEC §"Acceptance Tests" — `it_marks_subscription_past_due_and_creates_database_notification_for_failed_invoice`, `it_renders_persistent_dashboard_dunning_banner_with_billing_cta`, `it_renders_grace_expiry_on_billing_page_using_ends_at_or_configured_period` (usa `Carbon::setTestNow`), `it_blocks_generation_after_grace_and_allows_generation_within_grace`, `it_records_payment_failure_and_displays_it_in_admin_subscriptions`, `it_treats_payment_action_required_as_dunning_and_queues_mail_except_for_log_mailer` (dataset `[['smtp'], ['log']]`); setup com `uses(RefreshDatabase::class)` ou `beforeEach(fn () => $this->refreshDatabase())`; factories `Subscription::factory()->pastDue()` e `PaymentFailure::factory()->forSubscription(...)`; `actingAs` para user e admin (`is_admin => true`); `Mail::fake()` no AC7; após edição rodar `vendor/bin/pint --dirty --format agent` e `php artisan test --compact --filter=DunningTest` como gate final.
      Cobre: RF-06 (e indiretamente RF-01..RF-05, RF-07)
      Acceptance criteria: `php artisan test --compact --filter=DunningTest` exibe os 7 testes passando; `vendor/bin/pint --dirty --format agent` reporta `✓` para todos os arquivos PHP novos/alterados.
      Testes: auto-verifica via runner; meta-teste opcional AC6 pode usar `dataset` para iterar sobre os 7 nomes e `expect(test_exists(...))->toBeTrue()`.
- [ ] T09 (FLEXIBLE) — Comando `CheckExpiredDunning`
      Arquivos: `app/Console/Commands/CheckExpiredDunning.php` (novo, opcional), `routes/console.php` (alterado, opcional — registro `$schedule->command(...)` diário)
      Mudança: comando idempotente que varre assinaturas `past_due` e dispara notificação adicional "acesso bloqueado" para usuários cujo grace expirou nas últimas 24h; nada além disso. Pular este checkbox se a equipe preferir adiar (FLEXIBLE conforme SPEC).
      Cobre: nenhum RIGID; higiene operacional
      Acceptance criteria: `php artisan dunning:check-expired` é executável e não lança; rodar duas vezes consecutivas produz o mesmo número de notificações (idempotência).
      Testes: cobertura fora do escopo deste plano.
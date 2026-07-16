# Phases: welcome-plans-section

Gerado por /plan a partir de PLAN.md — view executável para `./ralph.sh .spec/features/welcome-plans-section/PHASES.md`.

## Phase 1: Implementação e verificação da seção (dual query + toggle Alpine + empty state)

Antes de implementar, leia:
1. `.spec/features/welcome-plans-section/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/welcome-plans-section/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T02 — Criar SFC de planos guest-safe com dual query + toggle Alpine + cards compatíveis
      Arquivos: `resources/views/components/⚡welcome/plans.blade.php`; opcionalmente `app/Livewire/Welcome/Plans.php`
      Mudança: Criar o SFC Livewire 4 sob `resources/views/components/`, executar **uma única `render()`** que retorna `['plans' => ['month' => …, 'year' => …]]`, cada lista com `SubscriptionPlan::query()->active()->ordered()->with('interval')->whereHas('interval', fn ($q) => $q->where('slug', '<key>'))->limit(3)->get()`; não abortar guests; renderizar toggle Alpine (`x-data="{ interval: 'month' }"` + dois botões `x-on:click`) sem `wire:click`; renderizar dois grids via `x-show`; renderizar até três cards visualmente alinhados ao billing por grid; bifurcar CTAs para `register`/`billing.index` por auth, **independente do intervalo visível**.
      Cobre: RF-02, RF-03, RF-04, RF-05, RF-07, RNF-01, RNF-02, RNF-03, RNF-04
      Acceptance criteria: O SFC guest-safe é auto-descoberto; **uma única `render()`** carrega ambas as listas; cada lista limitada a três; toggle Alpine padrão `month` sem round-trip; cards contêm badge Popular no primeiro, preço `text-4xl`, créditos com `bolt`, CTA full-width e os tokens `font-serif`, `glass-card`, `gradient-generate`, `text-on-surface`, `border-white/10`; nenhuma rota de guest é abortada; nenhum `wire:click` dentro do container do toggle.
      Testes: `tests/Feature/Livewire/WelcomePlansTest.php` — filtrar ativos/ordem/limite-dual, renderizar como guest e auth em ambos os grids, verificar tokens, query log e ausência de `wire:click`.

- [ ] T01 — Compor a seção no fluxo da Welcome
      Arquivos: `resources/views/welcome.blade.php`
      Mudança: Inserir `<livewire:welcome.plans />` após o fechamento de `#how-it-works` e antes da abertura de `<footer>`, sem modificar as seções vizinhas.
      Cobre: RF-01, RF-03
      Acceptance criteria: A resposta de `GET /` contém a seção de planos entre o marcador `#how-it-works` e o `<footer>`, e o tag resolve sem exceção.
      Testes: `tests/Feature/Livewire/WelcomePlansTest.php` — `it_renders_plans_below_how_it_works` e `it_uses_sfc_welcome_plans_component`.

- [ ] T04 — Empty state por intervalo (RF-08)
      Arquivos: `resources/views/components/⚡welcome/plans.blade.php` (mesmo arquivo de T02 — entrega conjunta ou commit imediatamente posterior)
      Mudança: Quando `count($plans[$key]) === 0` para `key ∈ {'month','year'}`, o grid correspondente renderiza um único card muted contendo `material-symbols-outlined inventory_2` e a mensagem localizada equivalente a `__('No plans available for this interval right now.')`. O card vazio compartilha `glass-card` + `text-on-surface/60` + `border-white/10`. Aplicação **independente por intervalo**.
      Cobre: RF-08
      Acceptance criteria: O card vazio aparece quando a lista do intervalo está vazia e **não** aparece quando há ao menos um plano; verifica-se tanto para `month` quanto para `year` de forma isolada.
      Testes: implícito em AC7 (ano vazio → card vazio presente em `year`, cards normais em `month`) e em AC8 (ambos populados → card vazio ausente).

- [ ] T03 — Expandir a cobertura Pest para 8 testes (incluindo toggle e cap-dual)
      Arquivos: `tests/Feature/Livewire/WelcomePlansTest.php`
      Mudança: Atualizar o arquivo para conter **8 testes** Pest: os 6 prévios (posição; filtro+ordem+limite-dual via 4 month + 4 year; SFC; CTA guest/auth; tokens; factory) **mais** `it_renders_monthly_and_yearly_grids_with_toggle` (AC7 — wrapper `x-data`, ambos grids, ausência de `wire:click` no container) e `it_caps_each_interval_at_three_plans` (AC8 — 4 month ativos + 4 year ativos + 1 inativo, cada grid exatamente 3, inativo ausente). Manter `RefreshDatabase`, `actingAs()` e query log.
      Cobre: RF-01, RF-02, RF-03, RF-04, RF-05, RF-06, RF-07, RF-08, RNF-01, RNF-02, RNF-04
      Acceptance criteria: `php artisan test --compact --filter=WelcomePlansTest` passa com **todos os 8 cenários** verdes, incluindo o toggle e o cap-dual; `vendor/bin/pint --dirty --format agent` termina com sucesso.
      Testes: `php artisan test --compact --filter=WelcomePlansTest` (conjunto); `php artisan test --compact --filter=it_renders_monthly_and_yearly_grids_with_toggle` (AC7 isolado); `php artisan test --compact --filter=it_caps_each_interval_at_three_plans` (AC8 isolado); `vendor/bin/pint --dirty --format agent`.

- Goal: Entregar a seção de planos pública, com dual query, toggle Alpine client-side e empty state por intervalo, integrada à Welcome, com cobertura Pest completa (8 testes).
- Steps:
  1. `resources/views/components/⚡welcome/plans.blade.php` (novo) — implementar SFC, dual query no `render()`, toggle Alpine, empty state por grid, cards, tokens e CTAs; opcionalmente `app/Livewire/Welcome/Plans.php` (novo) para classe.
  2. `resources/views/welcome.blade.php` — inserir o componente entre How it works e footer (linha ~224).
  3. `tests/Feature/Livewire/WelcomePlansTest.php` (novo/expandido) — adicionar/ratificar AC1–AC8 incluindo AC7 (toggle) e AC8 (cap-dual).
- Validation: `vendor/bin/pint --dirty --format agent` deve terminar com exit code 0; `php artisan test --compact --filter=WelcomePlansTest` deve terminar com exit code 0 e os **8 testes verdes**; isolamentos de AC7 (`it_renders_monthly_and_yearly_grids_with_toggle`) e AC8 (`it_caps_each_interval_at_three_plans`) também verdes.
- Parallelizable: no — T02 define o componente (e entrega T04 embutido) antes de T01; T03 depende da composição e implementação. Dentro da fase, T02 é a unidade inicial independente.
- Touches: `resources/views/components/⚡welcome/plans.blade.php`, `app/Livewire/Welcome/Plans.php` (opcional), `resources/views/welcome.blade.php`, `tests/Feature/Livewire/WelcomePlansTest.php`.

## Phase 2: Hardening de query log e ausência de round-trip

Antes de implementar, leia: SPEC.md (RNF-01, RNF-04) e PLAN.md (seção "Test strategy").

- [ ] T05 — Reauditar query log durante render() e ausência de round-trip no toggle
      Arquivos: nenhum novo (verificação)
      Mudança: Sem alteração de código. Re-executar `DB::enableQueryLog()` no teste e confirmar query count ≤ 5 para o `render()` (RNF-01); inspecionar o markup do toggle em AC7 e confirmar ausência de `wire:click`/`wire:model`/`@click.live`/qualquer chamada Livewire (RNF-04). Se alguma assertion falhar, abrir um item corretivo e voltar à Phase 1.
      Cobre: RNF-01, RNF-04
      Acceptance criteria: Os 8 testes permanecem verdes; logs de query ≤ 5; markup do toggle Alpine continua sem `wire:*`.
      Testes: `php artisan test --compact --filter=WelcomePlansTest` + `php artisan test --compact --filter=it_renders_monthly_and_yearly_grids_with_toggle` (re-execução).
- Goal: Garantir que a entrega não introduziu regressões de performance (N+1) nem round-trip acidental no toggle Alpine.
- Steps:
  1. Rodar o conjunto Pest novamente e arquivar a contagem de queries observada no log.
  2. Inspecionar visualmente o HTML renderizado do toggle para confirmar atributos Alpine puros.
- Validation: 8 testes verdes; sem itens corretivos abertos a partir da reauditoria.
- Parallelizable: yes — esta fase é exclusivamente verificação; pode ser incorporada ao critério de "done" da Phase 1.
- Touches: nenhum arquivo de produção; apenas `tests/Feature/Livewire/WelcomePlansTest.php` se uma assertion corretiva for necessária.

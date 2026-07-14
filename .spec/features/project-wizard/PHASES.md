# Phases: project-wizard

<!-- inputs: SPEC.md@sha256:1f902d0de79c PLAN.md@sha256:1f902d0de79c -->

> View executável para `./ralph.sh`. Cada `## Phase N:` corresponde a uma seção da tabela `## Execution Phases` em `PLAN.md`. A heading regex é estrita: `^## Phase [0-9]+: ` e `^### Phase [0-9]+\.[0-9]+:`.

> **Status (2026-07-14):** 33/33 tasks `[x]`. Verificado em `a764485` — `vendor/bin/pint --dirty` limpo, `php artisan test --compact` 201/202 passing (1 skip CI-only em `WizardPerformanceTest::picker_render_time_best_effort_*`). Snapshot detalhado em `.spec/features/project-wizard-resume/STATUS.md`. Próximas melhorias (não-bloqueantes) catalogadas em STATUS §5.2 (NEW-A..N).

**Pré-requisitos upstream (já mergeados, validados pelo fato de os modelos e migrations existirem):**

- Phase 1 — Database Foundation (`projects`, `source_images`, `categories`, `styles`, `layouts`, `category_styles`, `style_layouts`, `project_modes`, `project_statuses`, category/style/layout statuses, `products`). **CRÍTICO** — sem `projects` o `mount()` falha.
- Phase 1.6 — Catalog Seeder (mug + 6 categories + 5 styles + 4 layouts + pivots). **CRÍTICO** — sem seed os pickers mostram empty state.
- Phase 2.1 — Eloquent models de catálogo (`Product`, `Category`, `Style`, `Layout`, `ProjectMode`, `CategoryStatus`, `StyleStatus`, `LayoutStatus`). **CRÍTICO**.
- Phase 2.3 — Model `Project` com `SoftDeletes`, casts `inputs`/`first_generated_at`, relationships (`user`, `product`, `category`, `style`, `layout`, `mode`, `status`, `sourceImage`). **CRÍTICO**.
- Phase 2.5 — `ProjectPolicy::view|update` registrado + `EnsureAdmin` middleware. **CRÍTICO** para REQ-09.
- Phase 3 — Auth (Fortify signup com grant de créditos, OAuth). Suave — testes podem usar factories.
- Phase 4.1 — Dashboard com CTA `New Project` → `route('projects.new')`. Suave — rota é diretamente endereçável.
- Phase 5 — Admin Catalog CRUD. Suave — afeta a qualidade de e2e mas não bloqueia construção.

Se algum **CRÍTICO** faltar, todas as fases abaixo bloqueiam. O build não pode prosseguir.

**Convenções deste arquivo:**
- `## Phase N:` — fase executável ralph (top-level).
- `### Phase N.M:` — sub-fase interna ao `## Phase N` (legado opcional — aqui usamos para agrupar tarefas fortemente acopladas).
- Tasks marcadas `- [x] **Task:**` com sub-bullets `**Acceptance criteria:**`, `**Feature tests:**` (lógica de negócio) ou `**Design ref:**` (frontend-only), e `**Traces:**` de volta ao SPEC.
- Após completar a fase, rodar `vendor/bin/pint --dirty --format agent` e `php artisan test --compact --filter=Projects` antes de marcar done (Boost rules).

---

## Phase 1: Wizard skeleton + wizard-only layout + route + parent component + progress bar

Antes de implementar, leia:
1. `.spec/features/project-wizard/SPEC.md` — REQ-01, REQ-09, REQ-10, REQ-U1, REQ-U2; § "AS IS" / "TO BE"
2. `.spec/features/project-wizard/PLAN.md` — "Architecture Decisions", "Risk Register"
3. `.spec/init/design/screens.md` — S3.1 Top Progress Indicator
4. `.spec/init/design/components.md` — A.3 Wizard Topbar, A.4 Wizard Footer, A.6 Wizard Shell, B.5 Wizard Tile, custom progress bar
5. `.spec/init/design/tokens.md` — `.selection-glow`, `.glass-card`, paleta `primary #c0c1ff`

### Phase 1.1: Wizard-only layout shell + topbar + footer + progress bar components

- [x] **Task:** Criar wizard layout shell vazio com topbar + sticky footer (sem step content ainda).
      Arquivos:
      - `kindrad-canvas/resources/views/layouts/wizard.blade.php` (novo)
      - `kindrad-canvas/resources/views/components/layout/wizard-topbar.blade.php` (novo — A.3)
      - `kindrad-canvas/resources/views/components/layout/wizard-footer.blade.php` (novo — A.4)
      Mudança: Layout Blade minimalista (`<html class="dark">`, mesmo stack de fonts do `layouts/app.blade.php`); inclui `wizard-topbar`, slot `{{ $slot }}` centralizado em `max-w-5xl`, e `wizard-footer` sticky. Topbar mostra logo (mini paleta) + Exit (ghost button). Footer mostra Back (ghost, disabled quando `$step === 1` no futuro) + Current Selection placeholder + Continue (primary pill).
      Cobre: REQ-U1
      Acceptance criteria:
      - View `wizard` layout renderiza topbar + slot + footer; sidebar ausência confirmada em DOM (`<aside>` não presente).
      - Sticky footer permanece visível ao rolar main canvas.
      - Não há `<head>` duplicado quando usado dentro de Livewire component view.
      Feature tests: n/a (visual shell)
      Design ref: A.3 (`wizard-topbar`), A.4 (`wizard-footer`), A.6 (`layouts/wizard.blade.php`); tokens `primary #c0c1ff`, `surface-container`, `outline-variant`; `.glass-card` reaproveitada do app shell.
      Traces: SPEC REQ-U1, screens.md S3.3

- [x] **Task:** Criar Blade component de progress bar customizado que aceita `step` (int 1-7) e `total` (int 7).
      Arquivos:
      - `kindrad-canvas/resources/views/components/wizard/progress-bar.blade.php` (novo)
      Mudança: Renderiza barra com `h-[2px] bg-surface-container-highest rounded-full` e fill `bg-primary shadow-[0_0_10px_rgba(192,193,255,0.6)]` width = `(step / total) * 100%`. Inclui label `STEP 0{step} OF 07` (mono-sm primary) + section name (mono-sm on-surface-variant, passado via prop `sectionName`).
      Cobre: REQ-U2
      Acceptance criteria:
      - Em `step=3, total=7`, o fill width computa para 42.857 % (assert via `assertSeeText` e `assertSee` no Blade compilado).
      - Label renderiza exatamente `STEP 03 OF 07` (zero-padded).
      Feature tests: `tests/Feature/Projects/WizardProgressBarTest.php` — `test_progress_bar_fill_at_step_3_is_between_28_and_43_percent`, `test_progress_bar_label_formatting`.
      Traces: SPEC REQ-U2, screens.md S3.1, components.md custom progress bar

### Phase 1.2: Route `/projects/new` + parent Livewire `Projects\Wizard` + first render

- [x] **Task:** Criar parent Livewire component `App\Livewire\Projects\Wizard` via `php artisan make:livewire Projects/Wizard`.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (novo)
      - `kindrad-canvas/resources/views/livewire/projects/wizard.blade.php` (novo)
      Mudança: Componente com `public int $step = 1`, `public ?int $projectId = null`, `public ?int $modeId = null`, `public ?int $categoryId = null`, `public ?int $styleId = null`, `public ?int $layoutId = null`, `public ?int $sourceImageId = null`, `public array $inputs = []`. `mount()` chama `$this->authorize('view', Project::class)` e cria um `Project` com `user_id = auth()->id()`, `product_id = Product::where('slug','mug')->value('id')`, `status_id = ProjectStatus::where('slug','draft')->value('id')`, todos os FKs `NULL`, `inputs = []`. View renderiza o wizard layout (`<x-layouts.wizard>`) com o progress bar no topo e um slot centralizado com `@if($step === 1) ...` placeholder por enquanto.
      Cobre: REQ-01
      Acceptance criteria:
      - GET `/projects/new` (autenticado) cria exatamente um `projects` row por usuário (`projects` count depois = 1 para esse usuário).
      - `projects.user_id === auth()->id()`; `projects.status_id` resolve para `project_statuses.slug='draft'`; `projects.product_id` resolve para `products.slug='mug'`.
      - Após mount, `$wizard->projectId` é o id recém-criado; `$wizard->step === 1`.
      Feature tests:
      - `tests/Feature/Projects/WizardStartTest.php` — `test_clicking_new_project_creates_draft_row`, `test_draft_row_belongs_to_current_user`
      Traces: SPEC REQ-01, US-3.1, workflow 1

- [x] **Task:** Adicionar rota `GET /projects/new` com middleware `auth` e named route `projects.new`.
      Arquivos:
      - `kindrad-canvas/routes/web.php` (alterado — adicionar dentro do `Route::middleware(['auth', 'verified'])` group)
      Mudança: Linha `Route::get('projects/new', App\Livewire\Projects\Wizard::class)->name('projects.new');`. Sem POSTs — todas as mutações via Livewire actions. Substituir a rota `dashboard` existente por uma referência explícita ao CTA do dashboard (não escopo desta task).
      Cobre: REQ-10
      Acceptance criteria:
      - `php artisan route:list --name=projects.new` lista a rota com middleware `auth`.
      - Guest request a `GET /projects/new` é redirecionado para `/login`.
      - Authenticated request renderiza o componente.
      Feature tests: `tests/Feature/Projects/WizardStartTest.php` — `test_guest_is_redirected_to_login`.
      Traces: SPEC REQ-10, US-8.1

### Phase 1.3: Footer step navigation actions + Validation gate

- [x] **Task:** Implementar actions `next()`, `back()`, `goToStep(int $step)` no parent com regras de progressão.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      - `kindrad-canvas/resources/views/livewire/projects/wizard.blade.php` (alterado)
      Mudança: `next()` valida o step atual (regras condicionais via `rules()` por step) e avança `$step++` se válido; `back()` decrementa; `goToStep(int $step)` aceita 1 ≤ step ≤ 7 e ignora se fora. O footer agora wirea `wire:click="back"` no Back, `wire:click="next"` no Continue. O botão Continue fica disabled se o step atual falhar `validate()`. Back fica disabled em `step === 1`.
      Cobre: REQ-U2 (navegação), REQ-N1 (sem DB write parcial em next() inválido)
      Acceptance criteria:
      - Em step 1 sem seleção de mode, `next()` falha validação e não cria row (no-op state change).
      - Em step 1 com mode selecionado, `next()` avança para step 2.
      - Back em step 2 retorna para step 1 sem perder `$projectId`.
      - `goToStep(7)` em step 2 (sem dados) é bloqueado por authorize+validation; `goToStep(2)` em step 7 navega de volta.
      Feature tests: `tests/Feature/Projects/WizardStartTest.php` — `test_next_without_mode_does_not_advance`, `test_back_returns_to_previous_step_preserving_state`, `test_go_to_step_requires_validation_gate`.
      Traces: SPEC REQ-N1, US-3.1

### Phase 1.4: Exit wizard confirmation + dashboard CTA

- [x] **Task:** Wire do Exit button no topbar para confirmar saída via `<flux:modal>` e redirecionar para `route('dashboard')`.
      Arquivos:
      - `kindrad-canvas/resources/views/components/layout/wizard-topbar.blade.php` (alterado)
      - `kindrad-canvas/resources/views/livewire/projects/wizard.blade.php` (alterado — slot para modal)
      Mudança: Botão Exit abre um `<flux:modal>` "Exit wizard?" com copy "Your draft will be saved" + actions "Cancel" (ghost) e "Exit" (primary outlined) que redireciona via `return redirect()->route('dashboard')`. A row draft permanece no banco (não soft-delete).
      Cobre: SPEC §"Open Questions" Exit wizard confirmation (default assumido)
      Acceptance criteria:
      - Click em Exit abre modal; click em "Exit" (modal confirm) redireciona para dashboard; `projects` row continua existindo (soft-delete NÃO aplicado).
      - Cancel fecha modal sem mudar rota.
      Feature tests: `tests/Feature/Projects/WizardExitTest.php` — `test_exit_modal_preserves_draft_row`, `test_exit_cancel_keeps_user_on_wizard`.
      Traces: SPEC REQ-U1 (exit button), §"Open Questions" Exit wizard confirmation
      Design ref: `<flux:modal>` em topo do topbar; tokens `error` (icon) e `on-surface-variant` (copy)

---

## Phase 2: Step 1 — Mode selector (component + persistence + read-only flag)

Antes de implementar, leia:
1. `.spec/features/project-wizard/SPEC.md` — REQ-02, REQ-11; workflow 1 (step 1)
2. `.spec/features/project-wizard/PLAN.md` — "Architecture Decisions" (parent owns state; pivot filtering)
3. `.spec/init/design/components.md` — B.5 Wizard Tile (text variant)

### Phase 2.1: Step 1 child component + `selectMode` action

- [x] **Task:** Criar `App\Livewire\Projects\Wizard\Steps\Mode` (step 1) que renderiza tiles de `project_modes` filtrados a `slug IN ('free','mug')`.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard/Steps/Mode.php` (novo — via `php artisan make:livewire Projects/Wizard/Steps/Mode`)
      - `kindrad-canvas/resources/views/livewire/projects/wizard/steps/mode.blade.php` (novo)
      Mudança: child recebe `$projectId` e `$modeId` via mount props do parent; renderiza o componente B.5 wizard-tile (text variant) para cada mode carregado. `<livewire:projects.wizard.steps.mode :project-id="$projectId" :mode-id="$modeId" />` no slot do parent quando `$step === 1`. Click em tile chama `selectMode($modeId)`.
      Cobre: REQ-02
      Acceptance criteria:
      - Step 1 renderiza apenas os modes com slug `free` e `mug` (assert via `assertSee` em ambos os labels).
      - Modes com slug diferente (e.g., hypothetical `tshirt` mode) NÃO aparecem.
      - Cada tile mostra nome + ícone (`auto_awesome` para Free, `coffee` para Mug).
      Design ref: B.5 Wizard Tile (text variant); tokens `selection-glow`, `.glass-card`, iconografia Material Symbols
      Traces: SPEC REQ-02, US-3.1, US-6.1, workflow 1

- [x] **Task:** Implementar `selectMode(int $modeId)` no parent: valida existence + slug ∈ `('free','mug')`, persiste `projects.mode_id`, avança step.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: action `selectMode(int $modeId): void` chama `$this->authorize('update', $project)`, valida que o mode existe com slug ∈ (`free`,`mug`), atualiza `projects.mode_id`, seta `$this->modeId`, e avança `$step = 2`. Adicionar à `rules()`: `'modeId' => ['required','integer','exists:project_modes,id']`.
      Cobre: REQ-02, REQ-09
      Acceptance criteria:
      - Após `selectMode($mugId)`, `projects.mode_id` é o id do row `slug='mug'`.
      - Avanço sem seleção (`modeId === null`) é bloqueado por validação.
      - Authorization: user B chamando `selectMode` em projeto de user A recebe `AuthorizationException` (vira 403 no Livewire request).
      Feature tests: `tests/Feature/Projects/WizardModeStepTest.php` — `test_selecting_mug_persists_mode_id`, `test_selecting_free_persists_mode_id`, `test_advance_blocked_without_selection`, `test_non_owner_cannot_set_mode_via_action`.
      Traces: SPEC REQ-02, REQ-09, US-3.1, US-6.1

### Phase 2.2: Step 1 read-only flag + selection recovery on reload

- [x] **Task:** Após mount, mostrar mode selecionado highlighted + impedir nova seleção se `Project::isModeLocked()` (forward-declared helper, vacuously true para `first_generated_at !== null`).
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard/Steps/Mode.php` (alterado)
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      - `kindrad-canvas/resources/views/livewire/projects/wizard/steps/mode.blade.php` (alterado)
      Mudança: Quando `$modeId` é non-null no parent, o child Mode propaga o tile selecionado com `.active-selection .selection-glow`. O action `selectMode` valida `Project::isModeLocked()` (helper em `App\Models\Project` checa `first_generated_at !== null`); se true, no-op silencioso (no MVP draft projects nunca estão locked, então a regra protege o caso forward-declared).
      Cobre: REQ-02 (selection preserved on reload), REQ-11 (vacuously satisfied for draft projects)
      Acceptance criteria:
      - Após reload (refresh) da rota `/projects/new` com um projeto existente em draft e `mode_id` setado, o tile `mug` (ou `free`) está highlighted com `selection-glow`.
      - Tentativa de `setMode` em projeto com `first_generated_at` set (caso simulado em teste) é no-op (não muta `mode_id`).
      - Wizard que monta em projeto já com algum step preenchido pula para o `step` máximo conhecido (`max(1, $step)` derivado dos FKs não-null).
      Feature tests: `tests/Feature/Projects/WizardModeStepTest.php` — `test_selection_persists_on_reload`, `test_mode_locked_after_first_generation_blocks_rewrite`.
      Traces: SPEC REQ-02, REQ-11, US-6.1 (immutability forward declaration)

---

## Phase 3: Steps 2–4 — Category / Style / Layout pickers with pivot-driven cascading filters

Antes de implementar, leia:
1. `.spec/features/project-wizard/SPEC.md` — REQ-03, REQ-04, REQ-05; workflows 2
2. `.spec/features/project-wizard/PLAN.md` — "Architecture Decisions" (eager loading, indexed columns)
3. `.spec/init/design/components.md` — B.5 Wizard Tile (text + image variants)

### Phase 3.1: Step 2 — Category picker

- [x] **Task:** Criar `App\Livewire\Projects\Wizard\Steps\Category` (step 2) com query filtrada `mug product + active status`.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard/Steps/Category.php` (novo)
      - `kindrad-canvas/resources/views/livewire/projects/wizard/steps/category.blade.php` (novo)
      Mudança: Carrega `Category::with('product:id,slug')->whereHas('product', fn($q) => $q->where('slug','mug'))->whereHas('status', fn($q) => $q->where('slug','active'))->orderBy('sort_order')->get()` (eager-load `status`). Renderiza B.5 text variant com `description`, `thumbnail_path` (via `<img>` se set), check_circle badge na seleção. Empty state (F.1) se count == 0.
      Cobre: REQ-03, REQ-U3, REQ-U8
      Acceptance criteria:
      - Step 2 lista apenas categories: `product.slug='mug'` AND `category_statuses.slug='active'`.
      - Categories com status ≠ `active` não aparecem (assert via factory com `category_statuses.slug='inactive'`).
      - Cada tile renderiza título (`name`), descrição, e opcionalmente thumbnail.
      - Selected tile tem classe `.active-selection .selection-glow` (box-shadow `0 0 0 2px #c0c1ff`).
      - Quando 0 categories, F.1 empty state com icon `style` + copy "No categories available" + action "Contact support" (placeholder, deferred).
      Feature tests: `tests/Feature/Projects/WizardCategoryStepTest.php` — `test_category_lists_active_mug_categories`, `test_inactive_categories_excluded`, `test_selected_tile_has_selection_glow`, `test_empty_state_when_no_categories`.
      Traces: SPEC REQ-03, US-3.2, workflow 2
      Design ref: B.5 text variant, F.1 empty-state card, tokens `.selection-glow`

- [x] **Task:** Implementar `selectCategory(int $categoryId)` no parent com `authorize('update')` e validação de slug do product.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: action atualiza `projects.category_id`, seta `$categoryId`, avança `step = 3`. Reset de `styleId` e `layoutId` (cascade — selecionar categoria nova invalida styles/layouts).
      Cobre: REQ-03, REQ-09
      Acceptance criteria:
      - Select category → `projects.category_id` setado; `$step = 3`.
      - Re-selecting uma category diferente zera `styleId` e `layoutId` (assert via DB state).
      - Non-owner recebe 403.
      Feature tests: `tests/Feature/Projects/WizardCategoryStepTest.php` — `test_selecting_category_persists_and_advances`, `test_reselecting_category_resets_style_and_layout`, `test_non_owner_cannot_set_category`.
      Traces: SPEC REQ-03, REQ-09, workflow 2

### Phase 3.2: Step 3 — Style picker (filter via `category_styles` pivot)

- [x] **Task:** Criar `App\Livewire\Projects\Wizard\Steps\Style` (step 3) com query filtrada pelo pivot + status active.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard/Steps/Style.php` (novo)
      - `kindrad-canvas/resources/views/livewire/projects/wizard/steps/style.blade.php` (novo)
      Mudança: Query usa `Style::whereHas('status', fn($q) => $q->where('slug','active'))->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))->orderBy('name')->get()` (via pivot `category_styles`). Renderiza B.5 **image variant** — aspect-square, full-bleed `thumbnail_path`, hover scale-up, label row com icon + name. Empty state (F.1) com icon `style` + "No styles available for this category" + action "Browse other categories" → `goToStep(2)`.
      Cobre: REQ-04, REQ-U4, REQ-U8
      Acceptance criteria:
      - Step 3 lista apenas styles cujas relações pivot `category_styles.category_id` igualam `$categoryId` E `style_statuses.slug='active'`.
      - Styles fora do pivot para essa category NÃO aparecem.
      - Empty state renderiza F.1 + action button que chama `goToStep(2)`.
      - Tile usa `aspect-square` (CSS `aspect-ratio: 1/1`); selected tem `selection-glow`.
      Feature tests: `tests/Feature/Projects/WizardStyleStepTest.php` — `test_style_filtered_by_category_pivot`, `test_inactive_styles_excluded`, `test_empty_state_when_no_styles_for_category`.
      Traces: SPEC REQ-04, US-3.3, workflow 2
      Design ref: B.5 image variant, F.1 empty state, tokens `.selection-glow`

- [x] **Task:** Implementar `selectStyle(int $styleId)` no parent; cascade reset de `layoutId`.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: action valida que a style existe, pertence ao pivot para a chosen category (server-side check), atualiza `projects.style_id`, seta `$styleId`, reseta `$layoutId = null`, avança `step = 4`.
      Cobre: REQ-04, REQ-09
      Acceptance criteria:
      - Select style → `projects.style_id` setado; `$step = 4`; `$layoutId === null` (reset).
      - Server-side: chamar `selectStyle($styleId)` com style não pertencente ao pivot para a chosen category → `ValidationException` (422).
      Feature tests: `tests/Feature/Projects/WizardStyleStepTest.php` — `test_selecting_style_persists_and_advances`, `test_selecting_style_resets_layout`, `test_stylen_not_in_pivot_blocked`.
      Traces: SPEC REQ-04, REQ-09, workflow 2

### Phase 3.3: Step 4 — Layout picker (filter via `style_layouts` pivot)

- [x] **Task:** Criar `App\Livewire\Projects\Wizard\Steps\Layout` (step 4) com query filtrada pelo pivot.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard/Steps/Layout.php` (novo)
      - `kindrad-canvas/resources/views/livewire/projects/wizard/steps/layout.blade.php` (novo)
      Mudança: Query `Layout::whereHas('status', fn($q) => $q->where('slug','active'))->whereHas('styles', fn($q) => $q->where('styles.id', $styleId))->orderBy('name')->get()`. Renderiza B.5 image variant com `preview_path` e overlay de safe-area (subtle border dashed) derivado de `safe_area_overlay` JSON.
      Cobre: REQ-05, REQ-U4, REQ-U8
      Acceptance criteria:
      - Step 4 lista apenas layouts pivoteados para a chosen style E `layout_statuses.slug='active'`.
      - Layouts fora do pivot NÃO aparecem.
      - Empty state (F.1) com icon `dashboard` + "No layouts available" + action "Edit style" → `goToStep(3)`.
      Feature tests: `tests/Feature/Projects/WizardLayoutStepTest.php` — `test_layout_filtered_by_style_pivot`, `test_inactive_layouts_excluded`, `test_empty_state_when_no_layouts`.
      Traces: SPEC REQ-05, US-3.4, workflow 2
      Design ref: B.5 image variant, F.1 empty state

- [x] **Task:** Implementar `selectLayout(int $layoutId)` no parent.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: action atualiza `projects.layout_id`, seta `$layoutId`, avança `step = 5`.
      Cobre: REQ-05, REQ-09
      Acceptance criteria:
      - Select layout → `projects.layout_id` setado; `$step = 5`.
      - Non-owner recebe 403.
      Feature tests: `tests/Feature/Projects/WizardLayoutStepTest.php` — `test_selecting_layout_persists_and_advances`, `test_non_owner_cannot_set_layout`.
      Traces: SPEC REQ-05, REQ-09, workflow 2

### Phase 3.4: Picker performance verification (REQ-N4)

- [x] **Task:** Adicionar teste de performance que mede query count e tempo do picker.
      Arquivos:
      - `tests/Feature/Projects/WizardPerformanceTest.php` (novo)
      Mudança: Teste renderiza cada step (2/3/4) com o seed catalog (6 categories × 5 styles × 4 layouts), conta queries via `DB::enableQueryLog()`, mede tempo `microtime(true)`. Assertions: ≤ 4 queries por step; < 300 ms por render.
      Cobre: REQ-N4
      Acceptance criteria:
      - Step 2 render: ≤ 4 queries, < 300 ms.
      - Step 3 render: ≤ 4 queries, < 300 ms.
      - Step 4 render: ≤ 4 queries, < 300 ms.
      - Falha se o DBML estiver sem os índices `categories(product_id, status_id, sort_order)`, `category_styles(category_id)`, `style_layouts(style_id)`.
      Feature tests: `tests/Feature/Projects/WizardPerformanceTest.php` — `test_picker_query_count_under_threshold`, `test_picker_render_time_under_p95`.
      Traces: SPEC REQ-N4

---

## Phase 4: Step 5 — Source image upload (dropzone + S3 disk + Replace/Remove/Skip)

Antes de implementar, leia:
1. `.spec/features/project-wizard/SPEC.md` — REQ-06, REQ-N2; workflow 3
2. `.spec/features/project-wizard/PLAN.md` — "Architecture Decisions" (WithFileUploads + s3 disk)
3. `.spec/init/design/components.md` — C.6 File Upload Dropzone

### Phase 4.1: Dropzone Blade component + step 5 child

- [x] **Task:** Criar Blade component de dropzone file upload (C.6) com estados empty/preview/upload-failed.
      Arquivos:
      - `kindrad-canvas/resources/views/components/upload/dropzone.blade.php` (novo)
      Mudança: Props `wireModel`, `accept`, `maxSizeMb`, `previewUrl`. Empty state: `border-2 border-dashed border-primary/20 bg-primary/5 rounded-2xl p-stack-lg text-center` com icon `cloud_upload` (32px) + headline-md "Drag your photo here" + label-md "JPEG / PNG / WEBP up to 10 MB". Preview state: aspect-square thumbnail + Replace + Remove buttons inline. F.3 error banner se validation error existe.
      Cobre: REQ-U5, REQ-U8
      Acceptance criteria:
      - Renderiza empty state com copy exata da spec.
      - Renderiza preview state com Replace + Remove visíveis.
      - Drop zone é keyboard-accessible (`tabindex=0`, `wire:keydown.enter` abre file picker).
      Design ref: C.6 File Upload Dropzone
      Traces: SPEC REQ-U5

- [x] **Task:** Criar step 5 child `App\Livewire\Projects\Wizard\Steps\SourceImage` que consome o `WithFileUploads` trait + usa o component `upload/dropzone`.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard/Steps/SourceImage.php` (novo)
      - `kindrad-canvas/resources/views/livewire/projects/wizard/steps/source-image.blade.php` (novo)
      Mudança: `use WithFileUploads;`. Public property `$photo` (temporary file). View usa `<x-upload.dropzone>` com `preview-url` apontando para `Storage::disk('s3')->url($project->sourceImage->path)` se `$sourceImageId` set. Botões Replace e Remove chamam `replaceSourceImage()` e `removeSourceImage()` do parent (passados via event/dispatch up).
      Cobre: REQ-06, REQ-U5
      Acceptance criteria:
      - Step renderiza dropzone quando `$sourceImageId === null`.
      - Step renderiza preview + Replace/Remove quando `$sourceImageId` set.
      Design ref: C.6 + tokens `.glass-card`
      Traces: SPEC REQ-06, REQ-U5, workflow 3

### Phase 4.2: Upload action — validation, persistence, S3 storage

- [x] **Task:** Implementar `uploadSourceImage()` no parent: validar mime/size, criar `source_images` row, copiar para S3, setar `projects.source_image_id`.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: action `$this->authorize('update', $project)`; `validate(['photo' => ['required','file','mimes:jpeg,png,webp','max:10240']])`; gera UUID; constrói user-scoped key `source-images/{user_id}/{uuid}.{ext}`; `Storage::disk('s3')->putFileAs(dirname($key), $this->photo->getRealPath(), basename($key))`; cria `SourceImage` com `user_id`, `disk='s3'`, `path=$key`, `original_filename`, `mime_type`, `size_bytes`; atualiza `projects.source_image_id`; seta `$sourceImageId`. Erro de validation re-renderiza com F.3 banner.
      Cobre: REQ-06, REQ-09, REQ-N2
      Acceptance criteria:
      - Upload válido (jpeg, 2 MiB) cria `source_images` row + seta `projects.source_image_id`.
      - Arquivo > 10 MiB é rejeitado com validation error e F.3 banner; `$sourceImageId` permanece null; nenhum `source_images` row criado.
      - Arquivo `.gif` é rejeitado.
      - `Storage::disk('s3')::assertExists($expectedKey)` em teste usando `Storage::fake('s3')`.
      - Non-owner recebe 403.
      Feature tests:
      - `tests/Feature/Projects/WizardSourceImageUploadTest.php` — `test_accepts_valid_image_and_creates_source_image`, `test_rejects_oversized_file_with_validation_error`, `test_rejects_invalid_mime_type`, `test_synthetic_12mb_payload_returns_422`, `test_non_owner_cannot_upload_image`
      Traces: SPEC REQ-06, REQ-N2, US-3.5, workflow 3

### Phase 4.3: Replace + Remove + Skip actions

- [x] **Task:** Implementar `replaceSourceImage()` (re-uploads) e `removeSourceImage()` no parent.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: `replaceSourceImage()` chama `uploadSourceImage` após o usuário escolher novo arquivo (mesma lógica; SPEC FLEXIBLE: deixamos o row anterior em DB sem FK cascade). `removeSourceImage()` seta `projects.source_image_id = null` (mantém row antigo em `source_images`); chama `$this->authorize('update', $project)`. Botão Skip no rodapé do step avança sem upload (`next()`).
      Cobre: REQ-06
      Acceptance criteria:
      - Replace cria novo `source_images` row e atualiza `projects.source_image_id`; row anterior permanece.
      - Remove seta `projects.source_image_id = null` e mostra dropzone empty state.
      - Skip (click em Continue sem upload) deixa `source_image_id null` e avança para step 6.
      Feature tests: `tests/Feature/Projects/WizardSourceImageUploadTest.php` — `test_replace_creates_new_source_image_row`, `test_remove_clears_source_image_id`, `test_skip_advances_without_upload`.
      Traces: SPEC REQ-06, US-3.5, workflow 3

### Phase 4.4: Validation gate for step 5 (Skip permitted) + step transition

- [x] **Task:** Permitir `next()` em step 5 sem upload (skip é válido) e avançar para step 6.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      - `kindrad-canvas/resources/views/livewire/projects/wizard.blade.php` (alterado)
      Mudança: Em step 5, `next()` é incondicional (source image é opcional); step 5 footer mostra também botão "Skip" (ghost secondary) que usa a mesma action. O Back fica disponível para retornar ao step 4.
      Cobre: REQ-06 (skip semantics)
      Acceptance criteria:
      - Step 5 sem upload: Continue → step 6 sem erro.
      - Step 5 com upload: Continue → step 6 (idêntico).
      - Back de step 6 retorna a 5 com `$sourceImageId` preservado.
      Feature tests: `tests/Feature/Projects/WizardSourceImageUploadTest.php` — `test_next_without_upload_advances`, `test_back_from_step_6_preserves_source_image`.
      Traces: SPEC REQ-06, workflow 3

---

## Phase 5: Step 6 — User inputs (Flux form fields + JSON persistence + category validation)

Antes de implementar, leia:
1. `.spec/features/project-wizard/SPEC.md` — REQ-07, US-3.6; workflow 4
2. `.spec/features/project-wizard/PLAN.md` — §"Architecture Decisions" (Inputs JSON persistence, fixed max lengths default)
3. `.spec/init/design/components.md` — C.1 Flux inputs
4. `.spec/init/design/screens.md` — S3.2 Inputs step

### Phase 5.1: Step 6 child component + Form bindings

- [x] **Task:** Criar `App\Livewire\Projects\Wizard\Steps\Inputs` (step 6) com bindings `wire:model.live` para name/phrase/theme/dedicatoria.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard/Steps/Inputs.php` (novo)
      - `kindrad-canvas/resources/views/livewire/projects/wizard/steps/inputs.blade.php` (novo)
      Mudança: 4 `<flux:input>` fields com `wire:model.live` correspondendo a chaves em `$inputs` (parent-owned array). Cada field tem label + helper text + maxlength indicator (`{used}/{max}`) quando limite configurado. Emite evento `inputsUpdated` para o parent (Livewire dispatches) sincronizando `$inputs`.
      Cobre: REQ-07, REQ-U6
      Acceptance criteria:
      - Todos os 4 fields (`name`, `phrase`, `theme`, `dedicatoria`) renderizam com `<flux:input>`.
      - Cada field mostra contador de caracteres vs. max quando limite configurado (name 80, phrase 240, theme 120, dedicatoria 500).
      - Editar o campo atualiza `$inputs` no parent em tempo real (assert via property test).
      Design ref: C.1 Flux input, tokens `mono-sm` para contadores
      Traces: SPEC REQ-U6, US-3.6

- [x] **Task:** Implementar `updateInput(string $key, string $value)` no parent para sincronizar.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: action recebe key/value, atualiza `$inputs[$key] = $value;` (whitelist keys ∈ `['name','phrase','theme','dedicatoria']`).
      Cobre: REQ-07, REQ-N1
      Acceptance criteria:
      - Chamar `updateInput('name','Alice')` seta `$inputs['name'] = 'Alice'`.
      - Chamar `updateInput('evil_key','x')` é no-op (não aparece em `$inputs`).
      Feature tests: `tests/Feature/Projects/WizardInputsStepTest.php` — `test_update_input_persists_to_state`, `test_unknown_keys_are_ignored`.
      Traces: SPEC REQ-07, REQ-N1

### Phase 5.2: Validation rules + JSON persistence on `next()`

- [x] **Task:** Implementar `next()` em step 6: validar required + max length, persistir JSON, avançar.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: action `$this->authorize('update', $project)`; valida `rules()` retornando `'inputs.name' => 'required|string|max:80'`, `'inputs.phrase' => 'nullable|string|max:240'`, `'inputs.theme' => 'nullable|string|max:120'`, `'inputs.dedicatoria' => 'nullable|string|max:500'`; em sucesso, atualiza `projects.inputs = $inputs` (array → JSON cast no model) e avança `step = 7`.
      Cobre: REQ-07, REQ-09, REQ-N1
      Acceptance criteria:
      - Submit com `name` vazio + outros campos preenchidos → validation error em `inputs.name`; `$step` permanece em 6; `projects.inputs` não foi mutado (REQ-N1).
      - `name` com 81 chars → validation error (`max:80`).
      - Submit válido → `projects.inputs` JSON contém exatamente `{name, phrase, theme, dedicatoria}`; advance para step 7.
      Feature tests:
      - `tests/Feature/Projects/WizardInputsStepTest.php` — `test_empty_name_blocks_continue`, `test_oversized_phrase_blocks_continue`, `test_valid_inputs_persist_as_json`, `test_invalid_input_does_not_mutate_earlier_steps`, `test_non_owner_cannot_update_inputs`
      Traces: SPEC REQ-07, REQ-N1, US-3.6, workflow 4

---

## Phase 6: Step 7 — Review + summary card + Edit buttons + disabled Generate state

Antes de implementar, leia:
1. `.spec/features/project-wizard/SPEC.md` — REQ-08, workflow 5
2. `.spec/features/project-wizard/PLAN.md` — §"Architecture Decisions" (Generate is a seam stub)
3. `.spec/init/design/components.md` — H status pill patterns
4. `.spec/init/design/screens.md` — S3.2 Review

### Phase 6.1: Step 7 child component — summary card list

- [x] **Task:** Criar `App\Livewire\Projects\Wizard\Steps\Review` (step 7) que renderiza summary read-only com uma Edit button por seção.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard/Steps/Review.php` (novo)
      - `kindrad-canvas/resources/views/livewire/projects/wizard/steps/review.blade.php` (novo)
      Mudança: Section rows: Mode (mug/free), Category (nome), Style (nome), Layout (nome), Source Image (thumbnail ou "Skipped"), Inputs (4 campos com truncamento). Cada row tem botão `Edit` (ghost icon-button) que chama `goToStep($n)` no parent. B.5 empty-state show se algum FK null.
      Cobre: REQ-08 (part 1)
      Acceptance criteria:
      - 6 Edit buttons existem (mode, category, style, layout, source image, inputs).
      - Source image row mostra thumbnail se `$sourceImageId` set, senão "Skipped (no image)".
      - Inputs row mostra os 4 valores; clicáveis individualmente Edit chamam `goToStep(6)`.
      Design ref: B.1 glass-card para summary list, tokens `selection-glow`
      Traces: SPEC REQ-U7, US-3.7

### Phase 6.2: Generate button disabled + tooltip + submit() seam stub

- [x] **Task:** Adicionar Generate CTA no Review com disabled state baseado em `credit_balance == 0` e tooltip `"You're out of credits"`.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard/Steps/Review.php` (alterado)
      - `kindrad-canvas/resources/views/livewire/projects/wizard/steps/review.blade.php` (alterado)
      Mudança: `<flux:button variant="primary">Generate</flux:button>` com `:disabled="$creditBalance === 0"`; quando disabled, renderiza `:title="__('You're out of credits')"` OU `:aria-describedby="..."` apontando para um `<span>` oculto. Botão chama `wire:click="submit"`.
      Cobre: REQ-08 (Generate disabled state)
      Acceptance criteria:
      - Quando `auth()->user()->credit_balance > 0`, Generate está enabled (`<button>` sem `disabled` attr).
      - Quando `credit_balance == 0`, Generate está `<button disabled>` com atributo `title` ou `aria-describedby` contendo exatamente a string `"You're out of credits"`.
      Design ref: H status pill patterns (disabled state), tokens `error` (tooltip backdrop), `.active-glow` no hover enabled
      Traces: SPEC REQ-08, REQ-U7, US-3.7

- [x] **Task:** Implementar `submit()` no parent como seam stub — redirect para `projects.show` (que existirá em Phase 8) ou no-op marker.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: action `submit(): void` chama `$this->authorize('update', $project)`, valida `credit_balance > 0` (server-side, retorna mensagem se 0), e redireciona para `route('dashboard')` com `<flux:toast>` "Phase 7 generation not yet implemented" — pois `/projects/{id}` (Phase 8) ainda não existe. Comentário PHPDoc explica que Phase 7.3 substituirá este redirect por uma chamada `SubmitGeneration`.
      Cobre: REQ-08 (server-side gate), seams SPEC §"Contracts" REQ-C6
      Acceptance criteria:
      - Click em Generate com `credit_balance > 0` redireciona ou executa no-op stub; nenhuma row `credit_transactions` é criada pelo wizard.
      - Click forçado via action server-side com `credit_balance == 0` retorna error message (não crash).
      Feature tests: `tests/Feature/Projects/WizardReviewStepTest.php` — `test_generate_button_disabled_when_no_credits`, `test_generate_does_not_write_credit_transactions`, `test_submit_action_blocks_when_credit_balance_zero`.
      Traces: SPEC REQ-08, US-3.7, workflow 5

### Phase 6.3: Review step routing + Edit navigation

- [x] **Task:** `goToStep(int $step)` em Review navega para cada passo preservando state, mas exige validação para steps > current.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: `goToStep(int $step)` aceita 1 ≤ step ≤ 7. Navegação para step anterior é livre (preserve). Navegação para step > current exige autorização + validação dos FKs intermediários (e.g., ir a 7 sem layout setado é bloqueado com mensagem "Complete earlier steps first").
      Cobre: REQ-08 (Edit navigation)
      Acceptance criteria:
      - Em step 7 com todos FKs setados, `goToStep(2)` navega para 2 preservando `$projectId`, `$modeId`, etc.
      - Em step 4 (após `layout_id` setado), `goToStep(7)` avança para 7.
      - Em step 4 sem `layout_id`, `goToStep(7)` retorna erro.
      Feature tests: `tests/Feature/Projects/WizardReviewStepTest.php` — `test_edit_button_navigates_to_step_preserving_state`, `test_go_to_step_7_requires_layout`.
      Traces: SPEC REQ-08, US-3.7

---

## Phase 7: Policy + authorization hardening + cross-step security tests

Antes de implementar, leia:
1. `.spec/features/project-wizard/SPEC.md` — REQ-09, REQ-N3, US-8.1
2. `.spec/features/project-wizard/PLAN.md` — §"Architecture Decisions" (server-side authorization)
3. `kindrad-canvas/AGENTS.md` — boost rules (`make:policy`, `make:test --pest`)

### Phase 7.1: Re-authorization on every Livewire request (REQ-N3)

- [x] **Task:** Adicionar `authorizeOrAbort()` helper no parent chamado em `mount()` e `hydrate()` para re-validar `ProjectPolicy::view` em cada request.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: extrair helper `authorizeOrAbort(): void` que carrega o `Project` via `$this->projectId`, chama `$this->authorize('view', $project)`, e aborta com 403 se não-owner. Chamado em `mount()` e `hydrate()`.
      Cobre: REQ-09, REQ-N3
      Acceptance criteria:
      - User A autenticado que tem em sua session um `$projectId` pertencente a user B: tentar navegar para `/projects/new` (re-mount) recebe 403.
      - Admin user pode acessar projetos de qualquer user sem 403.
      Feature tests: `tests/Feature/Projects/WizardAuthorizationTest.php` — `test_reauthorize_on_mount_blocks_non_owner`, `test_admin_can_view_other_users_draft`.
      Traces: SPEC REQ-09, REQ-N3, US-8.1

### Phase 7.2: Cross-action authorization sweep (todas as actions autenticadas)

- [x] **Task:** Audit todas as actions do parent (`selectMode`, `selectCategory`, `selectStyle`, `selectLayout`, `uploadSourceImage`, `removeSourceImage`, `replaceSourceImage`, `updateInput`, `next`, `back`, `goToStep`, `submit`) garantindo que cada uma chama `authorize('update', $project)` ou `authorizeOrAbort()`.
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: Extrair helper `authorizeUpdate(): void` (chama `authorizeOrAbort()` + checa ownership). Cada action ou helper chama logo após carregamento do Project.
      Cobre: REQ-09
      Acceptance criteria:
      - Test sweep chama cada action como user B em projeto de user A — todas retornam 403.
      - Same sweep como admin (`is_admin = true`) — todas passam sem 403 (admin bypass).
      Feature tests: `tests/Feature/Projects/WizardAuthorizationTest.php` — `test_all_wizard_actions_reject_non_owners`, `test_admin_can_execute_all_wizard_actions`.
      Traces: SPEC REQ-09, US-8.1

### Phase 7.3: Soft-delete protection + first_generated_at guard

- [x] **Task:** Bloquear todas actions se `projects.deleted_at !== null` (soft-delete).
      Arquivos:
      - `kindrad-canvas/app/Livewire/Projects/Wizard.php` (alterado)
      Mudança: helper `authorizeOrAbort()` checa `if ($project->trashed()) abort(404)` (not 403 — não vaza existence).
      Cobre: REQ-09 (implied; soft-delete protection)
      Acceptance criteria:
      - Soft-deleted project: mount retorna 404; cada action retorna 404.
      Feature tests: `tests/Feature/Projects/WizardAuthorizationTest.php` — `test_soft_deleted_project_returns_404`.
      Traces: SPEC REQ-09, US-5.3

---

## Phase 8: Wizard end-to-end smoke + final hardening

Antes de implementar, leia:
1. `.spec/features/project-wizard/SPEC.md` — todas as REQs
2. `.spec/features/project-wizard/PLAN.md` — §"Validation Criteria"

### Phase 8.1: End-to-end happy path feature test

- [x] **Task:** Criar feature test que executa o wizard completo de step 1 ao step 7 em uma única sessão.
      Arquivos:
      - `tests/Feature/Projects/WizardEndToEndTest.php` (novo)
      Mudança: Test usa `actingAs($user)`, simula navegação: GET `/projects/new` → click `selectMode(mug)` → step 2 click `selectCategory(birthday)` → step 3 click `selectStyle(watercolor)` → step 4 click `selectLayout(centered)` → step 5 Skip → step 6 preenche inputs → step 7 Review mostra todos os selections.
      Cobre: US-3.1, US-3.2, US-3.3, US-3.4, US-3.5, US-3.6, US-3.7, US-6.1
      Acceptance criteria:
      - Após completar o wizard, `projects` row tem `mode_id`, `category_id`, `style_id`, `layout_id` setados; `source_image_id` null; `inputs` JSON com 4 chaves.
      - Nenhuma exceção thrown ao longo do caminho.
      Feature tests: `tests/Feature/Projects/WizardEndToEndTest.php` — `test_full_wizard_completes_without_errors`, `test_final_project_state_matches_all_selections`.
      Traces: SPEC US-3.1–3.7, US-6.1

### Phase 8.2: Pint + final test run

- [x] **Task:** Rodar `vendor/bin/pint --dirty --format agent` e `php artisan test --compact --filter=Projects`. Se passar, este feature está pronto para merge.
      Arquivos: n/a
      Mudança: Comandos de checagem final; sem mudança de código. Documentar qualquer drift de formatação em commits subsequentes.
      Cobre: Boost rules finais
      Acceptance criteria:
      - Pint retorna sem mudanças pendentes.
      - Todos os testes em `tests/Feature/Projects/` passam.
      - Nenhum erro de Larastan em arquivos modificados (rodar `vendor/bin/phpstan analyse app/Livewire/Projects app/Policies app/Models/Project.php` se configurado).
      Feature tests: n/a
      Traces: Boost rules (`pint`, `test --compact`); SPEC §"Validation Criteria"

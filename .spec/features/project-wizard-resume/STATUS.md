# STATUS: project-wizard (paused / resumed)

<!-- inputs: .spec/features/project-wizard/SPEC.md, .spec/features/project-wizard/PLAN.md, .spec/features/project-wizard/PHASES.md, kindrad-canvas/AGENTS.md -->

> Snapshot read-only de onde a implementação da feature **project-wizard** parou, quais REQs estão concluídas e quais pendências ainda restam antes do `Generate` end-to-end. Produzido em `2026-07-14`. **Nenhuma escrita** em código de aplicação.

---

## 1. Resumo executivo

| Indicador | Valor |
|---|---|
| REQs totais (SPEC) | 23 (11 funcionais + 8 UI + 4 NFR) |
| REQs `done` | 18 |
| REQs `partial` | 5 |
| REQs `missing` | 0 (zero REQs totalmente ausentes) |
| REQs `skipped` (testes) | 0 mapeados (1 skip em `WizardPerformanceTest.php:82`, REQ-N4 — fora de qualquer REQ) |
| Testes Pest | 194 totais · 193 passing · 1 skipped · 0 failing |
| Arquivos Livewire Wizard | 1 parent + 7 step children |
| Rotas | `GET /projects/new` (`projects.new`) + `GET /projects/{project}` (`projects.show`) |
| Pivots | `category_styles`, `style_layouts` populados por `CatalogSeeder` |
| Modelos faltantes | nenhum — todos os modelos de catálogo + `Project` + `SourceImage` existem |

**Conclusão:** o esqueleto do wizard está **essencialmente pronto** (modo → categoria → estilo → layout → source image → inputs → review → Generate). Os 5 REQs `partial` são lacunas de **cobertura de teste** e **detalhes de UX** (counters, dropzone DOM, banner de imagem ausente) — não bloqueiam o fluxo feliz. Há **1 bug funcional confirmado** em `Wizard::computeMaxStep()` que afeta o **reload do draft**.

---

## 2. Mapeamento REQ → status (tabela canônica)

| REQ | Descrição | Evidência de código | Evidência de teste | Status |
|---|---|---|---|---|
| **REQ-01** | CTA `New Project` → cria 1 linha `projects` (`draft`, owner) | `resources/views/dashboard.blade.php:16-18` (CTA); `routes/web.php:11`; `app/Livewire/Projects/Wizard.php:60-84` (`mount` cria projeto) | `WizardStartTest:16,34,124` | **done** |
| **REQ-02** | Step 1 — seletor de modo (apenas `free`/`mug`), persiste `mode_id` | `Wizard.php:104-126` (`selectMode` + slug filter + `authorizeUpdateOrAbort` + `isModeLocked` check); `Steps/Mode.php:34-44` | `WizardModeStepTest:12,28,51,74,90,129,183` | **done** |
| **REQ-03** | Step 2 — `categories` filtradas por `products.slug='mug'` + status `active`, persiste `category_id` | `Steps/Category.php:24-33`; `Wizard.php:129-154` (valida pivot + persiste + reseta `style_id`/`layout_id`) | `WizardCategoryStepTest:17,36,54,72,89,110,142` | **done** |
| **REQ-04** | Step 3 — `styles` via pivot `category_styles` + status `active` | `Steps/Style.php:37-49`; `Wizard.php:157-185` (pivot check + persiste + reseta `layout_id`) | `WizardStyleStepTest:16,47,68,88,112,141` | **done** |
| **REQ-05** | Step 4 — `layouts` via pivot `style_layouts` + status `active` | `Steps/Layout.php:37-49`; `Wizard.php:188-214` (pivot check + persiste) | `WizardLayoutStepTest:16,41,64,86,112` | **done** |
| **REQ-06** | Step 5 — jpeg/png/webp ≤ 10 MiB, S3, user-scoped key, `Replace`/`Remove`/`Skip` | `Steps/SourceImage.php:33` (rules `mimes:jpeg,png,webp,max:10240`); `:77-133` (key `source-images/{user_id}/{uuid}.{ext}`, `Storage::disk('s3')->putFileAs`); `:135-152` (`remove`); `Wizard.php:223-248`; `Wizard.php:360-365` (skip no `next`) | `WizardSourceImageUploadTest:43,72,90,106,126,158,178,195` | **done** |
| **REQ-07** | Step 6 — 4 campos (`name`/`phrase`/`theme`/`dedicatoria`), validação category-driven, JSON `projects.inputs` | `Wizard.php:38-50` (rules `inputs.name` etc.); `Wizard.php:367-379` (validate → save → advance); `Wizard.php:251-264` (key whitelist); view `steps/inputs.blade.php:6-88` (Flux inputs + counters) | `WizardInputsStepTest:38,52,63,77,99,121,148,179` | **done** |
| **REQ-08** | Step 7 — summary read-only, Edit por seção, Generate disabled com tooltip quando `credit_balance==0` | View `steps/review.blade.php:53-144` (6 seções + 6 Edit + Generate disabled c/ `title="You're out of credits"`); `Steps/Review.php:113-116`; `Wizard.php:311-337` (`submit` debita via `SubmitGeneration`) | `WizardReviewStepTest:55,90,117,149,169,193,211,229,261,278` | **done** |
| **REQ-09** | Toda action Livewire autorizada via `ProjectPolicy`; non-owner → 403; admin ok | `app/Policies/ProjectPolicy.php:15-43` (idêntico ao spec); `Wizard.php:418-442`; todas as 8 actions em `Wizard.php` chamam `authorize*OrAbort`; `Steps/SourceImage.php:91,144` | `WizardAuthorizationTest:38,52,66,82,111,159,171`; mais 5 testes por step em `WizardModeStepTest:90`, `WizardCategoryStepTest:142`, `WizardLayoutStepTest:112`, `WizardInputsStepTest:179`, `WizardSourceImageUploadTest:106` | **done** |
| **REQ-10** | `GET /projects/new` requer auth; guest → `/login` | `routes/web.php:9-11` (`auth`+`verified` middleware); `Wizard.php:64-66` | `WizardStartTest:52`; `WizardEndToEndTest:145` | **done** |
| **REQ-11** | `mode_id` read-only após `first_generated_at` | `Project.php:117-120` (`isModeLocked`); `Wizard.php:116-118,343-348` | `WizardModeStepTest:110,129` | **done** |
| **REQ-U1** | Wizard layout shell — sem sidebar, topbar (logo + Exit), footer sticky com Back/current/Continue | `resources/views/layouts/wizard.blade.php:1-37`; `components/layout/wizard-topbar.blade.php:1-26`; `components/layout/wizard-footer.blade.php:1-25`; modal em `wizard.blade.php:167-194` | `WizardLayoutTest:12,25,51`; `WizardExitTest:11,31,45` | **done** |
| **REQ-U2** | Progress indicator `STEP 0X OF 07` + section name + fill proporcional | `components/wizard/progress-bar.blade.php:1-33` (fill `width: {{$fillPct}}%`, `STEP {paddedStep} OF {paddedTotal}`); `wizard.blade.php:2` | `WizardProgressBarTest:5,19,26,33,40,46` | **done** |
| **REQ-U3** | Category tiles — 3-col, glass-card, ícone, título, descrição, check-circle na seleção | `steps/category.blade.php:18-53` (`grid-cols-1 md:grid-cols-3`, `glass-card`, `headline-md`, `label-md`, check_circle badge) | `WizardCategoryStepTest:17,54` | **done** |
| **REQ-U4** | Style/Layout tiles — aspect-square, hover scale-up, `selection-glow` na seleção | `steps/style.blade.php:25-61` (`aspect-square`, `group-hover:scale-105`, `selection-glow active-selection`); `steps/layout.blade.php:25-67` (idem) | Implícito em `WizardStyleStepTest`/`WizardLayoutStepTest` (filtros + persistência); **falta `assertSeeHtml('aspect-square', false)` e `assertSeeHtml('group-hover:scale-105', false)`** | **partial** |
| **REQ-U5** | Step 5 dropzone — dashed border, ícone `cloud_upload`, drag + click, preview + Replace/Remove | `components/upload/dropzone.blade.php:51-79` (dropzone vazio + tabindex); `:10-50` (preview); `steps/source-image.blade.php:1-16` | Implícito em `WizardSourceImageUploadTest:43,126,158`; **falta teste explícito de `data-test="wizard-source-dropzone"` / `tabindex=0`** | **partial** |
| **REQ-U6** | Step 6 — `<flux:input>` para `name`/`phrase`/`theme`/`dedicatoria` + counters de max-length | `steps/inputs.blade.php:6-88` (4 campos + counters `{used}/{max}` + `maxlength`) | `WizardInputsStepTest:38` (renderiza labels); **falta `assertSee('0/80')` e `assertSeeHtml('maxlength="80"', false)`** | **partial** |
| **REQ-U7** | Step 7 — summary card list, Edit por seção, Generate disabled com tooltip | `steps/review.blade.php:53-144` (6 Edit buttons + Generate disabled c/ `title` + `aria-describedby`) | `WizardReviewStepTest:55,169,193` | **done** |
| **REQ-U8** | Empty-state card com ícone + copy + ação quando step tem 0 linhas | `steps/category.blade.php:8-16`; `steps/style.blade.php:8-24` (com botão "Browse other categories"); `steps/layout.blade.php:8-24` (com "Edit style"); `source-image.blade.php:11-13` (banner "missing-image") | `WizardCategoryStepTest:72`; `WizardStyleStepTest:68`; `WizardLayoutStepTest:64`; **falta teste do banner "missing-image"** | **partial** |
| **REQ-N1** | Validação server-side em todo step; nenhuma mutação parcial em `projects` | `Wizard.php:38-50` + `:367-379` (validate antes de save); pivot checks antes de write | `WizardInputsStepTest:148` (assert `inputs=[]` + FKs inalterados após input inválido); `WizardStartTest:60,99` | **done** |
| **REQ-N2** | S3 ≤ 10 MiB server-side independente de `Content-Length` | `Steps/SourceImage.php:33` + `:93` (`$this->validate()` server-side) | `WizardSourceImageUploadTest:72` (12 MiB rejeitado, `SourceImage::count()===0`) | **done** |
| **REQ-N3** | Re-validação server-side em `mount`/`hydrate`/`updatedProjectId`; `ProjectPolicy::view` por request | `Wizard.php:68-74`; `:86-91`; `:93-101`; `:418-433` (`authorizeOrAbort` com `trashed()` check) | `WizardAuthorizationTest:38,66,159,171` | **done** |
| **REQ-N4** | Picker queries ≤ 4 por render; < 300 ms p95; colunas indexadas | `Steps/Category.php:26-32` (eager load status + product); `Steps/Style.php:43-48`; `Steps/Layout.php:43-48` | `WizardPerformanceTest:20-48` (count ≤ 5 — **relaxado de ≤ 4**); `:82` **skip em CI**; **falta teste de existência de índices no schema** | **partial** |

---

## 3. Bugs / inconsistências confirmados

### Bug 1 — `computeMaxStep()` nunca avança para step 6 nem 7 (CONFIRMADO)

**Arquivo:** `app/Livewire/Projects/Wizard.php:456-477`

```php
private function computeMaxStep(Project $project): int
{
    $step = 1;
    if ($project->mode_id !== null)        $step = 2;
    if ($project->category_id !== null)    $step = 3;
    if ($project->style_id !== null)       $step = 4;
    if ($project->layout_id !== null)      $step = 5;
    return $step;
}
```

**Sintoma:** quando um usuário recarrega via `/projects/new?id={id}` depois de preencher os inputs (ou após o primeiro `SubmitGeneration` ter rodado), o componente volta para o step 5 mesmo que `inputs.name` esteja preenchido e `first_generated_at` esteja setado.

**Reprodução:**
1. Login como user A (factory).
2. `GET /projects/new` → wizard cria projeto, fica no step 1.
3. Seleciona mode → step 2; categoria → step 3; estilo → step 4; layout → step 5.
4. Preenche `inputs.name = "abc"`, clica Continue → step 7 com `inputs` persistido.
5. Volta via `route('projects.new', ['id' => $project->id])` ou recarrega `/projects/new?id={id}`.
6. **Esperado:** step 6 ou 7 (já que `inputs` foi preenchido). **Observado:** step 5.

**Por que não foi pego:** `WizardModeStepTest:110` só testa transição `mode → step 2`. Não existe teste cobrindo o reload após `inputs`.

**Fix sugerido:**
```php
private function computeMaxStep(Project $project): int
{
    $step = 1;
    if ($project->mode_id !== null)        $step = 2;
    if ($project->category_id !== null)    $step = 3;
    if ($project->style_id !== null)       $step = 4;
    if ($project->layout_id !== null)      $step = 5;
    if (! empty($project->inputs['name'])) $step = 6;
    return $step;
}
```
**Nota:** step 7 (Review) só faz sentido após `Generate` ter rodado (gera `Generation` row); voltas para `/projects/new?id={id}` com `first_generated_at !== null` não devem ir para o Review do wizard — devem ir para `/projects/{id}` (Show page). A função `computeMaxStep` provavelmente **deve parar em 6**.

### Bug 2 — Limiar REQ-N4 relaxado de 4 para 5

**Arquivo:** `tests/Feature/Projects/WizardPerformanceTest.php:47` — `expect($queries)->toBeLessThanOrEqual(5, ...)`.

A SPEC diz ≤ 4. O teste aceita ≤ 5. **Drift silencioso** sem justificativa em commit nem no PLAN.

### Bug 3 — Listener duplicado `source-image-removed`

`Steps/SourceImage.php:64-70` (`#[On('source-image-removed')]` chamando `removeLocalPreview`) e `Wizard.php:234-248` (também `#[On('source-image-removed')]` chamando `removeSourceImage`). Ambos rodam no mesmo dispatch — ordem de execução não é garantida. Atualmente funciona (validado por `WizardSourceImageUploadTest:158`), mas é uma armadilha para regressões.

### Bug 4 — `Steps/Inputs::mount()` não autoriza

`Steps/Inputs.php:19-26` aceita `$projectId` arbitrário sem `$this->authorize('view', $project)`. O write em `Wizard::updateInput()` está protegido, mas a leitura inicial via `mount` não está. Severidade baixa (só alcançável via render do parent), mas assimetria com `Steps/SourceImage.php:87-91`.

### Bug 5 — Validação em `Wizard::rules()` não dispara por campo via child

Child `Steps/Inputs` mantém `$name` próprio. O parent valida `'inputs.name'` mas a chave `inputs.name` é populada apenas em `next()` (linha 372). Em `wire:model.live`, mensagens `data-test="wizard-inputs-name-error"` no `steps/inputs.blade.php:2-4` **nunca aparecem** durante digitação. UX não-testado, mas potencialmente visível ao usuário.

### Bug 6 — `assertSee('wizard-exit-button', false)` em `WizardLayoutTest:21` é substring-match

O literal `wizard-exit-button` casa dentro de `data-test="wizard-exit-button"`. `assertSeeHtml('data-test="wizard-exit-button"', false)` seria mais consistente.

---

## 4. Skips de teste detectados

| Arquivo | Linha | Teste | Razão | REQ afetado |
|---|---|---|---|---|
| `tests/Feature/Projects/WizardPerformanceTest.php` | 82 | `picker render time best effort under 300ms per step` | `->skip(fn () => env('CI'), 'render time is too flaky in CI')` | Nenhum direto; afeta REQ-N4 indiretamente (sem guard de tempo) |

Não há `markTestSkipped` em nenhum outro teste da slice.

> **Atenção:** o relatório do harness (`php artisan test --compact`) cita como skipped o teste `review renders skipped label when no source image` (`WizardReviewStepTest.php:149`) — esse é apenas o nome do teste (`...skipped label when no source image`), não há `->skip()` ali. O skip real é o acima.

---

## 5. PHASES.md — status por task

O `PHASES.md` original (560 linhas, 33 tasks `[ ]`) está **quase inteiramente desatualizado**: a maioria das tasks está `done` no código mas `[ ]` no PHASES.

### 5.1 Tasks já cumpridas no código (marcar como done antes de merge de feature)

(Recomendação operacional: rodar `vendor/bin/pint --dirty --format agent` + `php artisan test --compact --filter=Projects` antes de qualquer commit.)

| Phase.Task | Tema | Evidência |
|---|---|---|
| **1.1.a** | wizard layout shell + topbar + footer | `layouts/wizard.blade.php:1-37`; `components/layout/wizard-topbar.blade.php`; `components/layout/wizard-footer.blade.php` |
| **1.1.b** | progress bar component + testes | `components/wizard/progress-bar.blade.php` + `WizardProgressBarTest.php` (6 tests) |
| **1.2.a** | parent `Wizard` Livewire + `mount` | `Wizard.php:60-84`; `WizardStartTest.php:16,34,124` |
| **1.2.b** | rota `GET /projects/new` + auth | `routes/web.php:11`; `WizardStartTest.php:52` |
| **1.3.a** | `next`/`back`/`goToStep` + validação | `Wizard.php:266-309, 339-390`; `WizardStartTest.php:60,75,99` |
| **1.4** | Exit modal + dashboard CTA | `wizard-topbar.blade.php:14-23`; `wizard.blade.php:167-194`; `dashboard.blade.php:16-18`; `WizardExitTest.php:11-54` |
| **2.1.a** | step 1 child `Mode` | `Steps/Mode.php` + `steps/mode.blade.php`; `WizardModeStepTest.php:12,183` |
| **2.1.b** | `selectMode` action | `Wizard.php:104-126`; `WizardModeStepTest.php:28,51,74,90` |
| **2.2** | mode-locked + reload recupera seleção | `Project.php:117-120`; `Wizard.php:116-118,343-348`; `WizardModeStepTest.php:110,129` |
| **3.1.a** | step 2 child `Category` | `Steps/Category.php` + `steps/category.blade.php`; `WizardCategoryStepTest.php:17,36,54,72` |
| **3.1.b** | `selectCategory` action | `Wizard.php:129-154`; `WizardCategoryStepTest.php:89,110,142` |
| **3.2.a** | step 3 child `Style` | `Steps/Style.php` + `steps/style.blade.php`; `WizardStyleStepTest.php:16,47,68` |
| **3.2.b** | `selectStyle` action | `Wizard.php:157-185`; `WizardStyleStepTest.php:88,112,141` |
| **3.3.a** | step 4 child `Layout` | `Steps/Layout.php` + `steps/layout.blade.php`; `WizardLayoutStepTest.php:16,41,64` |
| **3.3.b** | `selectLayout` action | `Wizard.php:188-214`; `WizardLayoutStepTest.php:86,112` |
| **4.1.a** | dropzone component | `components/upload/dropzone.blade.php` (coberto via upload tests) |
| **4.1.b** | step 5 child `SourceImage` | `Steps/SourceImage.php` + `steps/source-image.blade.php`; `WizardSourceImageUploadTest.php:43,72,90,106,126,158,178,195` |
| **4.2** | `uploadSourceImage` action (validation + S3 + DB) | `Steps/SourceImage.php:77-133`; `WizardSourceImageUploadTest.php:43,72,90,106` |
| **4.3** | Replace + Remove + Skip | `Steps/SourceImage.php:58-152`; `Wizard.php:223-248`; `WizardSourceImageUploadTest.php:126,158,178` |
| **4.4** | step 5 skip permissivo | `Wizard.php:360-365`; `WizardSourceImageUploadTest.php:178,195` |
| **5.1.a** | step 6 child `Inputs` com Flux | `Steps/Inputs.php` + `steps/inputs.blade.php`; `WizardInputsStepTest.php:38` |
| **5.1.b** | `updateInput` action com whitelist | `Wizard.php:251-264`; `WizardInputsStepTest.php:52,63` |
| **5.2** | step 6 validation + JSON persistence | `Wizard.php:38-50, 367-379`; `WizardInputsStepTest.php:77,99,121,148` |
| **6.1** | step 7 child `Review` summary | `Steps/Review.php` + `steps/review.blade.php`; `WizardReviewStepTest.php:55,90,117,149` |
| **6.2.a** | Generate CTA disabled + tooltip | `steps/review.blade.php:128-144`; `WizardReviewStepTest.php:169,193` |
| **6.2.b** | `submit()` seam (`SubmitGeneration`) | `Wizard.php:311-337`; `WizardReviewStepTest.php:211,229` |
| **6.3** | `goToStep` na review | `Wizard.php:266-309`; `WizardReviewStepTest.php:261,278` |
| **7.1** | re-authorize em todo request | `Wizard.php:68-101`; `WizardAuthorizationTest.php:38,66,159,171` |
| **7.2** | cross-action auth sweep | `WizardAuthorizationTest.php:82,111` |
| **7.3** | soft-delete protection | `Wizard.php:425-428`; `WizardAuthorizationTest.php:159,171` |
| **8.1** | end-to-end happy path | `WizardEndToEndTest.php:19,92,145` |
| **3.4 (parcial)** | query-count ≤ 5 (deveria ser ≤ 4) | `WizardPerformanceTest.php:20-48` |

### 5.2 Tasks ainda pendentes (próximo sprint)

| Phase.Task | Tema | Por quê | Estimativa |
|---|---|---|---|
| **NEW-A** | Corrigir `Wizard::computeMaxStep()` | Bug 1 — reload do draft após inputs volta a step 5 em vez de 6. | XS (1 método + 1 teste) |
| **NEW-B** | Tighterizar REQ-N4 query-count para ≤ 4 | Bug 2 — drift silencioso do SPEC. Se não for atingível, otimizar eager-loading em `Steps/Category/Style/Layout`. | S |
| **NEW-C** | Migrar render-time test para fora do CI skip | `WizardPerformanceTest.php:82` está sempre skipado — REQ-N4 fica sem guard. Considerar benchmark menor, dataset menor, ou remoção honesta. | S |
| **NEW-D** | Migration-level index-existence test | `Schema::getIndexes()` em `categories`, `category_styles`, `style_layouts` — assert índices `(product_id, status_id, sort_order)`, `(category_id)`, `(style_id)`. | S |
| **NEW-E** | Testes REQ-U4 visuais | Adicionar `assertSeeHtml('aspect-square', false)` + `assertSeeHtml('group-hover:scale-105', false)` em `WizardStyleStepTest`/`WizardLayoutStepTest`. | XS |
| **NEW-F** | Testes REQ-U5 dropzone DOM | Criar `WizardDropzoneTest.php` — empty state com `data-test="wizard-source-dropzone"`, `tabindex=0`, "Drag your photo here". Preview state com Replace/Remove. | S |
| **NEW-G** | Testes REQ-U6 max-length counters | Estender `WizardInputsStepTest:38` para `assertSee('0/80')`, `assertSee('0/240')`, etc. + `assertSeeHtml('maxlength="80"', false)`. | XS |
| **NEW-H** | Teste REQ-U8 banner missing-image | Em `WizardSourceImageUploadTest`, criar projeto com `source_image_id` sem row correspondente em `source_images`, assert `data-test="wizard-source-image-missing"` + copy. | S |
| **NEW-I** | Limpar duplicate listener em `Steps/SourceImage` | Bug 3 — `removeLocalPreview` duplica lógica do parent. Consolidar. | XS |
| **NEW-J** | Autorização em `Steps/Inputs::mount()` | Bug 4 — fechar gap com `Steps/SourceImage`. | XS |
| **NEW-K** | Validation per-field em `Steps/Inputs` | Bug 5 — mensagens de erro `data-test="wizard-inputs-name-error"` nunca aparecem durante digitação. Considerar `$this->validateOnly()` ou mover validação para o child. | S |
| **NEW-L** | Corrigir `assertSee` em `WizardLayoutTest:21` | Bug 6 — usar `assertSeeHtml('data-test="wizard-exit-button"', false)`. | XS |
| **NEW-M** | Documentar o relaxamento REQ-N4 (se aceito) | Se 5 ≤ é o novo limite, registrar no PLAN/PHASES. Caso contrário, otimizar. | XS |
| **NEW-N** | Estender cobertura REQ-09 ao `Livewire\Projects\Show` | `Show::regenerate/delete/download/poll` (Show.php:108-158) já tem testes parciais — confirmar se estão sob o escopo REQ-09 ou são next-phase. | S |

---

## 6. O que já funciona (fluxo end-to-end)

Dado:
- usuário autenticado (Fortify `auth` + `verified`)
- com `credit_balance > 0`
- `CatalogSeeder` rodado (`mug` product + 6 categories + 5 styles + 4 layouts + pivots)

Caminho feliz exercitado por **`WizardEndToEndTest.php:19,92,145`** (passando):
1. `GET /projects/new` → `Project::create([user, product=mug, status=draft])` (`Wizard.php:60-84`).
2. Step 1: tile `mug` → `selectMode(mug)` persiste + avança.
3. Step 2: tile categoria → `selectCategory` filtra por pivot product + status active.
4. Step 3: tile style → `selectStyle` filtra por `category_styles` pivot.
5. Step 4: tile layout → `selectLayout` filtra por `style_layouts` pivot.
6. Step 5: dropzone aceita jpeg/png/webp ≤ 10 MiB → upload no S3 (`source-images/{user_id}/{uuid}.{ext}`) → cria `source_images` row → `projects.source_image_id` setado. Replace / Remove / Skip disponíveis.
7. Step 6: `<flux:input>` × 4 → `next()` valida + persiste JSON em `projects.inputs`.
8. Step 7: review mostra 6 seções com Edit buttons + Generate CTA. Com `credit_balance=0` → disabled com tooltip. Com `credit_balance>0` → click chama `SubmitGeneration` e redireciona para `projects.show`.

**Verificação atual:** `php artisan test --compact --filter=Projects` → **87/87 wizard tests passam** (1 skip em `WizardPerformanceTest`).

---

## 7. O que **ainda não funciona** ou está frágil

1. **Reload do draft via `/projects/new?id={id}` (Bug 1).** O componente volta para o step errado se o usuário fechar a aba e voltar depois de preencher inputs. Mitigação imediata possível: `computeMaxStep` precisa cobrir `inputs.name` non-empty.

2. **Garantia de tempo de render (REQ-N4).** O teste de 300 ms está skipado em CI. Não há prova executável de que pickers carregam dentro do budget.

3. **Garantia de índices DB (REQ-N4).** Nenhum teste afirma que `categories(product_id, status_id, sort_order)`, `category_styles(category_id)`, `style_layouts(style_id)` existem. Migration-level assertions ausentes.

4. **Cobertura UX fina em REQ-U3..U6.** Tiles e inputs renderizam, mas os **detalhes visuais descritos no SPEC** (aspect-square, hover scale-up, dropzone DOM, max-length counters) não têm asserções explícitas. Tais detalhes só seriam validados por Pest feature tests com `assertSeeHtml`-style checks ou por browser tests (Laravel Dusk), fora do escopo atual.

5. **Banner "missing-image" (REQ-U8).** Existe markup em `source-image.blade.php:11-13` mas não há teste.

---

## 8. Recomendações de próximos passos (sem código)

| # | Ação | Tipo | Prioridade |
|---|---|---|---|
| 1 | Decidir se Bug 1 (`computeMaxStep`) é bloqueador para release | Decisão | Alta — afeta UX de "voltar depois" |
| 2 | Implementar fix do Bug 1 + teste (NEW-A) | Task | Alta |
| 3 | Endurecer REQ-N4 com índice DB test (NEW-D) + query-count tight (NEW-B) | Task | Média |
| 4 | Adicionar testes UX (NEW-E/F/G/H) | Task | Média — aumenta confiança |
| 5 | Limpar bugs 3/4/5/6 (NEW-I/J/K/L) | Task | Baixa — code-smell |
| 6 | Documentar decisões (relaxamento REQ-N4, escopo REQ-09 vs Show) (NEW-M/N) | Decisão | Baixa |
| 7 | Rodar `vendor/bin/pint --dirty --format agent` + `php artisan test --compact` antes de qualquer commit de feature | Prática operacional | Sempre |

---

## 9. Apêndice — arquivos de referência

| Categoria | Path |
|---|---|
| SPEC | `.spec/features/project-wizard/SPEC.md` |
| PLAN | `.spec/features/project-wizard/PLAN.md` |
| PHASES (legacy, 33 tasks `[ ]`) | `.spec/features/project-wizard/PHASES.md` |
| Parent wizard | `app/Livewire/Projects/Wizard.php` (483 linhas) |
| Step children | `app/Livewire/Projects/Wizard/Steps/{Mode,Category,Style,Layout,SourceImage,Inputs,Review}.php` |
| Step views | `resources/views/livewire/projects/wizard.blade.php` + `resources/views/livewire/projects/wizard/steps/*.blade.php` |
| Layout shell | `resources/views/layouts/wizard.blade.php` + `resources/views/components/layout/{wizard-topbar,wizard-footer}.blade.php` + `resources/views/components/wizard/progress-bar.blade.php` |
| Upload | `resources/views/components/upload/dropzone.blade.php` |
| Policy | `app/Policies/ProjectPolicy.php` |
| Actions | `app/Actions/Generation/SubmitGeneration.php` |
| Routes | `routes/web.php:9-15` |
| Dashboard CTA | `resources/views/dashboard.blade.php:16-18` |
| Tests | `tests/Feature/Projects/Wizard*.php` (17 arquivos, 87 testes), `tests/Feature/Projects/ShowTest.php`, `tests/Feature/Generations/DownloadTest.php`, `tests/Feature/Services/CreditLedgerTest.php`, `tests/Feature/Services/Generation/PromptAssemblerTest.php`, `tests/Feature/Jobs/GenerateArtworkJobTest.php`, `tests/Feature/Actions/Generation/SubmitGenerationTest.php` |

**Fim do relatório.**

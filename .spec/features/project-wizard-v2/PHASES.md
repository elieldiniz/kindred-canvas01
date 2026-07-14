# Phases: project-wizard-v2 (single-page configurator)

<!-- inputs: SPEC.md@sha256:project-wizard-v2 kindrad-canvas/AGENTS.md -->

> View executável para `./ralph.sh`. Cada `## Phase N:` corresponde a uma seção da tabela `## Execution Phases` em `SPEC.md`. A heading regex é estrita: `^## Phase [0-9]+: `.

**Pré-requisitos upstream (já mergeados em `a764485`):**
- Phase 1.x Database Foundation (todas as 26 tabelas, incluindo `projects`, `source_images`, `credit_transactions`, `category_styles`, `style_layouts`, `project_statuses`, `products`, etc.). **CRÍTICO** — sem `projects` o `mount()` falha.
- Phase 1.6 Catalog Seeder (mug + 6 categories + 5 styles + 4 layouts + 120 prompt templates + `credit_transaction_reasons`). **CRÍTICO** — sem seed os cards ficam vazios.
- Phase 2.x Models (Product, Category, Style, Layout, SourceImage, Project, ProjectPolicy, ProjectMode, etc.). **CRÍTICO**.
- Phase 6.1-6.4 Wizard (7 steps já implementados e testados). **Será DELETADO** em Phase 5 deste PHASES. Mas enquanto a Phase 0-4 está em andamento, o wizard coexiste.
- Phase 7.1-7.3 Generation pipeline (`SubmitGeneration`, `GenerateArtworkJob`, `PromptAssembler`, `OpenAIProvider`, `CreditLedger::debit`/`refund`). **CRÍTICO** — Phase 4 deste PHASES estende o pipeline.

**Convenções deste arquivo:**
- `## Phase N:` — fase executável ralph.
- `### Phase N.M:` — sub-fase interna (agrupa tasks fortemente acopladas).
- Tasks marcadas `- [ ] **Task:**` com sub-bullets `**Acceptance criteria:**`, `**Feature tests:**` (lógica de negócio) ou `**Design ref:**` (frontend-only), e `**Traces:**` de volta ao SPEC.
- Após completar a fase, rodar `vendor/bin/pint --dirty --format agent` e `php artisan test --compact --filter=Projects` antes de marcar done (Boost rules).

---

## Phase 0: Schema migrations + seeder update (foundation for the configurator)

Antes de implementar, leia:
1. `.spec/features/project-wizard-v2/SPEC.md` — "Schema delta", "poses table", "project_photos table"
2. `.spec/init/database-schema.md` — `projects`, `source_images`, `products` existing structure
3. `database/migrations/2026_07_13_*` — existing migration naming convention
4. `database/seeders/CatalogSeeder.php` — existing seed structure

### Phase 0.1: Poses table migration

- [ ] **Task:** Criar tabela `poses` + lookup `pose_statuses` via `php artisan make:migration`.
      Arquivos:
      - `database/migrations/2026_07_15_000001_create_pose_statuses_table.php` (novo)
      - `database/migrations/2026_07_15_000002_create_poses_table.php` (novo)
      Mudança: `pose_statuses` com 2 rows seed (`active`, `inactive`); `poses` com colunas `id, slug (unique), name, thumbnail_path (nullable), status_id (FK), sort_order (int default 0), timestamps`. Index em `status_id`.
      Cobre: SPEC schema delta
      Acceptance criteria:
      - Ambas tabelas criadas; `down()` reverte sem erro.
      - `pose_statuses` seedada com 2 rows no construtor da migration.
      - FK `poses.status_id` → `pose_statuses.id` com `restrict` (não cascade — admin pode desativar pose sem deletar).
      Feature tests: `tests/Feature/Schema/PoseSchemaTest.php` — `test_poses_table_has_expected_columns`, `test_pose_statuses_seeded_with_active_and_inactive`.
      Traces: SPEC schema delta § `poses` table

### Phase 0.2: Project photos pivot migration

- [ ] **Task:** Criar tabela `project_photos` (pivot) via `php artisan make:migration`.
      Arquivos:
      - `database/migrations/2026_07_15_000003_create_project_photos_table.php` (novo)
      Mudança: Colunas `id, project_id (FK projects ON DELETE CASCADE), source_image_id (FK source_images ON DELETE CASCADE), position (int NOT NULL DEFAULT 0), timestamps`. `UNIQUE (project_id, source_image_id)` e `UNIQUE (project_id, position)`. Index em `source_image_id`.
      Cobre: SPEC schema delta
      Acceptance criteria:
      - Tabela criada; `down()` reverte sem erro.
      - 2 unique constraints presentes.
      - Cascade delete funciona (deletar project deleta suas project_photos; deletar source_image deleta o link).
      Feature tests: `tests/Feature/Schema/ProjectPhotoSchemaTest.php` — `test_project_photos_table_has_expected_columns`, `test_unique_constraints_present`, `test_cascade_delete_from_project`.
      Traces: SPEC schema delta § `project_photos` table

### Phase 0.3: Projects columns migration (subject_type, custom_prompt, pose_id) + drop source_image_id

- [ ] **Task:** Adicionar 3 colunas em `projects` + dropar `source_image_id` (substituído por `project_photos`).
      Arquivos:
      - `database/migrations/2026_07_15_000004_add_v2_columns_to_projects_table.php` (novo)
      Mudança: `up()` adiciona `subject_type` (enum-like string, nullable — checada em PHP), `custom_prompt` (text, nullable), `pose_id` (FK `poses` ON DELETE SET NULL, nullable). `down()` reverte. **Em `up()`, NÃO dropar `source_image_id` ainda** (a wizard antiga ainda usa; Phase 5 deste PHASES faz a remoção).
      Cobre: SPEC schema delta
      Acceptance criteria:
      - 3 colunas adicionadas com tipos corretos.
      - `pose_id` FK funciona; ON DELETE SET NULL.
      - `down()` reverte.
      Feature tests: `tests/Feature/Schema/ProjectV2ColumnsTest.php` — `test_projects_has_subject_type_and_custom_prompt_and_pose_id`, `test_pose_id_fk_set_null_on_pose_delete`.
      Traces: SPEC schema delta

### Phase 0.4: Seeder update — 8 poses + free_art product + updated prompt templates

- [ ] **Task:** Atualizar `CatalogSeeder` para seedar poses, free_art, e atualizar os 120 prompt templates com os 3 novos placeholders.
      Arquivos:
      - `database/seeders/CatalogSeeder.php` (alterado)
      Mudança: 3 adições:
      1. Inserir 1 row em `products` com `slug='free_art'`, `name='Free Art'`, `print_width_mm=210`, `print_height_mm=297` (A4 landscape-ish; admin pode ajustar). `min_dpi=300`. `color_mode_id=CMYK`.
      2. Inserir 8 rows em `poses` com slugs `abracados, beijo, sentados, caminhando, natal, praia, sofá, flores`. `thumbnail_path` aponta para `images/poses/{slug}.jpg` (placeholder — admin sobe reais em 5.2-5.8). `status_id` = `pose_statuses` active.
      3. Atualizar a query que cria os 120 `PromptTemplate` rows: o `body` agora é `A {subject_type} portrait in the {pose} pose. {name} {phrase} {dedicatoria} {custom_prompt} Style: {print_specs}.` (note: placeholders viram `{name}` em vez de `{{name}}` — esse é o formato do `PromptAssembler` após `strtr`; ou ajustar o strtr para usar `{{}}`. **Decisão: manter `{{}}`** e ajustar o body do template).
      Cobre: SPEC schema delta + seeder
      Acceptance criteria:
      - Seeder é idempotente: rodar 2x não duplica rows.
      - 8 poses existem após seed.
      - `products` tem 2 rows (mug + free_art).
      - 120 prompt templates atualizados com os placeholders (assert via `PromptTemplate::first()->body` contém `{{subject_type}}`).
      Feature tests: `tests/Feature/Seeders/CatalogSeederV2Test.php` — `test_seeds_eight_poses`, `test_seeds_free_art_product`, `test_prompt_templates_contain_new_placeholders`.
      Traces: SPEC schema delta + decisions

### Phase 0.5: Eloquent models (Pose, ProjectPhoto, Project enum SubjectType)

- [ ] **Task:** Criar model `Pose` + model `ProjectPhoto` + enum-like const no `Project` para `subject_type`.
      Arquivos:
      - `app/Models/Pose.php` (novo)
      - `app/Models/ProjectPhoto.php` (novo)
      - `app/Models/Project.php` (alterado — adicionar `subject_type` cast + relationship `photos()` + `pose()`)
      Mudança:
      - `Pose`: fillable `slug, name, thumbnail_path, status_id, sort_order`. Relationships `status()` belongsTo PoseStatus, `projects()` belongsToMany via `project_photos` (com pivot `position` ordenado).
      - `ProjectPhoto`: fillable `project_id, source_image_id, position`. Relationships `project()`, `sourceImage()`. Scope `ordered()` que retorna `orderBy('position')`.
      - `Project`: add `subject_type` to fillable. Add `photos()` HasMany ProjectPhoto (with `sourceImage` eager-loaded). Add `pose()` BelongsTo Pose.
      Cobre: SPEC schema delta
      Acceptance criteria:
      - `Pose::count()` = 8 após seed.
      - `Project::find($id)->photos` retorna collection ordenada por position.
      - `Project::find($id)->pose` retorna o Pose se setado, null caso contrário.
      Feature tests: `tests/Feature/Models/PoseTest.php` — `test_pose_has_projects_relationship`, `tests/Feature/Models/ProjectPhotoTest.php` — `test_ordered_scope`, `tests/Feature/Models/ProjectTest.php` — `test_photos_relationship`, `test_pose_relationship`.
      Traces: SPEC schema delta

---

## Phase 1: Shared Blade components (blocks/card + blocks/photo-dropzone)

Antes de implementar, leia:
1. `.spec/features/project-wizard-v2/SPEC.md` — REQ-U2, REQ-U3, REQ-U4
2. `.spec/init/design/components.md` — F.1 empty state, C.6 file upload
3. `.spec/init/design/tokens.md` — `.glass-card`, `.selection-glow`, color tokens
4. `resources/css/app.css` — design tokens already registered

### Phase 1.1: Reusable card component (visual primitive)

- [ ] **Task:** Criar Blade component `blocks/card` reutilizável por todos os 7 blocks.
      Arquivos:
      - `resources/views/components/blocks/card.blade.php` (novo)
      Mudança: Props `icon` (string Material Symbols), `title` (string), `helper` (string nullable), `default` slot (inner content). Renderiza glass-card wrapper com header (icon + title + helper) e slot. Reutiliza `.glass-card` de `app.css`. Padding `p-stack-lg`. Border `border-outline-variant`.
      Cobre: REQ-U2 (block shell)
      Acceptance criteria:
      - Renderiza com classes corretas.
      - `data-test="block-{title-slug}"` attribute no wrapper (slugified title).
      - Slot renderiza inner content.
      Feature tests: `tests/Feature/Components/BlockCardTest.php` — `test_renders_with_icon_title_and_helper`, `test_slot_content_is_rendered`.
      Traces: SPEC REQ-U2

### Phase 1.2: Reusable selection card component (Product, SubjectType, Style, Pose, Category)

- [ ] **Task:** Criar Blade component `blocks/selection-card` reutilizável para todos os selection-card-grids.
      Arquivos:
      - `resources/views/components/blocks/selection-card.blade.php` (novo)
      Mudança: Props `wireClick` (string PHP callable, renderizado como `wire:click`), `icon` (Material Symbols), `name` (string), `description` (string nullable), `thumbnail` (string nullable URL), `selected` (bool, default false), `aspect` (string `square` ou `portrait`, default `square`), `testId` (string). Renderiza botão com `.glass-card`, `aspect-square` ou `aspect-4/5`, `data-test` do `testId`. Quando `selected=true`, adiciona `.selection-glow` + check_circle badge no canto superior direito. Quando `thumbnail` setado, renderiza `<img>` full-bleed com `group-hover:scale-110`. Caso contrário, renderiza o icon em um tile `bg-surface-container-high`.
      Cobre: REQ-U3 (selection cards)
      Acceptance criteria:
      - Renderiza sem classe `selection-glow` quando `selected=false`.
      - Renderiza com classe `selection-glow` + check_circle badge quando `selected=true`.
      - `data-test` customiza o attribute de teste.
      Feature tests: `tests/Feature/Components/SelectionCardTest.php` — `test_renders_with_thumbnail_when_provided`, `test_renders_icon_fallback_when_no_thumbnail`, `test_selected_state_adds_glow_and_check_badge`.
      Traces: SPEC REQ-U3

### Phase 1.3: Multi-slot photo dropzone component

- [ ] **Task:** Criar Blade component `blocks/photo-dropzone` que renderiza 1 ou 2 slots de upload.
      Arquivos:
      - `resources/views/components/blocks/photo-dropzone.blade.php` (novo)
      Mudança: Props `wireUpload` (string callable, renderizado como `wire:model` no input file), `wireRemove` (string callable), `slot` (int 0 ou 1, qual slot este é), `slotCount` (int 1 ou 2, total de slots), `preview` (string nullable URL), `error` (string nullable, error message). Renderiza:
      - Se `preview` setado: thumbnail com Replace + Remove buttons (reusa padrão do `components/upload/dropzone.blade.php` existente).
      - Se `preview` null: dashed-border dropzone com icon `cloud_upload` + headline "Drag your photo here" + helper "JPEG / PNG / WEBP up to 10 MB".
      - Se `error` setado: error banner `bg-error-container/20 border-error/40` com icon `error` + mensagem.
      Cobre: REQ-U4
      Acceptance criteria:
      - Renderiza empty state com copy exata.
      - Renderiza preview state com Replace + Remove.
      - Renderiza error banner quando error setado.
      Feature tests: `tests/Feature/Components/PhotoDropzoneTest.php` — `test_renders_empty_state`, `test_renders_preview_state`, `test_renders_error_banner`.
      Traces: SPEC REQ-U4

### Phase 1.4: Preview row component (used by the preview panel)

- [ ] **Task:** Criar Blade component `blocks/preview-row` usado pelo Preview child.
      Arquivos:
      - `resources/views/components/blocks/preview-row.blade.php` (novo)
      Mudança: Props `icon` (string Material Symbols), `label` (string), `value` (string nullable). Renderiza row com icon (24px) + label (label-md uppercase tracking-widest on-surface-variant) + value (label-md on-surface; `—` em on-surface-variant se value null). Padding `py-stack-sm`.
      Cobre: REQ-U5
      Acceptance criteria:
      - Renderiza o label e value corretamente.
      - Renderiza `—` em on-surface-variant quando value null.
      Feature tests: `tests/Feature/Components/PreviewRowTest.php` — `test_renders_label_and_value`, `test_renders_dash_when_value_null`.
      Traces: SPEC REQ-U5

---

## Phase 2: Configurator Livewire parent + 7 block children (the configurator UI)

Antes de implementar, leia:
1. `.spec/features/project-wizard-v2/SPEC.md` — REQ-01, REQ-02, REQ-03, REQ-05, REQ-06, REQ-07
2. `app/Livewire/Projects/Wizard.php` — existing parent pattern (rehydrate from `projectId`)
3. `app/Livewire/Projects/Wizard/Steps/*.php` — existing child dispatch pattern
4. `kindrad-canvas/AGENTS.md` — Boost rules

### Phase 2.1: Configurator parent Livewire component

- [ ] **Task:** Criar parent Livewire `App\Livewire\Projects\Configurator` via `php artisan make:livewire Projects/Configurator --class`.
      Arquivos:
      - `app/Livewire/Projects/Configurator.php` (novo)
      - `resources/views/livewire/projects/configurator.blade.php` (novo)
      Mudança: Componente com `public ?int $projectId = null`, `public ?string $productSlug = null` (Mug ou Free Art), `public ?string $subjectType = null`, `public ?int $categoryId = null`, `public ?int $styleId = null`, `public ?int $poseId = null`, `public string $customPrompt = ''`. `mount(?int $id = null)` chama `authorizeOrAbort` (mesma lógica do Wizard) e rehidrata from `Project`. View renderiza single-page layout com 7 blocks + preview panel. Auth via `ProjectPolicy` em todo write action.
      Cobre: REQ-01, REQ-10, REQ-13
      Acceptance criteria:
      - GET `/projects/new` cria `projects` row com `subject_type=null, custom_prompt='', pose_id=null` (todos nullable), `status_id=draft`, `product_id=Product::where('slug','mug')->value('id')` (default to mug; configurator lets user switch to free_art).
      - Rehidrata from `?id=N`: `mount(N)` lê Project e seta todas as properties.
      - Cada write action chama `$this->authorize('update', $project)`.
      - Soft-deleted project → 404 (mesma regra).
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_mount_creates_draft_project`, `test_mount_rehydrates_existing_draft`, `test_guest_is_redirected_to_login`, `test_non_owner_gets_403_on_every_action`.
      Traces: SPEC REQ-01, REQ-10, REQ-13

### Phase 2.2: Block-Product child (2 cards: Mug, Free Art)

- [ ] **Task:** Criar `App\Livewire\Projects\Configurator\BlockProduct` com 2 selection cards.
      Arquivos:
      - `app/Livewire/Projects/Configurator/BlockProduct.php` (novo)
      - `resources/views/livewire/projects/configurator/block-product.blade.php` (novo)
      Mudança: Component recebe `$productSlug` via mount. Renderiza 2 `selection-card`s: "Mug" (icon `coffee`, thumbnail placeholder) e "Free Art" (icon `image`, thumbnail placeholder). Click em card chama `selectProduct(string $slug)` que valida que slug ∈ {`mug`, `free_art`}, atualiza `Project.product_id`, atualiza `$productSlug`, e seta `subjectType` no parent (via dispatch event). Autoriza via `authorize('update', $project)`.
      Cobre: REQ-02
      Acceptance criteria:
      - Renderiza 2 cards: Mug, Free Art.
      - Click no card persiste `product_id` e atualiza preview.
      - Click no mesmo card não causa duplicate update.
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_selecting_mug_persists_product_id`, `test_selecting_free_art_persists_product_id`, `test_invalid_product_slug_rejected`.
      Traces: SPEC REQ-02

### Phase 2.3: Block-SubjectType child (5 cards + reveal logic)

- [ ] **Task:** Criar `App\Livewire\Projects\Configurator\BlockSubjectType` com 5 cards.
      Arquivos:
      - `app/Livewire/Projects\Configurator/BlockSubjectType.php` (novo)
      - `resources/views/livewire/projects/configurator/block-subject-type.blade.php` (novo)
      Mudança: Component recebe `$subjectType` via mount. Renderiza 5 `selection-card`s: Pessoa (icon `person`), Casal (icon `people`), Família (icon `family_restroom`), Pet (icon `pets`), Outra (icon `category`). Click chama `selectSubjectType(string $type)` que valida ∈ {`pessoa`, `casal`, `familia`, `pet`, `outra`}, atualiza `Project.subject_type`. Computa `needsPose = in_array($type, ['casal', 'familia'])` e `slotCount = needsPose ? 2 : 1` — passa para o Block-Photos via property reativa.
      Cobre: REQ-03
      Acceptance criteria:
      - Renderiza 5 cards.
      - Click persiste `subject_type`.
      - `needsPose` muda para true/false baseado na escolha (afeta visibilidade do Block-Pose).
      - `slotCount` muda para 1/2 baseado na escolha (afeta Block-Photos).
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_selecting_pessoa_hides_pose_block`, `test_selecting_casal_reveals_pose_block_and_two_photo_slots`, `test_selecting_pet_hides_pose_block_and_shows_one_photo_slot`.
      Traces: SPEC REQ-03

### Phase 2.4: Block-Photos child (multi-slot dropzone, writes to project_photos)

- [ ] **Task:** Criar `App\Livewire\Projects\Configurator\BlockPhotos` com 1-2 dropzones.
      Arquivos:
      - `app/Livewire/Projects\Configurator/BlockPhotos.php` (novo)
      - `resources/views/livewire/projects/configurator/block-photos.blade.php` (novo)
      Mudança: Component usa `WithFileUploads`. Properties: `public ?int $projectId`, `public string $subjectType`, `public array $photoPositions = [0, 1]` (sempre 2 slots; o segundo é hidden se slotCount=1). Mount: lê `project_photos` ordered by `position` e popula o estado. Methods: `upload(int $slot)` valida mime+size, cria `SourceImage`, insere `ProjectPhoto` com `position=$slot`. `remove(int $slot)` deleta o `ProjectPhoto` (cascade deleta SourceImage). `replace(int $slot)` chama `remove` e `upload`. Estado: `$previews = [0 => null|string, 1 => null|string]` (URLs signed do S3 ou null).
      Cobre: REQ-04
      Acceptance criteria:
      - 1 slot visível para Pessoa/Pet/Outra; 2 slots para Casal/Família.
      - Upload válido cria `SourceImage` + `ProjectPhoto` (position 0 ou 1).
      - Upload > 10MB rejeitado com error.
      - Remove deleta o `ProjectPhoto` (cascade).
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_uploading_photo_creates_source_image_and_project_photo`, `test_uploading_oversized_photo_rejected`, `test_remove_clears_project_photo`, `test_subject_type_casal_requires_both_slots_filled`.
      Traces: SPEC REQ-04

### Phase 2.5: Block-Category child (refactored from existing Steps\Category)

- [ ] **Task:** Criar `App\Livewire\Projects\Configurator\BlockCategory` com 6 cards.
      Arquivos:
      - `app/Livewire/Projects/Configurator/BlockCategory.php` (novo)
      - `resources/views/livewire/projects/configurator/block-category.blade.php` (novo)
      Mudança: Reusa 90% do `app/Livewire/Projects/Wizard/Steps/Category.php` existente. Renderiza `Category::with(['status:id,slug', 'product:id,slug'])->where('product_id', $productId)->whereHas('status', 'slug=active')->orderBy('sort_order')->get()`. Click chama `selectCategory(int $id)`. Autoriza via policy.
      Cobre: REQ-N3 (4-tuple), decision: keep Category
      Acceptance criteria:
      - Renderiza 6 categories (mug) ou apenas as do product selecionado.
      - Click persiste `category_id`.
      - Inactive categories excluídas.
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_selecting_category_persists_category_id`, `test_inactive_categories_excluded`, `test_filtered_by_selected_product`.
      Traces: SPEC REQ-N3

### Phase 2.6: Block-Style child (refactored from existing Steps\Style)

- [ ] **Task:** Criar `App\Livewire\Projects\Configurator\BlockStyle` com 8 cards filtrados pelo pivot.
      Arquivos:
      - `app/Livewire/Projects/Configurator/BlockStyle.php` (novo)
      - `resources/views/livewire/projects/configurator/block-style.blade.php` (novo)
      Mudança: Renderiza `Style::whereHas('status', 'slug=active')->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))->orderBy('name')->get()`. Click chama `selectStyle(int $id)`. Autoriza via policy.
      Cobre: REQ-06, REQ-N3
      Acceptance criteria:
      - Renderiza styles filtrados pelo category pivot.
      - Click persiste `style_id`.
      - Inactive styles excluídos.
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_selecting_style_persists_style_id`, `test_inactive_styles_excluded`, `test_filtered_by_category_pivot`.
      Traces: SPEC REQ-06, REQ-N3

### Phase 2.7: Block-Pose child (8 cards, conditional on subject_type)

- [ ] **Task:** Criar `App\Livewire\Projects\Configurator\BlockPose` com 8 cards.
      Arquivos:
      - `app/Livewire/Projects/Configurator/BlockPose.php` (novo)
      - `resources/views/livewire/projects/configurator/block-pose.blade.php` (novo)
      Mudança: Property `$poseId` (nullable int). Component tem `public bool $visible` (computed from parent subjectType). Mount: lê `Pose::whereHas('status', 'slug=active')->orderBy('sort_order')->get()`. Click chama `selectPose(int $id)`. Se `!$visible`, retorna early sem renderizar (parent decide visibilidade via `@if`).
      Cobre: REQ-05
      Acceptance criteria:
      - 8 poses renderizadas quando `visible=true`.
      - Nada renderizado quando `visible=false` (parent wrapper has `@if`).
      - Click persiste `pose_id`.
      - Click no mesmo pose não causa duplicate update.
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_pose_block_hidden_when_subject_type_pessoa`, `test_pose_block_visible_when_subject_type_casal`, `test_selecting_pose_persists_pose_id`.
      Traces: SPEC REQ-05

### Phase 2.8: Block-Prompt child (textarea, max 500)

- [ ] **Task:** Criar `App\Livewire\Projects\Configurator\BlockPrompt` com textarea.
      Arquivos:
      - `app/Livewire/Projects/Configurator/BlockPrompt.php` (novo)
      - `resources/views/livewire/projects/configurator/block-prompt.blade.php` (novo)
      Mudança: Property `string $customPrompt = ''` (max:500). Renderiza `<flux:textarea wire:model.live.debounce.500ms="customPrompt" maxlength="500">` com counter `{strlen}/500` e helper "Optional. Describe anything we should add — colors, mood, objects, scene.". Method `updated()` persiste a `Project.custom_prompt`. Autoriza via policy.
      Cobre: REQ-07
      Acceptance criteria:
      - Textarea renderiza com `maxlength=500`.
      - Counter atualiza em tempo real.
      - Debounce de 500ms evita request a cada keystroke.
      - `Project.custom_prompt` persiste no DB.
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_typing_custom_prompt_persists_to_state`, `test_custom_prompt_max_length_validation`.
      Traces: SPEC REQ-07

---

## Phase 3: Preview panel + sticky Generate footer

Antes de implementar, leia:
1. `.spec/features/project-wizard-v2/SPEC.md` — REQ-08, REQ-09, REQ-U5
2. `resources/views/components/blocks/preview-row.blade.php` (Phase 1.4)
3. `app/Livewire/Projects/Wizard.php` (current `submit()` method) — reuse logic

### Phase 3.1: Preview child component (sticky aside, mirrors all selections)

- [ ] **Task:** Criar `App\Livewire\Projects\Configurator\Preview` que renderiza o painel lateral.
      Arquivos:
      - `app/Livewire/Projects/Configurator/Preview.php` (novo)
      - `resources/views/livewire/projects/configurator/preview.blade.php` (novo)
      Mudança: Component é renderless (sem state próprio). Recebe `$projectId` via mount. Renderiza `<aside class="sticky top-margin-page">` (desktop) ou `<details open>` (mobile, controlado por atributo no parent). Renderiza 7 `preview-row`s: Product (Product::find($productId)?->name), Subject type (strtoupper), Photos (count + N "uploaded" ou "Pending"), Category, Style, Pose (se setado), Prompt (truncated to 80 chars + "..." se > 80). Helper text "Live preview" no topo com `auto_awesome` icon. Glass-card wrapper.
      Cobre: REQ-08, REQ-U5
      Acceptance criteria:
      - Renderiza todos os 7 rows com label e value correto.
      - Renderiza `—` para fields não setados.
      - Sticky positioning funciona (desktop).
      - Mobile accordion expand/collapse funciona.
      - Atualiza em tempo real quando o user faz selections (mesmo request Livewire).
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_preview_renders_current_selections`, `test_preview_shows_dash_for_unset_fields`, `test_preview_shows_pose_only_when_subject_type_casal_or_familia`.
      Traces: SPEC REQ-08, REQ-U5

### Phase 3.2: Sticky footer with Generate button

- [ ] **Task:** Adicionar sticky footer no parent view `configurator.blade.php` com o botão Generate.
      Arquivos:
      - `resources/views/livewire/projects/configurator.blade.php` (alterado)
      Mudança: Footer fixo no bottom (`<footer class="sticky bottom-0 border-t border-outline-variant bg-surface-container/95 backdrop-blur-md p-stack-md">`). Dentro: validation message ("Complete all required blocks to enable Generate") + button Generate (`<flux:button variant="primary" wire:click="generate" :disabled="!canGenerate()" size="lg">Generate</flux:button>`). `canGenerate()` é um computed property: `projectId && productId && subjectType && categoryId && styleId && (subjectType in ['casal', 'familia'] ? count(project_photos) == 2 : count(project_photos) >= 1) && creditBalance > 0`. Tooltip `You're out of credits` quando disabled por falta de credits.
      Cobre: REQ-09, REQ-U7
      Acceptance criteria:
      - Button disabled quando qualquer required field está faltando.
      - Button disabled quando `credit_balance == 0`, com tooltip correto.
      - Button enabled quando tudo preenchido.
      - Click chama `generate()` que delega para `SubmitGeneration::execute()`.
      Feature tests: `tests/Feature/Projects/ConfiguratorTest.php` — `test_generate_button_disabled_when_credit_balance_zero`, `test_generate_button_disabled_when_required_fields_missing`, `test_generate_button_enabled_when_all_required_fields_filled`, `test_generate_calls_submit_generation`.
      Traces: SPEC REQ-09, REQ-U7

### Phase 3.3: Generate action on parent (delegates to SubmitGeneration)

- [ ] **Task:** Implementar `generate()` no parent `Configurator`.
      Arquivos:
      - `app/Livewire/Projects/Configurator.php` (alterado)
      Mudança: `public function generate(): RedirectResponse|Redirector`. Valida `canGenerate()` (server-side, não confia no client). Authoriza `update`. Chama `app(SubmitGeneration::class)->execute($user, $project)`. Redireciona para `route('projects.show', $project)`.
      Cobre: REQ-09
      Acceptance criteria:
      - Re-checks `canGenerate()` server-side.
      - Re-autoriza policy.
      - Chama `SubmitGeneration::execute()` (consistente com wizard antiga).
      - Redireciona para `projects.show`.
      Feature tests: covered by `test_generate_calls_submit_generation`.
      Traces: SPEC REQ-09

---

## Phase 4: Generation pipeline updates (PromptAssembler + SubmitGeneration + GenerateArtworkJob)

Antes de implementar, leia:
1. `.spec/features/project-wizard-v2/SPEC.md` — "Generation pipeline impact (Phase 7)"
2. `app/Services/PromptAssembler.php` — current placeholder substitution logic
3. `app/Actions/Generation/SubmitGeneration.php` — current generation action
4. `app/Jobs/GenerateArtworkJob.php` — current job with provider dispatch
5. `app/Contracts/GenerationProvider.php` — current provider contract

### Phase 4.1: PromptAssembler — 3 new placeholders

- [ ] **Task:** Adicionar placeholders `{{subject_type}}`, `{{pose}}`, `{{custom_prompt}}` ao `PromptAssembler::assemble()`.
      Arquivos:
      - `app/Services/PromptAssembler.php` (alterado)
      Mudança: No array `$replacements`, adicionar:
      ```php
      '{{subject_type}}' => (string) ($project->subject_type ?? ''),
      '{{pose}}' => $project->pose?->name ?? '',
      '{{custom_prompt}}' => (string) ($project->custom_prompt ?? ''),
      ```
      No array `$constraints`, adicionar:
      ```php
      'subject_type' => $project->subject_type,
      'pose' => $project->pose?->name,
      'custom_prompt' => $project->custom_prompt,
      ```
      Cobre: SPEC generation pipeline
      Acceptance criteria:
      - `assemble($project)` substitui os 3 novos placeholders.
      - `constraints` array inclui os 3 novos campos.
      Feature tests: `tests/Feature/Services/PromptAssemblerV2Test.php` — `test_prompt_includes_subject_type_placeholder`, `test_prompt_includes_pose_placeholder`, `test_prompt_includes_custom_prompt_placeholder`, `test_constraints_include_v2_fields`.
      Traces: SPEC generation pipeline

### Phase 4.2: GenerationProvider contract — accept array<SourceImage>

- [ ] **Task:** Atualizar `App\Contracts\GenerationProvider` para aceitar `?array<SourceImage>` em vez de `?SourceImage`.
      Arquivos:
      - `app/Contracts/GenerationProvider.php` (alterado)
      - `app/Services/Generation/OpenAIProvider.php` (alterado)
      - `app/Services/Generation/GeminiProvider.php` (alterado, se existir)
      - `app/Services/Generation/ReplicateProvider.php` (alterado, se existir)
      Mudança: Contract `generate(string $prompt, array $constraints, ?array $sourceImages = null)`. OpenAIProvider: usa `$sourceImages[0]` como o source image primário (compat com API `/v1/images/edits` que aceita apenas 1 imagem). GeminiProvider/ReplicateProvider: passa o array completo (multi-image input suportado).
      Cobre: SPEC generation pipeline
      Acceptance criteria:
      - `OpenAIProvider::generate()` funciona com `$sourceImages[0]`.
      - GeminiProvider e ReplicateProvider passam array completo.
      Feature tests: `tests/Feature/Services/Generation/OpenAIProviderV2Test.php` — `test_generate_uses_first_source_image_as_primary`.
      Traces: SPEC generation pipeline

### Phase 4.3: SubmitGeneration — project_photos validation + array<SourceImage>

- [ ] **Task:** Atualizar `App\Actions\Generation\SubmitGeneration` para validar `project_photos` e passar o array.
      Arquivos:
      - `app/Actions/Generation/SubmitGeneration.php` (alterado)
      Mudança: Adicionar validação no `execute()`:
      1. `$project->load('photos.sourceImage')` (eager load).
      2. Se `in_array($project->subject_type, ['casal', 'familia'])`, `$photos->count() !== 2` → throw exception.
      3. Se `!in_array($project->subject_type, ['casal', 'familia'])`, `$photos->count() < 1` → throw exception.
      4. Passa `$photos->pluck('sourceImage')->all()` (array) ao `PromptAssembler::assemble()` e `GenerationProvider::generate()`.
      5. `Generation` row stores o count de photos em `Generation.metadata` JSON (new column or existing? **Decision: reuse `constraints_snapshot` JSON**, add `photo_count` and `photo_paths`).
      Cobre: SPEC generation pipeline
      Acceptance criteria:
      - Validation rejeita se photos count != esperado.
      - `Generation.constraints_snapshot` inclui `photo_count` e `photo_paths`.
      - `PromptAssembler` recebe o array de SourceImage.
      Feature tests: `tests/Feature/Actions/Generation/SubmitGenerationV2Test.php` — `test_refuses_when_casal_has_only_one_photo`, `test_refuses_when_pessoa_has_no_photo`, `test_passes_photos_to_prompt_assembler`, `test_constraints_snapshot_includes_photo_paths`.
      Traces: SPEC generation pipeline

### Phase 4.4: GenerateArtworkJob — eager-load photos + pass to provider

- [ ] **Task:** Atualizar `App\Jobs\GenerateArtworkJob` para carregar `project.photos.sourceImage` e passar ao provider.
      Arquivos:
      - `app/Jobs/GenerateArtworkJob.php` (alterado)
      Mudança: Adicionar `->with('project.photos.sourceImage')` no `Generation::find()`. Passa `$generation->project->photos->pluck('sourceImage')->all()` ao `$provider->generate()`.
      Cobre: SPEC generation pipeline
      Acceptance criteria:
      - Job carrega photos eager-loaded (no N+1).
      - Provider recebe array<SourceImage> ordenado por `position`.
      Feature tests: `tests/Feature/Jobs/GenerateArtworkJobV2Test.php` — `test_job_loads_photos_eagerly`, `test_job_passes_photos_to_provider_in_order`.
      Traces: SPEC generation pipeline

---

## Phase 5: Configurator feature tests + remove old wizard

Antes de implementar, leia:
1. `.spec/features/project-wizard-v2/SPEC.md` — "Test plan", "Acceptance criteria"
2. `tests/Feature/Projects/Wizard*Test.php` — existing test patterns (will be deleted)
3. `kindrad-canvas/AGENTS.md` — Boost rules

### Phase 5.1: ConfiguratorTest — all 25 acceptance tests

- [ ] **Task:** Criar `tests/Feature/Projects/ConfiguratorTest.php` com todos os 25 testes do SPEC test plan.
      Arquivos:
      - `tests/Feature/Projects/ConfiguratorTest.php` (novo)
      Mudança: Pest feature tests cobrindo cada acceptance criterion. Use `actingAs`, `Livewire::test`, `get`, factory states. Test patterns:
      - `ConfiguratorTest::test_guest_is_redirected_to_login` — REQ-11
      - `ConfiguratorTest::test_authenticated_user_creates_draft_on_mount` — REQ-01
      - `ConfiguratorTest::test_mount_rehydrates_existing_draft` — REQ-13
      - `ConfiguratorTest::test_selecting_mug_persists_product_id` — REQ-02
      - `ConfiguratorTest::test_selecting_free_art_persists_product_id` — REQ-02
      - `ConfiguratorTest::test_selecting_pessoa_hides_pose_block` — REQ-03
      - `ConfiguratorTest::test_selecting_casal_reveals_pose_block_and_two_photo_slots` — REQ-03
      - `ConfiguratorTest::test_uploading_photo_creates_source_image_and_project_photo` — REQ-04
      - `ConfiguratorTest::test_uploading_oversized_photo_rejected_with_validation_error` — REQ-N1
      - `ConfiguratorTest::test_uploading_gif_rejected_with_mime_validation` — REQ-N1
      - `ConfiguratorTest::test_remove_clears_project_photo` — REQ-04
      - `ConfiguratorTest::test_selecting_pose_persists_pose_id` — REQ-05
      - `ConfiguratorTest::test_selecting_style_persists_style_id` — REQ-06
      - `ConfiguratorTest::test_selecting_category_persists_category_id` — REQ-N3
      - `ConfiguratorTest::test_typing_custom_prompt_persists_to_state` — REQ-07
      - `ConfiguratorTest::test_preview_renders_current_selections` — REQ-08
      - `ConfiguratorTest::test_preview_shows_dash_for_unset_fields` — REQ-08
      - `ConfiguratorTest::test_generate_button_disabled_when_credit_balance_zero` — REQ-09
      - `ConfiguratorTest::test_generate_button_disabled_when_required_fields_missing` — REQ-09
      - `ConfiguratorTest::test_generate_button_enabled_when_all_required_fields_filled` — REQ-09
      - `ConfiguratorTest::test_generate_calls_submit_generation` — REQ-09
      - `ConfiguratorTest::test_non_owner_gets_403_on_every_write_action` — REQ-10
      - `ConfiguratorTest::test_admin_can_execute_all_configurator_actions` — REQ-10
      - `ConfiguratorTest::test_blocks_become_readonly_after_first_generated_at` — REQ-12
      - `ConfiguratorTest::test_custom_prompt_remains_editable_after_first_generation` — REQ-12
      - `ConfiguratorTest::test_full_configurator_flow_end_to_end` — integration
      Cobre: SPEC acceptance criteria (todas)
      Acceptance criteria:
      - 25+ testes passando.
      - Cada acceptance criterion do SPEC tem pelo menos 1 teste.
      Feature tests: este arquivo.
      Traces: SPEC acceptance criteria

### Phase 5.2: Remove old Wizard + 7 step children + their views + 17 wizard tests

- [ ] **Task:** Deletar o wizard antigo (Phase 6) já que o configurator o substitui.
      Arquivos:
      - `app/Livewire/Projects/Wizard.php` (delete)
      - `app/Livewire/Projects/Wizard/` (delete diretório inteiro: Steps/, etc.)
      - `resources/views/livewire/projects/wizard.blade.php` (delete)
      - `resources/views/livewire/projects/wizard/` (delete diretório inteiro: steps/, etc.)
      - `resources/views/layouts/wizard.blade.php` (delete — não mais usado)
      - `resources/views/components/layout/wizard-topbar.blade.php` (delete)
      - `resources/views/components/layout/wizard-footer.blade.php` (delete)
      - `resources/views/components/wizard/` (delete diretório inteiro)
      - `tests/Feature/Projects/Wizard*Test.php` (delete todos os 17 testes do wizard)
      Mudança: `git rm -r` para cada path. Após delete, rodar `grep -r "Wizard\\|wizard" app/ tests/ 2>&1` para garantir nenhum import órfão.
      Cobre: FLEX-5 (replace old wizard)
      Acceptance criteria:
      - Wizard files completamente removidos.
      - Nenhum import órfão.
      - Suite completa passa sem esses testes.
      Feature tests: n/a (deletion task)
      Traces: SPEC FLEX-5

### Phase 5.3: Update project-phases.md, HANDOFF.md, run pint + full suite

- [ ] **Task:** Marcar Phase 6 (Wizard) como superseded em `project-phases.md`; atualizar HANDOFF.md.
      Arquivos:
      - `.spec/init/project-phases.md` (alterado — Phase 6 marcada como superseded by Phase 6v2)
      - `HANDOFF.md` (alterado — adicionar status do v2)
      Mudança: Em project-phases.md, adicionar nota "Phase 6 (Wizard) superseded by project-wizard-v2 (2026-07-15)" e referenciar `.spec/features/project-wizard-v2/`. Em HANDOFF.md, adicionar seção "Phase 6v2: Single-Page Configurator (2026-07-15) — done" e atualizar stats.
      Cobre: documentation
      Acceptance criteria:
      - Phase 6 marcada como superseded.
      - HANDOFF.md reflete v2.
      - `vendor/bin/pint --dirty --format agent` passa.
      - `php artisan test --compact` passa (target: 220+ tests, 0 failures).
      Feature tests: n/a (documentation + final verification)
      Traces: n/a

---

## Risk Register

| Risk | Mitigation |
|---|---|
| Phase 0 schema migration may fail on existing dev DBs with old data | Migration uses `down()` to revert; new columns are nullable; `source_image_id` is NOT dropped until Phase 5 (coexistence with old wizard) |
| Phase 5 deletion of wizard may break other code that imports `App\Livewire\Projects\Wizard` | Run `grep -r "App\\\\Livewire\\\\Projects\\\\Wizard" app/ tests/` after deletion; fix any orphans |
| New `project_photos` pivot may not cascade properly | Add explicit cascade test in `tests/Feature/Schema/ProjectPhotoSchemaTest.php` |
| `PromptAssembler` placeholder changes may break existing prompt templates (if any admin customized them) | New placeholders default to empty string when not set; existing templates without new placeholders still render correctly |
| Multi-photo generation may be slower than single-photo (more bytes to encode) | Out of MVP scope; only first photo is sent to OpenAI; Gemini/Replicate can use full array |
| Sticky footer on mobile may cover content | Add bottom padding to main content equal to footer height (64px + 16px) |

## Out of scope for this PHASES.md

- Phase 5 admin CRUD for `poses` (deferred to Phase 5.2-5.8 of `project-phases.md`).
- Phase 7.4 Reverb broadcasting (separate PHASES).
- Phase 7.5 live status updates (separate PHASES).
- Mockup generation (Phase 9).
- Real payments (Phase 9).
- Multi-photo drag-to-reorder (Phase 9+).
- Internationalization (en only for MVP).

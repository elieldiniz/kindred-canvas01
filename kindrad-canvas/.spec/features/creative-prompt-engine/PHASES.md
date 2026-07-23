# Phases: creative-prompt-engine

Gerado por /plan a partir de PLAN.md — view executável para `./ralph.sh .spec/features/creative-prompt-engine/PHASES.md`.

## Phase 1: Foundation de schema e domínio

Antes de implementar, leia:
1. `.spec/features/creative-prompt-engine/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/creative-prompt-engine/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T01 — Migrations: add enriched columns to catalog tables
      Arquivos: `database/migrations/2026_07_23_000001_add_enriched_columns_to_categories_table.php`, `database/migrations/2026_07_23_000002_add_prompt_fragment_to_layouts_table.php`, `database/migrations/2026_07_23_000003_add_negative_fragment_to_styles_table.php`, `database/migrations/2026_07_23_000004_add_product_prompt_rules_to_products_table.php`, `database/migrations/2026_07_23_000005_add_rich_description_to_poses_table.php`
      Mudança: adicionar `scene_prompt`, `emotion_hint`, `lighting_hint`, `color_palette` (text, nullable) em `categories`; `prompt_fragment` (text, nullable) em `layouts`; `negative_fragment` (text, nullable) em `styles`; `product_prompt_rules` (json, nullable) em `products`; `rich_description` (text, nullable) em `poses`. Todas reversíveis (down dropa colunas adicionadas).
      Cobre: RF-05, RF-06, RF-09, RF-11, RNF-02
      Acceptance criteria: `php artisan migrate` cria todas as colunas; `php artisan migrate:rollback` remove todas; schema verificável via `Schema::hasColumn()`.
      Testes: `php artisan migrate && php artisan migrate:rollback` — verificar criação e remoção das colunas.

- [ ] T02 — Migrations: scene_presets table + scene_preset_id FK on projects
      Arquivos: `database/migrations/2026_07_23_000006_create_scene_presets_table.php`, `database/migrations/2026_07_23_000007_add_scene_preset_id_to_projects_table.php`
      Mudança: criar tabela `scene_presets` com `id`, `category_id` (FK constrained), `name`, `slug`, `prompt_fragment` (text), `sort_order` (int, default 0), `is_default` (boolean, default false), `timestamps`; unique `(category_id, slug)`. Adicionar `scene_preset_id` nullable FK em `projects` com `SET NULL` on delete; index em `category_id`.
      Cobre: RF-13, RNF-02
      Acceptance criteria: tabela `scene_presets` existe com colunas corretas; FK `scene_preset_id` em `projects` aceita null e valida FK; rollback remove tabela e coluna.
      Testes: `php artisan migrate && php artisan migrate:rollback` — verificar criação e remoção da tabela e FK.

## Phase 2: PromptEngine core

Antes de implementar, leia:
1. `.spec/features/creative-prompt-engine/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/creative-prompt-engine/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T03 — PromptFragment value object + PromptModule interface + PromptEngine orchestrator
      Arquivos: `app/Services/PromptEngine/PromptFragment.php`, `app/Services/PromptEngine/PromptModule.php`, `app/Services/PromptEngine/PromptEngine.php`
      Mudança: criar `PromptFragment` como value object readonly com `public readonly string $text`, `public readonly int $priority`, `public readonly ?string $negativeFragment = null`. Criar interface `PromptModule` com `public function fragment(Project $project): ?PromptFragment`. Criar `PromptEngine` com construtor aceitando `iterable<PromptModule>` (via container tagging). Método `assemble(Project $project): array{prompt: string, constraints: array}` coleta fragmentos não-nulos, ordena por prioridade decrescente, concatena texto, mergeia negative fragments em bloco inline "Avoid:", retorna prompt + constraints (dimensões em pixels computadas do product).
      Cobre: RF-01, RF-02, RF-08, RF-11, CT-01, CT-02, CT-03
      Acceptance criteria: `PromptFragment` é construível com `new PromptFragment(text: '...', priority: 10, negativeFragment: 'no text')`; `negativeFragment` é nullable; `PromptEngine::assemble()` retorna array com chave `prompt` (string não-vazia) e `constraints` (array com width/height); fragmentos são ordenados por prioridade decrescente.
      Testes: `tests/Unit/Services/PromptEngine/PromptFragmentTest.php`, `tests/Unit/Services/PromptEngine/PromptEngineTest.php` — testar construção, prioridade, ordenação, concatenação.

## Phase 3: Módulos do PromptEngine

Antes de implementar, leia:
1. `.spec/features/creative-prompt-engine/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/creative-prompt-engine/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T04 — IdentityModule
      Arquivos: `app/Services/PromptEngine/Modules/IdentityModule.php`
      Mudança: implementar `PromptModule`. Extrair `Project.inputs['name']` (default `'the subject'`). Traduzir `Project.subject_type` pt→en: pessoa→person, casal→couple, familia→family, pet→pet, outra→subject. Retornar `PromptFragment(text: "...", priority: 90)`.
      Cobre: RF-03, RF-03a, RF-03b
      Acceptance criteria: para projeto com `inputs.name = 'Alice'` e `subject_type = 'casal'`, fragmento contém `'Alice'` e `'couple'`; para `inputs.name` vazio, contém `'the subject'`.
      Testes: `tests/Unit/Services/PromptEngine/Modules/IdentityModuleTest.php` — testar com nome presente, vazio, e cada subject_type.

- [ ] T05 — PoseModule
      Arquivos: `app/Services/PromptEngine/Modules/PoseModule.php`
      Mudança: implementar `PromptModule`. Ler `Project.slug`, traduzir para descrição rica em inglês (8 poses: abracados→embracing couple with warm body language, beijo→kissing couple in romantic embrace, sentados→sitting side by side in relaxed pose, caminhando→walking together hand in hand, natal→festive Christmas holiday scene, praia→beach scene with ocean waves, sofa→cozy living room sofa setting, flores→surrounded by colorful flowers). Prioridade: 85. Retornar null quando `pose_id` é null.
      Cobre: RF-04
      Acceptance criteria: para pose slug `'abracados'`, fragmento contém `'embracing'`; para `'beijo'`, contém `'kissing'`; as 8 poses são cobertas.
      Testes: `tests/Unit/Services/PromptEngine/Modules/PoseModuleTest.php` — testar cada uma das 8 poses e null quando sem pose.

- [ ] T06 — SceneModule
      Arquivos: `app/Services/PromptEngine/Modules/SceneModule.php`
      Mudança: implementar `PromptModule`. Verificar `Project.scene_preset_id` primeiro — quando setado, usar `ScenePreset.prompt_fragment`. Quando null, usar `Category.scene_prompt`. Retornar null quando ambos são empty/null. Prioridade: 70.
      Cobre: RF-05, RF-05a, RF-13, RF-13a, RF-13b
      Acceptance criteria: com `scene_preset_id` apontando para preset 'balloon_party', fragmento contém o `prompt_fragment` do preset; com `scene_preset_id = null`, usa `Category.scene_prompt`; com ambos null, retorna null.
      Testes: `tests/Unit/Services/PromptEngine/Modules/SceneModuleTest.php` — testar com preset, sem preset (fallback), e ambos null.

- [ ] T07 — EmotionModule
      Arquivos: `app/Services/PromptEngine/Modules/EmotionModule.php`
      Mudança: implementar `PromptModule`. Ler `Category.emotion_hint`. Retornar `PromptFragment(text: $hint, priority: 70)` ou null quando null/empty.
      Cobre: RF-05, RF-05b
      Acceptance criteria: com `emotion_hint = 'warm and joyful'`, fragmento contém `'warm and joyful'`; com `emotion_hint = null`, retorna null.
      Testes: `tests/Unit/Services/PromptEngine/Modules/EmotionModuleTest.php` — testar com valor e null.

- [ ] T08 — LightingModule
      Arquivos: `app/Services/PromptEngine/Modules/LightingModule.php`
      Mudança: implementar `PromptModule`. Ler `Category.lighting_hint`. Retornar `PromptFragment(text: $hint, priority: 70)` ou null quando null/empty.
      Cobre: RF-05
      Acceptance criteria: com `lighting_hint = 'golden hour sunlight'`, fragmento contém `'golden hour sunlight'`; com null, retorna null.
      Testes: `tests/Unit/Services/PromptEngine/Modules/LightingModuleTest.php` — testar com valor e null.

- [ ] T09 — ColorPaletteModule
      Arquivos: `app/Services/PromptEngine/Modules/ColorPaletteModule.php`
      Mudança: implementar `PromptModule`. Ler `Category.color_palette`. Retornar `PromptFragment(text: $palette, priority: 65)` ou null quando null/empty.
      Cobre: RF-05
      Acceptance criteria: com `color_palette = 'pastel pinks and creams'`, fragmento contém `'pastel pinks and creams'`; com null, retorna null.
      Testes: `tests/Unit/Services/PromptEngine/Modules/ColorPaletteModuleTest.php` — testar com valor e null.

- [ ] T10 — StyleModule
      Arquivos: `app/Services/PromptEngine/Modules/StyleModule.php`
      Mudança: implementar `PromptModule`. Ler `Style.prompt_fragment` (coluna existente). Retornar `PromptFragment(text: $fragment, priority: 75)`. Incluir `Style.negative_fragment` (nova coluna) como `negativeFragment` do fragmento quando non-empty.
      Cobre: RF-05
      Acceptance criteria: com style watercolor, fragmento contém `'watercolor'` e `negativeFragment` contém o negative_fragment do estilo (se definido).
      Testes: `tests/Unit/Services/PromptEngine/Modules/StyleModuleTest.php` — testar com prompt_fragment e negative_fragment.

- [ ] T11 — LayoutModule
      Arquivos: `app/Services/PromptEngine/Modules/LayoutModule.php`
      Mudança: implementar `PromptModule`. Ler `Layout.prompt_fragment` (nova coluna de T01). Retornar `PromptFragment(text: $fragment, priority: 70)` ou null quando null/empty. Substitui o `renderLayoutInstructions()` hardcoded do PromptAssembler.
      Cobre: RF-06
      Acceptance criteria: para layout slug `'centered'` com `prompt_fragment = 'Main subject MUST be centered...'`, fragmento contém o valor do banco; o método `renderLayoutInstructions()` não é mais chamado para montagem de prompt.
      Testes: `tests/Unit/Services/PromptEngine/Modules/LayoutModuleTest.php` — testar cada layout com prompt_fragment e null.

- [ ] T12 — ProductModule
      Arquivos: `app/Services/PromptEngine/Modules/ProductModule.php`
      Mudança: implementar `PromptModule`. Ler `Product.product_prompt_rules` (nova coluna JSON de T01). Decodificar array JSON, concatenar regras em fragmento de texto. Prioridade: 80. Retornar null quando coluna é null/empty.
      Cobre: RF-09
      Acceptance criteria: com product_rules `["rule1", "rule2"]`, fragmento contém `'rule1'` e `'rule2'`; com null, retorna null.
      Testes: `tests/Unit/Services/PromptEngine/Modules/ProductModuleTest.php` — testar com regras JSON e null.

- [ ] T13 — PrintSpecsModule
      Arquivos: `app/Services/PromptEngine/Modules/PrintSpecsModule.php`
      Mudança: implementar `PromptModule`. Replicar lógica `renderPrintSpecs()` do PromptAssembler: quando `Project.mode.slug === 'mug'`, computar `widthPx = round(print_width_mm * min_dpi / 25.4)`, `heightPx = round(print_height_mm * min_dpi / 25.4)`, retornar fragmento com layout horizontal, aspect ratio, resolução, detalhes de sublimação. Prioridade: 80. Retornar null para modos não-mug.
      Cobre: RF-09, RF-09a, RF-09b
      Acceptance criteria: para mug com `print_width_mm = 220`, `min_dpi = 300`, fragmento contém `'2165'` (largura em px) e `'1122'` (altura em px); para modo `'free'`, retorna null.
      Testes: `tests/Unit/Services/PromptEngine/Modules/PrintSpecsModuleTest.php` — testar mug e free.

- [ ] T14 — UserOverrideModule
      Arquivos: `app/Services/PromptEngine/Modules/UserOverrideModule.php`
      Mudança: implementar `PromptModule`. Ler `Project.custom_prompt`. Quando non-empty, retornar `PromptFragment(text: $custom, priority: 100)`. Quando null/empty, retornar null.
      Cobre: RF-07, RF-07a, RF-07b
      Acceptance criteria: com `custom_prompt = 'Add a rainbow'`, fragmento contém `'Add a rainbow'` e prioridade é 100; com `custom_prompt = null`, retorna null.
      Testes: `tests/Unit/Services/PromptEngine/Modules/UserOverrideModuleTest.php` — testar com valor e null.

- [ ] T15 — NegativePromptModule
      Arquivos: `app/Services/PromptEngine/Modules/NegativePromptModule.php`
      Mudança: implementar `PromptModule`. Emitir bloco base de restrição negativa: "Avoid: blurry, distorted faces, extra limbs, low quality, watermark, text overlay". Prioridade: 60. Retornar `PromptFragment` com este como `text` e null como `negativeFragment` (este módulo é o produtor do bloco negativo).
      Cobre: RF-11
      Acceptance criteria: fragmento contém `'Avoid:'` (ou prefixo equivalente de restrição).
      Testes: `tests/Unit/Services/PromptEngine/Modules/NegativePromptModuleTest.php` — testar que texto contém 'Avoid:'.

- [ ] T16 — CompositionModule
      Arquivos: `app/Services/PromptEngine/Modules/CompositionModule.php`
      Mudança: implementar `PromptModule`. Emitir instruções de composição baseadas no layout: ler `Layout.slug` e fornecer direção de composição (centered → subject centered with clean background; border_wrap → full width seamless edges; full_bleed → repeating pattern; split_top_bottom → dual composition with empty center). Prioridade: 70. Este módulo complementa LayoutModule com direção específica de composição.
      Cobre: RF-10
      Acceptance criteria: o prompt montado contém instruções de composição de pelo menos 4 categorias distintas de módulos (identity + style + scene/lighting + layout ou composition).
      Testes: `tests/Unit/Services/PromptEngine/Modules/CompositionModuleTest.php` — testar cada layout slug.

## Phase 4: Scene Presets

Antes de implementar, leia:
1. `.spec/features/creative-prompt-engine/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/creative-prompt-engine/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T18 — ScenePreset model
      Arquivos: `app/Models/ScenePreset.php`
      Mudança: criar model Eloquent com `$fillable = ['category_id', 'name', 'slug', 'prompt_fragment', 'sort_order', 'is_default']`. Cast `is_default` boolean, `sort_order` integer. `belongsTo(Category::class)`. Scope `forCategory(int $categoryId)`.
      Cobre: RF-13, CT-04
      Acceptance criteria: `ScenePreset` é criável com `ScenePreset::create([...])`; relação `category()` retorna instância de `Category`.
      Testes: `tests/Unit/Models/ScenePresetTest.php` — testar criação, casts, relação.

- [ ] T19 — Add scene_preset_id to Project model
      Arquivos: `app/Models/Project.php`
      Mudança: adicionar `'scene_preset_id'` a `$fillable`. Adicionar relação `BelongsTo` `scenePreset()` retornando `ScenePreset`.
      Cobre: RF-13
      Acceptance criteria: `$project->scenePreset` retorna instância de `ScenePreset` ou null; `scene_preset_id` está em fillable.
      Testes: `tests/Unit/Models/ProjectTest.php` — testar relação scenePreset.

- [ ] T20 — Seed scene_presets (4-5 por category, 24-30 registros)
      Arquivos: `database/seeders/ScenePresetSeeder.php`
      Mudança: criar seeder dedicado que cria 4-5 presets de cena por category, com exatamente 1 preset marcado `is_default = true` por category. Criar: birthday (5): Balloon Party (default), Cake & Candles, Confetti Blast, Garden Party, Surprise Moment; wedding (5): Altar Garden (default), Ballroom Elegant, Beach Sunset, Vintage Chapel, Garden Reception; pets (4): Dog Park (default), Cat Nap, Aquarium, Beach Play; family (5): Living Room (default), Backyard BBQ, Park Picnic, Holiday Dinner, Beach Day; couples (5): Rooftop Sunset (default), Coffee Shop, Starry Night, Beach Walk, Mountain View; kids (5): Playground (default), Candy World, Space Adventure, Birthday Party, Superhero Scene. Cada preset deve ter slug único dentro da category e `prompt_fragment` descritivo com no mínimo 20 caracteres. Usar `firstOrCreate` para idempotência (RNF-03).
      Cobre: RF-13, RF-13c, RF-16, RF-16a, RF-16b, RF-16c, RNF-03
      Acceptance criteria: tabela `scene_presets` tem 4-5 linhas por category (24-30 total); exatamente 1 preset por category tem `is_default = true`; cada `(category_id, slug)` é único; cada `prompt_fragment` é não-vazio, descritivo e tem no mínimo 20 caracteres; seeder é idempotente (executar 2x não duplica).
      Testes: `tests/Feature/Seeders/ScenePresetSeederTest.php` — verificar contagem por category, defaults, slugs únicos, tamanho dos prompt fragments e idempotência.

- [ ] T21 — BlockScene Livewire component
      Arquivos: `app/Livewire/Projects/Configurator/BlockScene.php`, `resources/views/livewire/projects/configurator/block-scene.blade.php`
      Mudança: criar novo componente Block seguindo padrão dos `BlockCategory`, `BlockStyle`, etc. BlockScene MUST filtrar por `categoryId`, exibindo somente presets onde `category_id === project.category_id`, e MUST NOT renderizar quando `categoryId` for null. Exibir cards de presets de cena para a category selecionada; cada card mostra nome + thumbnail (ou placeholder). Clique no card dispara evento `scene-selected` com `scenePresetId`. Configurator escuta via `#[On('scene-selected')]` e persiste `scene_preset_id` no Project. BlockScene MUST escutar o evento `category-selected`, resetar `scene_preset_id` para null e auto-selecionar o preset com `is_default = true`, se existir. Quando a category não tiver presets, mostrar o empty state "No scenes available for this category".
      Cobre: RF-13, RF-13d, RF-14, RF-14a, RF-14b, RF-14c, RF-14d, RF-15, RF-15a, RF-15b
      Acceptance criteria: componente renderiza somente cards da category selecionada; não renderiza quando `categoryId` é null; cada card tem `data-test="scene-preset-card"`; clique dispara `scene-selected`; `scene_preset_id` é persistido no projeto; `category-selected` reseta `scene_preset_id` para null e auto-seleciona o preset default; category sem presets mostra "No scenes available for this category".
      Testes: `tests/Feature/Livewire/BlockSceneTest.php` — testar filtragem, ocultação sem category, reset e auto-seleção no evento, empty state, dispatch e persistência.

## Phase 5: Seeders enriquecidos

Antes de implementar, leia:
1. `.spec/features/creative-prompt-engine/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/creative-prompt-engine/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T22 — Update CatalogSeeder: poses com rich descriptions em inglês
      Arquivos: `database/seeders/CatalogSeeder.php`
      Mudança: estender array `$poses` com campo `rich_description` para as 8 poses (descrições contextuais em inglês conforme T05). Semear via `updateOrInsert` no slug para atualizar registros existentes idempotentemente.
      Cobre: RF-04, RNF-03
      Acceptance criteria: após seeding, cada pose tem `rich_description` não-nulo e não-vazio; seeder é idempotente.
      Testes: `tests/Feature/Seeders/CatalogSeederEnrichedTest.php` — verificar rich_description para cada pose.

- [ ] T23 — Update CatalogSeeder: categorias com scene/emotion/lighting/color
      Arquivos: `database/seeders/CatalogSeeder.php`
      Mudança: estender seed de categorias para popular `scene_prompt`, `emotion_hint`, `lighting_hint`, `color_palette` para as 6 categorias (birthday, wedding, pets, family, couples, kids). Usar `updateOrInsert` para idempotência.
      Cobre: RF-05, RNF-03
      Acceptance criteria: cada category tem os 4 campos preenchidos e não-vazios; seeder é idempotente.
      Testes: `tests/Feature/Seeders/CatalogSeederEnrichedTest.php` — verificar campos enriquecidos.

- [ ] T24 — Update CatalogSeeder: layouts com prompt_fragment
      Arquivos: `database/seeders/CatalogSeeder.php`
      Mudança: estender array `$layouts` com campo `prompt_fragment` para os 4 layouts (centered, border_wrap, full_bleed, split_top_bottom). Usar `updateOrInsert` para idempotência.
      Cobre: RF-06, RNF-03
      Acceptance criteria: cada layout tem `prompt_fragment` não-nulo e não-vazio; seeder é idempotente.
      Testes: `tests/Feature/Seeders/CatalogSeederEnrichedTest.php` — verificar prompt_fragment para cada layout.

- [ ] T25 — Update CatalogSeeder: estilos com negative_fragment
      Arquivos: `database/seeders/CatalogSeeder.php`
      Mudança: estender array `$styles` com campo `negative_fragment` para os 5 estilos (watercolor, cartoon, realistic, pixel_art, minimalist_line). Usar `updateOrInsert` para idempotência.
      Cobre: RF-11, RNF-03
      Acceptance criteria: cada style tem `negative_fragment` não-nulo; seeder é idempotente.
      Testes: `tests/Feature/Seeders/CatalogSeederEnrichedTest.php` — verificar negative_fragment para cada style.

- [ ] T26 — Update CatalogSeeder: produtos com product_prompt_rules
      Arquivos: `database/seeders/CatalogSeeder.php`
      Mudança: estender array `$products` com campo `product_prompt_rules` JSON. Exemplo para mug: `["Horizontal wrap-around design", "Seamless left-right connection"]`. Exemplo para free_art: `["Standard portrait orientation", "Full canvas coverage"]`. Usar `updateOrInsert` para idempotência.
      Cobre: RF-09, RNF-03
      Acceptance criteria: cada product tem `product_prompt_rules` não-nulo (JSON decodificável); seeder é idempotente.
      Testes: `tests/Feature/Seeders/CatalogSeederEnrichedTest.php` — verificar product_prompt_rules para cada product.

## Phase 6: Integração e depreciação

Antes de implementar, leia:
1. `.spec/features/creative-prompt-engine/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/creative-prompt-engine/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T17 — Deprecar PromptAssembler, criar wrapper backward-compatible
      Arquivos: `app/Services/PromptAssembler.php`
      Mudança: adicionar anotação `@deprecated` na classe. Reescrever `assemble()` para delegar para `PromptEngine::assemble()` — injetar `PromptEngine` no construtor, proxy da chamada. Mantém todos os testes existentes (RF-12) passando enquanto a migração para PromptEngine acontece nos callers.
      Cobre: RF-12, RF-12a, RF-12b
      Acceptance criteria: `php artisan test --compact --filter=PromptAssemblerTest` passa com 0 falhas; classe `PromptAssembler` tem anotação `@deprecated`.
      Testes: `tests/Feature/Services/PromptAssemblerTest.php` — testes existentes continuam passando.

- [ ] T27 — Wire PromptEngine into SubmitGeneration, deprecate PromptAssembler usage
      Arquivos: `app/Actions/Generation/SubmitGeneration.php`, `app/Providers/AppServiceProvider.php`, Configurator Livewire component
      Mudança: em `SubmitGeneration::__construct()`, substituir dependência `PromptAssembler` por `PromptEngine`. Atualizar `$this->assembler->assemble($project)` para `$this->engine->assemble($project)`. Registrar `PromptEngine` no `AppServiceProvider` (ou `PromptEngineServiceProvider` dedicado): bind das 13 classes de módulo, tag `'prompt.modules'`, bind de `PromptEngine` resolvendo módulos tagged. Na integração do Configurator, `selectCategory()` MUST resetar `scene_preset_id` para null antes de salvar e MUST auto-selecionar o preset da category com `is_default = true` após o reset; `selectStyle()` MUST NOT tocar em `scene_preset_id`.
      Cobre: RF-01, RF-12, RF-14c, RF-15, RF-15a, RF-15c
      Acceptance criteria: `SubmitGeneration` usa `PromptEngine` como dependência; `selectCategory()` limpa e então seleciona o preset default; `selectStyle()` preserva `scene_preset_id`; `php artisan test --compact --filter=SubmitGenerationTest` passa; PromptAssembler não é mais instanciado por nenhum caller ativo.
      Testes: `tests/Feature/Actions/SubmitGenerationTest.php` — testes existentes passam; novo teste verifica que PromptEngine é chamado. Testes Livewire do Configurator verificam reset/default em category e preservação em style.

## Phase 7: Testes e verificação

Antes de implementar, leia:
1. `.spec/features/creative-prompt-engine/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/creative-prompt-engine/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T28 — Full test suite + regression + RNF-01 benchmark
      Arquivos: `tests/` (todos os testes)
      Mudança: executar `php artisan test --compact` e verificar 0 falhas. Verificar RF-12a (`PromptAssemblerTest` passa), RF-12b (`CatalogSeederV2Test` passa se existir). Rodar benchmark de RNF-01: `PromptEngine::assemble()` completa em <50ms para projeto com todos os 13 módulos (via Pest timing assertion). Verificar que `PromptAssembler` não é mais instanciado por callers ativos (grep por `new PromptAssembler` retornando 0 resultados fora do próprio wrapper).
      Cobre: RF-01, RF-12, RNF-01
      Acceptance criteria: `php artisan test --compact` retorna 0 falhas; benchmark RNF-01 passa; grep por `new PromptAssembler` fora de `PromptAssembler.php` retorna 0.
      Testes: `php artisan test --compact` — suite completa; `php artisan test --compact --filter=PromptAssemblerTest` — regressão; timing assertion no teste do PromptEngine.

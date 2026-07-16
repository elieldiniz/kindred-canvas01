# Phases: public-gallery-and-remix

Gerado por /plan a partir de PLAN.md — view executável para `./ralph.sh .spec/features/public-gallery-and-remix/PHASES.md`.

## Phase 1: Foundation de schema e domínio

Antes de implementar, leia:
1. `.spec/features/public-gallery-and-remix/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/public-gallery-and-remix/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T01 — Foundation de schema e domínio
      Arquivos: `database/migrations/*add_gallery_columns_to_projects_table.php`, `database/migrations/*create_gallery_favorites_table.php`, `app/Models/Project.php`, `app/Models/User.php`, `app/Models/GalleryFavorite.php`
      Mudança: adicionar flags de galeria e FK nullable auto-referenciada em `projects`; criar `gallery_favorites` com FKs e unique `(user_id, project_id)`; atualizar fillable/casts/relações e adicionar `User::isFreeTier()` baseado em `Subscription::isOpen()`.
      Cobre: RF-01, RF-02, RF-03, RF-04, RF-05, RF-06, RNF-03
      Acceptance criteria: o schema contém `is_published=false`, `is_in_explore=true`, `remixed_from_project_id` nullable e unique `(user_id, project_id)`; `Project` expõe casts/relações; `User::isFreeTier()` retorna false para subscription aberta e true caso contrário.
      Testes: `tests/Feature/Gallery/ExploreTest.php` e `tests/Feature/Gallery/AdminGalleryTest.php` — validar schema, defaults, classificação free-tier e constraint unique.

## Phase 2: Feed Explore, card e ações sociais

Antes de implementar, leia:
1. `.spec/features/public-gallery-and-remix/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/public-gallery-and-remix/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T02 — Feed Explore, card e ações sociais
      Arquivos: `routes/web.php`, `app/Livewire/Gallery/Explore.php`, `resources/views/livewire/gallery/explore.blade.php`, `app/Models/GalleryFavorite.php`, `app/Models/Project.php`
      Mudança: registrar `/explore` com `auth` sem `verified`; criar feed paginado eager-loaded de projetos free, publicados, opt-in e com latest generation concluída; renderizar imagem via disk configurado, autor, contagem, Favorite e Remix; implementar `toggleFavorite()` idempotente e `remix()` com gate de créditos/subscription, clone dos campos RIGID, FK de origem e redirect `projects.new`.
      Cobre: RF-01, RF-04, RF-05, RF-06, RNF-01, RNF-02, RNF-04, UI-01
      Acceptance criteria: guest recebe 401; somente os projetos elegíveis aparecem; cada card tem os dois `data-test` exigidos, URL de storage e contagem correta; favoritos alternam sem duplicar; remix bloqueado mostra `Insufficient credits` sem criar e remix permitido cria apenas os campos definidos, mantém draft e redireciona.
      Testes: `tests/Feature/Gallery/ExploreTest.php` — AC1.1–AC1.4, AC1.2, AC4.1–AC4.3, AC5.1–AC5.4, UI-01 e RNF-02.

## Phase 3: Aviso do autor e galeria administrativa

Antes de implementar, leia:
1. `.spec/features/public-gallery-and-remix/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/public-gallery-and-remix/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T03 — Aviso do autor e galeria administrativa
      Arquivos: `routes/web.php`, `app/Livewire/Projects/Show.php`, `resources/views/livewire/projects/show.blade.php`, `app/Livewire/Admin/Gallery/Index.php`, `resources/views/livewire/admin/gallery/index.blade.php`
      Mudança: adicionar aviso free-only, dismissível por projeto via session, com checkbox de opt-out persistindo `is_in_explore=false`; criar `/admin/gallery` no grupo admin com paginação de gerações completadas, filtro publicado/não publicado/todos, thumbnail, email, source e `flux:switch` persistente para `is_published`, reutilizando `components.layouts.admin`.
      Cobre: RF-02, RF-03, RF-06, RNF-02, UI-02
      Acceptance criteria: o aviso mostra o literal exigido apenas ao owner free na primeira visita, desaparece após dismissal e opt-out remove do Explore; admin vê todas as gerações completadas e pode filtrar/toggle; não-admin recebe 403; projeto ocultado continua visível no Show; cada linha possui `data-test="admin-gallery-row"` e switch.
      Testes: `tests/Feature/Gallery/ExploreTest.php` e `tests/Feature/Gallery/AdminGalleryTest.php` — AC2.1–AC2.4 e AC3.1–AC3.5/UI-02.

## Phase 4: Cobertura de aceitação e verificação integrada

Antes de implementar, leia:
1. `.spec/features/public-gallery-and-remix/SPEC.md` — requisitos RIGID que esta fase cobre
2. `.spec/features/public-gallery-and-remix/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T04 — Cobertura de aceitação e verificação integrada
      Arquivos: `tests/Feature/Gallery/ExploreTest.php`, `tests/Feature/Gallery/AdminGalleryTest.php`, `tests/Feature/Projects/ShowTest.php` se necessário para regressão
      Mudança: escrever Pest feature tests nos caminhos exatos para cada AC1.x–AC5.x, schema unique, autorização, UI/data-test, clone de remix, flags draft, filtros admin, session notice, opt-out, URL de storage e benchmark RNF-01 como meta soft; preservar e executar regressão do Show existente.
      Cobre: RF-01, RF-02, RF-03, RF-04, RF-05, RF-06, RNF-01, RNF-02, RNF-03, RNF-04, UI-01, UI-02
      Acceptance criteria: ambos os arquivos existem e têm blocos Pest identificáveis por AC; `php artisan test --compact --filter=ExploreTest` e `--filter=AdminGalleryTest` passam; todos os ACs da SPEC têm pelo menos uma assertion verificável e não há regressão em `Projects/ShowTest.php`.
      Testes: `php artisan test --compact --filter=ExploreTest`, `php artisan test --compact --filter=AdminGalleryTest`, `php artisan test --compact tests/Feature/Projects/ShowTest.php` — executar a cobertura nova e a regressão relacionada.

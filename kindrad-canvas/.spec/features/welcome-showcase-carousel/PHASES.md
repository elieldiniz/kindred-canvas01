# Phases: welcome-showcase-carousel

Gerado por /plan a partir de PLAN.md — view executável para `./ralph.sh .spec/features/welcome-showcase-carousel/PHASES.md`.

## Phase 1: Foundation

Antes de implementar, leia:
1. `.spec/features/welcome-showcase-carousel/SPEC.md` — requisitos RIGID RF-01, RF-04 (factory), constraints de schema
2. `.spec/features/welcome-showcase-carousel/PLAN.md` — decomposição completa, dependências e riscos

- [ ] T01 — Create showcase_items migration, model, and factory
      Arquivos: `database/migrations/YYYYMMDDHHMMSS_create_showcase_items_table.php`, `app/Models/ShowcaseItem.php`, `database/factories/ShowcaseItemFactory.php`
      Mudança: migration cria tabela `showcase_items` com `id`, `title` (string nullable 255), `image_path` (string NOT NULL), `sort_order` (integer default 0), `is_active` (boolean default true), `timestamps`; model com `$fillable = ['title','image_path','sort_order','is_active']` e `$casts = ['is_active' => 'boolean','sort_order' => 'integer']` (sem SoftDeletes); factory gera `UploadedFile::fake()->image(...)` + `image_path` determinístico, com state `inactive()`.
      Cobre: RF-01, RF-04 (suporte de teste)
      Acceptance criteria: `php artisan migrate:fresh` cria a tabela com todas as 7 colunas nos tipos/defaults especificados; `App\Models\ShowcaseItem::factory()->create()` retorna instância persistida com `is_active=true` e `sort_order` inteiro por padrão.
      Testes: exercitado indiretamente via `tests/Feature/Admin/ShowcaseTest.php` (AC1) em Phase 4.

## Phase 2: Admin showcase livewire

Antes de implementar, leia:
1. `.spec/features/welcome-showcase-carousel/SPEC.md` — requisitos RIGID RF-03, RF-04
2. `.spec/features/welcome-showcase-carousel/PLAN.md` — decomposição completa, dependências e riscos
3. `app/Livewire/Admin/Users/Index.php` — referência verbatim do guard `abort_unless(auth()->user()?->is_admin === true, 403)` (linha 34) e do `->layout('components.layouts.admin', ...)` (linhas 202–204)
4. `routes/web.php` linhas 36–60 — grupo admin onde a nova rota será inserida

- [ ] T02 — Add admin route + Livewire component + view for showcase CRUD
      Arquivos: `routes/web.php`, `app/Livewire/Admin/Showcase/Index.php`, `resources/views/livewire/admin/showcase/index.blade.php`
      Mudança: registrar `Route::livewire('showcase', App\Livewire\Admin\Showcase\Index::class)->name('showcase.index');` dentro do grupo `Route::middleware('admin')->prefix('admin')->name('admin.')` em `routes/web.php` (após a rota `users` na linha 57); componente class-based com `mount(): void { abort_unless(auth()->user()?->is_admin === true, 403); }`, props públicas `$title`, `$image` com `#[Validate]` (`image|mimes:jpeg,png,webp|max:5120`), actions `create()` (gera `{uuid}.{ext}`, `Storage::disk(config('generation.disk'))->putFileAs('showcase', $image, $filename)`, cria `ShowcaseItem` com `sort_order = (max ?? 0) + 1`), `updateTitle(int $id, string $title)`, `toggleActive(int $id)`, `moveUp(int $id)` / `moveDown(int $id)` (troca `sort_order` com vizinho; no-op nas pontas), `delete(int $id)` (deleta arquivo do disco antes da linha); `render()` retorna `ShowcaseItem::query()->orderBy('sort_order','ASC')->get()` com `->layout('components.layouts.admin', ['header' => __('Showcase')])`; view com header + form de upload (`data-test="admin-showcase-upload-form"`) + lista com thumbnail, título editável inline, toggle, botões up/down/edit/delete (`data-test="admin-showcase-row-{id}"`) usando componentes Flux.
      Cobre: RF-03, RF-04
      Acceptance criteria: `php artisan route:list --name=admin.showcase` mostra a rota `admin.showcase.index`; `Livewire::test(App\Livewire\Admin\Showcase\Index::class)` instancia sem 403 para usuário admin; usuários não-admin recebem 403 em `GET /admin/showcase`.
      Testes: `tests/Feature/Admin/ShowcaseTest.php` (AC3, AC3b, AC4, AC4b) em Phase 4.

## Phase 3: Welcome carousel

Antes de implementar, leia:
1. `.spec/features/welcome-showcase-carousel/SPEC.md` — requisitos RIGID RF-02
2. `.spec/features/welcome-showcase-carousel/PLAN.md` — decomposição completa, dependências e riscos
3. `resources/views/welcome.blade.php` linhas 225–229 — janela exata de inserção entre `<livewire:welcome.plans />` e o comentário `{{-- Footer --}}`

- [ ] T03 — Create showcase carousel partial and insert it into welcome.blade.php
      Arquivos: `resources/views/partials/showcase-carousel.blade.php`, `resources/views/welcome.blade.php`
      Mudança: novo partial com `<section class="border-t border-outline-variant" data-test="showcase-section" id="showcase">` contendo `@php $items = \App\Models\ShowcaseItem::query()->where('is_active', true)->orderBy('sort_order')->get(); @endphp`, header com kicker literal `Showcase` + h2 literal `Create keepsakes. Crafted with care.` (envoltos em `__()` é FLEXIBLE), track horizontal `<div class="flex snap-x scroll-smooth overflow-x-auto ...">` com `@foreach` de cards `aspect-[3/4] rounded-2xl shadow-2xl` exibindo `<img src="{{ Storage::disk(config('generation.disk'))->url($item->image_path) }}" loading="lazy">` e título opcional (`data-test="showcase-card-{id}"`, `data-test="showcase-card-title-{id}"`); rotação `nth-child(odd|even)` entre `-2deg` e `+2deg` via `<style>` inline escopado à seção; auto-scroll CSS via `@keyframes showcase-scroll { from { transform: translateX(0) } to { transform: translateX(-50%) } }` em track duplicada, desabilitado por `@media (prefers-reduced-motion: reduce)`; `welcome.blade.php` recebe UMA linha `@include('partials.showcase-carousel')` entre a linha 226 (`<livewire:welcome.plans />`) e a linha 228 (comentário `{{-- Footer --}}`), com comentário `{{-- Showcase carousel --}}` acima para rastreabilidade.
      Cobre: RF-02
      Acceptance criteria: `GET /` retorna HTML onde `data-test="showcase-section"` aparece estritamente **depois** da markup de `<livewire:welcome.plans>` e **antes** de `<footer>`; itens com `is_active=false` não aparecem; itens ativos aparecem em ordem `sort_order` ASC.
      Testes: `tests/Feature/Welcome/ShowcaseCarouselTest.php` (AC2, AC2b) em Phase 4.

## Phase 4: Tests

Antes de implementar, leia:
1. `.spec/features/welcome-showcase-carousel/SPEC.md` — requisitos RIGID RF-05 e tabela de Acceptance Tests (AC1–AC5)
2. `.spec/features/welcome-showcase-carousel/PLAN.md` — estratégia de teste e dependências
3. `tests/Pest.php` — `RefreshDatabase` aplicado a `tests/Feature/*` (linhas 17–19)

- [ ] T04 — Write Pest feature tests for schema, welcome render, admin guard, CRUD, and upload pipeline
      Arquivos: `tests/Feature/Admin/ShowcaseTest.php`, `tests/Feature/Welcome/ShowcaseCarouselTest.php`
      Mudança: tests Pest com `RefreshDatabase` + `Storage::fake(config('generation.disk'))`: (1) `it_creates_showcase_items_table` — introspecção de schema confirma colunas/tipos/defaults; (2) `it_renders_showcase_carousel_between_plans_and_footer` — `GET /` com 1 item ativo e `assertSeeInOrder([..., 'livewire:welcome.plans', 'data-test="showcase-section"', '<footer'])`; (3) `it_only_renders_active_showcase_items_in_sort_order` — seed de 5 itens (3 ativos + 1 ativo `sort_order=5` + 1 inativo), assert ordem dos `data-test="showcase-card-{id}"` é `5,10,20,30`; (4) `it_shows_admin_showcase_index_for_admin_users_only` — admin 200, não-admin 403, guest redirect login; (5) `it_allows_admin_to_create_edit_toggle_reorder_and_delete_showcase_items` — `Livewire::test(...)` cobre upload → `image_path='showcase/{uuid}.png'`, `updateTitle`, `toggleActive`, `moveUp/moveDown`, `delete` (linha + arquivo apagados); (6) `it_uploads_image_to_configured_disk_with_expected_path` — `UploadedFile::fake()->image('x.png')` válido aceito, `image/gif` rejeitado, arquivo > 5 MB rejeitado; (7) `it_deletes_image_from_storage_when_showcase_item_is_deleted` — após `delete()`, `Storage::disk(config('generation.disk'))->exists($item->image_path) === false`.
      Cobre: RF-01 (AC1), RF-02 (AC2, AC2b), RF-03 (AC3, AC3b), RF-04 (AC4, AC4b), RF-05 (AC5)
      Acceptance criteria: `php artisan test --compact --filter='ShowcaseTest|ShowcaseCarouselTest'` roda todos os 7 testes verdes; nenhum é skipped nem marcado `todo`; AC1–AC5 todos satisfeitos.
      Testes: este próprio task é a superfície de teste.
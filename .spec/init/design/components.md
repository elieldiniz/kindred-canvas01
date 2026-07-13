# Kindred Canvas — Component Library

<!-- inputs: project-description.md@sha256:4fb8c4284951 user-stories.md@sha256:880bd7ad3732 database-schema.md@sha256:2906da65676a -->

> What Flux gives us for free, what we must build custom, and where. Read this before any UI phase — pick the right component before reaching for a generic `<div>`.

## TL;DR

| Need | Use |
|---|---|
| Form input/select/textarea/checkbox/radio/toggle | **Flux** (`<flux:input>`, `<flux:select>`, `<flux:textarea>`, etc.) |
| Standard button | **Flux** (`<flux:button variant="primary">`) |
| Sidebar layout | **Flux** (`<flux:sidebar>`) + custom nav items |
| Topbar | **Flux** (`<flux:navbar>`) |
| Card with simple padding | **Flux** (`<flux:card>`) |
| Modal | **Flux** (`<flux:modal>`) |
| Toast / notification | **Flux** (`<flux:toast>`) |
| Brand sidebar (custom palette) | **Custom** — `components/layout/sidebar.blade.php` |
| Glass card with image bleed / hover lift | **Custom** — `components/glass-card.blade.php` |
| Project card (full image + gradient overlay + edit button) | **Custom** — `components/project-card.blade.php` |
| Wizard tile (icon + title + check + selection glow) | **Custom** — `components/wizard/tile.blade.php` |
| Wizard progress bar | **Custom** — `components/wizard/progress-bar.blade.php` |
| Credit balance widget (hero) | **Custom Livewire** — `<livewire:components.credit-balance-widget>` |
| Generation card (with status pill) | **Custom** — `components/generation-card.blade.php` |
| Result side panel | **Custom** — `components/result-side-panel.blade.php` |
| AI generating controller (loader + timeline) | **Custom** — `components/generation/controller.blade.php` |
| Timeline stepper (6 steps) | **Custom** — `components/generation/timeline-stepper.blade.php` |
| Toast variants (success / info / error) | **Flux toast** + custom wrapper for style pass |
| Drag-and-drop upload area | **Custom** — `components/upload/dropzone.blade.php` |
| Data table (admin) | **Custom** + Flux `table.index` pattern |

---

## A. Layout Components

### A.1 Sidebar — `resources/views/components/layout/sidebar.blade.php`
**Status:** Custom (replaces Flux sidebar to match mock palette).

- Width `260px`, fixed left, `bg-surface-container border-r border-outline-variant`.
- Slots: `logo`, `cta`, `nav`, `footer`.
- Logo block: `w-10 h-10 bg-primary-container rounded-lg flex items-center justify-center` + `palette` icon (FILL 1, on-primary-container) + headline-md title "Kindred Canvas" + label-md subtitle "AI Mug Design" (opacity-70 on-surface-variant).
- Primary CTA "New Project": `bg-primary text-on-primary font-bold rounded-xl py-3` with `add_circle` icon; `hover:scale-95 shadow-lg shadow-primary/20`.
- Nav items: `flex items-center gap-3 py-3 px-4 rounded-lg label-md`. Active: `bg-secondary-fixed-dim text-on-secondary-fixed font-bold`. Inactive: `text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest`.
- Footer block: `pt-stack-md border-t border-outline-variant space-y-2` with Help + Sign Out.

### A.2 Topbar — `resources/views/components/layout/topbar.blade.php`
**Status:** Custom.

- `fixed top-0 right-0 w-[calc(100%-260px)] h-16 bg-surface/80 backdrop-blur-md border-b border-outline-variant shadow-sm`.
- Left: rounded-full search input with leading `search` icon.
- Center: text links (Marketplace / Tutorials) — hidden on mobile.
- Right: `Upgrade` pill button (outline-primary), notifications icon, avatar (`w-8 h-8 rounded-full border border-outline`).

### A.3 Wizard Topbar — `resources/views/components/layout/wizard-topbar.blade.php`
**Status:** Custom.

- Logo left (small, `w-8 h-8 bg-primary rounded-lg` + palette icon) + headline-md wordmark.
- Right: Exit button (ghost) with `close` icon.

### A.4 Wizard Footer — `resources/views/components/layout/wizard-footer.blade.php`
**Status:** Custom.

- Sticky bottom, `border-t border-outline-variant bg-surface-container/30 backdrop-blur-sm px-gutter py-stack-md`.
- Back (left, disabled state on first step) / Current Selection (center, mono-sm uppercase label + label-md primary value) / Continue (right, primary pill `rounded-full` with arrow).

### A.5 App Shell — `resources/views/layouts/app.blade.php`
**Status:** Extends starter kit.

- `<html class="dark">` (dark-mode forced).
- Loads fonts via `<link>` or `@import`: Inter (400/500/600/700), Geist (400/500), Geist Mono (400), Material Symbols Outlined variable font.
- Loads custom CSS: `.glass-card`, `.selection-glow`, `.active-glow`, `.shimmer-effect`, `.float-anim`, `.custom-scrollbar`.
- Body: includes sidebar + topbar + `<main class="ml-sidebar-width mt-16">`.

### A.6 Wizard Shell — `resources/views/layouts/wizard.blade.php`
**Status:** Custom.

- No sidebar. Wizard topbar at top, centered main, wizard footer at bottom.
- Body: `bg-background min-h-screen flex flex-col`.

---

## B. Reusable Cards & Tiles

### B.1 Glass Card — `components/glass-card.blade.php`
**Status:** Custom (reusable across dashboard, wizard, history).

```html
<div class="glass-card rounded-2xl p-6 {{ $class }}">
  {{ $slot }}
</div>
```

CSS:
```css
.glass-card {
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: border-color 150ms ease;
}
.glass-card:hover {
  border-color: rgba(192, 193, 255, 0.3);
}
```

### B.2 Stat Card (Bento) — `components/stat-card.blade.php`
**Props:** `icon`, `value`, `label`, `delta` (optional mono-sm primary/secondary), `interactive` (bool — switches to primary hover style).

```html
<div class="glass-card rounded-2xl p-6 flex flex-col justify-between h-40">
  <div class="flex justify-between items-start">
    <span class="material-symbols-outlined text-primary-fixed-dim">{{ $icon }}</span>
    @isset($delta)
      <span class="font-mono-sm text-mono-sm text-primary">{{ $delta }}</span>
    @endisset
  </div>
  <div>
    <p class="font-headline-md text-headline-md">{{ $value }}</p>
    <p class="font-label-md text-label-md text-on-surface-variant">{{ $label }}</p>
  </div>
</div>
```

### B.3 Project Card — `components/project-card.blade.php`
**Props:** `imageUrl`, `categoryLabel`, `title`, `projectId`, `editUrl`.

```html
<div class="group relative aspect-[4/5] rounded-2xl overflow-hidden glass-card transition-transform duration-500 hover:-translate-y-2">
  <img class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" src="{{ $imageUrl }}" alt="{{ $title }}"/>
  <div class="absolute inset-0 bg-gradient-to-t from-background via-background/20 to-transparent opacity-60 group-hover:opacity-80 transition-opacity"></div>
  <div class="absolute bottom-0 left-0 right-0 p-6">
    <div class="flex justify-between items-end">
      <div>
        <p class="font-mono-sm text-mono-sm text-primary mb-1 uppercase tracking-widest">{{ $categoryLabel }}</p>
        <h4 class="font-headline-md text-headline-md text-on-surface">{{ $title }}</h4>
      </div>
      <a href="{{ $editUrl }}" class="w-10 h-10 rounded-full bg-surface/40 backdrop-blur-md flex items-center justify-center border border-white/10 hover:bg-primary hover:text-on-primary transition-all">
        <span class="material-symbols-outlined">edit</span>
      </a>
    </div>
  </div>
</div>
```

### B.4 Generation Card (history) — `components/generation-card.blade.php`
**Props:** `imageUrl` (nullable for in-progress), `title`, `timestamp`, `status` (`ready|processing|failed`), `onClick`.

- Top: `aspect-[4/3]` cover or in-progress spinner on `bg-surface-container-high`.
- Status pill (top-right): `px-2 py-1 bg-black/60 backdrop-blur-md rounded-md font-mono-sm text-mono-sm text-white` — text is `READY` / `Rendering...` / `FAILED`.
- Bottom: title (label-md bold truncate) + row (timestamp mono-sm + favorite + more_vert icons).

### B.5 Wizard Tile — `components/wizard/tile.blade.php`
**Props:** `icon`, `title`, `description`, `imageUrl` (optional, for style step), `selected` (bool).

Two variants:
- **Text tile (category step):** glass-card `rounded-xl p-stack-lg text-left group`; 12×12 icon tile (bg-surface-container-high text-primary group-hover:scale-110) + check_circle badge (opacity 0 → 100 on selected); headline-md title + label-md description.
- **Image tile (style / layout step):** `aspect-square relative rounded-xl overflow-hidden border border-outline-variant group`; full-bleed image hover-scale-105; gradient overlay bottom; label row `icon + name`; selection-glow on selected.

CSS:
```css
.selection-glow {
  box-shadow: 0 0 0 2px transparent;
  transition: box-shadow 200ms ease;
}
.selection-glow.active-selection,
.group.active-selection .selection-glow {
  box-shadow: 0 0 0 2px #c0c1ff;
}
```

### B.6 Result Side Panel — `components/result-side-panel.blade.php`
**Props:** `categoryLabel`, `styleLabel`, `layoutLabel`, `promptSnapshot`, `downloadUrl`, `regenerateUrl`, `editUrl`, `mockupUrl` (deferred but visible).

- Header "Result Details" + 3 pills (category / style / layout).
- "Prompt Used" card with `auto_awesome` icon label + italic body-md quote of `prompt_snapshot`.
- Action stack:
  - Primary: Download Design (with `download` icon, `hover:shadow-[0_0_20px_rgba(192,193,255,0.4)]`).
  - 2-col grid: Regenerate / Edit Art (outlined buttons).
  - Secondary: Create Mockup (disabled or hidden in MVP).

---

## C. Forms

### C.1 Inputs — Flux `<flux:input>` & `<flux:textarea>`
**Status:** Use Flux directly. Override variant to use the Material palette via `flux.config`.

Per component, set `variant="outline"` and ensure label uses `font-label-md text-label-md` (already default).

### C.2 Select — `<flux:select>`
**Status:** Use Flux directly.

Populate from lookup table query in the Livewire component.

### C.3 Toggle — `<flux:switch>` (admin is_admin toggle)
### C.4 Checkbox — `<flux:checkbox>` (style associations)
### C.5 Multi-select as checkable tag list — `components/form/tag-multiselect.blade.php`
**Props:** `options` (array of `{ id, name, color }`), `selected` (array of ids), `wireModel`.

- Renders `<flux:badge>` for each option with click handler that toggles selection state via Livewire.
- Selected state: `bg-primary text-on-primary`; unselected: `bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest`.

### C.6 File Upload Dropzone — `components/upload/dropzone.blade.php`
**Props:** `wireModel`, `accept`, `maxSizeMb`, `previewUrl` (optional).

- Empty state: `border-2 border-dashed border-primary/20 bg-primary/5 rounded-2xl p-stack-lg text-center hover:fill-primary/10`; large `cloud_upload` icon + headline-md "Drag your photo here" + label-md "JPEG / PNG / WEBP up to 10 MB".
- Preview state: aspect-square thumbnail + Replace + Remove buttons.

### C.7 JSON Editor — `components/form/json-editor.blade.php`
**Props:** `wireModel`, `placeholder` (default `{}`), `hint`.

- `<flux:textarea>` with monospace font (`font-mono-sm`) and live JSON validation badge.

---

## D. Livewire Components

### D.1 Credit Balance Widget — `<livewire:components.credit-balance-widget>`
**Listens on:** `private-user.{id}` channel.
**State:** `credit_balance` (int).
**Renders:** the hero credits card from S2.1.

Updates live when a `CreditBalanceChanged` event is broadcast (Phase 7.5 will need to wire this).

### D.2 Wizard Step Wrapper — `<livewire:projects.wizard>` (parent)
**State:** `step` (1-7), `project_id`, `mode_id`, `category_id`, `style_id`, `layout_id`, `source_image_id`, `inputs`.
**Methods:**
- `selectMode($modeId)`
- `selectCategory($categoryId)`
- `selectStyle($styleId)`
- `selectLayout($layoutId)`
- `uploadSourceImage($file)`
- `removeSourceImage()`
- `updateInput($key, $value)`
- `next()`, `back()`, `submit()`

Renders the appropriate step Livewire child component via `<livewire:is>`.

### D.3 Project Show — `<livewire:projects.show>`
**State:** `project_id`, `selected_generation_id`.
**Listens on:** `private-user.{id}` channel for `GenerationUpdated` events.
**Methods:**
- `selectGeneration($id)` — swap preview
- `regenerate()` — submit new Generation
- `deleteProject()` — soft-delete

### D.4 Generation Controller — `<livewire:projects.generating>`
**State:** `generation_id`, `currentStage` (1-6), `progressPct`, `etaSeconds`.
**Listens on:** `private-user.{id}` channel.
**Updates:** Livewire polls status every 2s as fallback, broadcast updates immediately.

---

## E. Animation & Feedback

### E.1 Shimmer — `.shimmer-effect`
**Use:** progress bar fill during generation.
```css
.shimmer-effect {
  background: linear-gradient(90deg, transparent, rgba(192,193,255,0.4), transparent);
  background-size: 200% 100%;
  animation: shimmer 1.5s linear infinite;
}
@keyframes shimmer {
  from { background-position: -200% 0; }
  to   { background-position: 200% 0; }
}
```

### E.2 Float — `.float-anim`
**Use:** decorative orbs on generation page.
```css
@keyframes float-anim {
  0%, 100% { transform: translateY(0); }
  50%      { transform: translateY(-20px); }
}
.float-anim { animation: float-anim 8s ease-in-out infinite; }
```

### E.3 Pulse Glow — `.active-glow`
**Use:** primary CTA hover, active timeline step.
```css
.active-glow {
  box-shadow: 0 0 20px rgba(192, 193, 255, 0.4);
}
```

### E.4 Custom Scrollbar — `.custom-scrollbar`
```css
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #2a3548; border-radius: 10px; }
```

### E.5 Toast — Flux `<flux:toast>` with custom variant
**Use:** "Tip: try a different style" hint during generation, "Generation completed", "Credit refunded".

Style override:
- info: `bg-surface-container-high/80 border-primary/20 text-primary lightbulb icon`
- success: `bg-primary/10 border-primary/40 text-primary check icon`
- error: `bg-error-container/20 border-error/40 text-error error icon`

---

## F. Empty / Loading / Error States

### F.1 Empty State Card
```html
<div class="glass-card rounded-2xl p-stack-lg text-center flex flex-col items-center gap-stack-md">
  <div class="w-12 h-12 rounded-full bg-surface-container-high flex items-center justify-center">
    <span class="material-symbols-outlined text-on-surface-variant">{{ $icon }}</span>
  </div>
  <h3 class="font-headline-md text-headline-md text-on-surface">{{ $title }}</h3>
  <p class="font-body-md text-body-md text-on-surface-variant max-w-sm">{{ $description }}</p>
  @isset($action)
    <flux:button variant="primary" :href="$action['url']">{{ $action['label'] }}</flux:button>
  @endisset
</div>
```

**Empty-state library (icon, title, action):**
- Categories empty: `style`, "No styles available for this category", "Browse other categories"
- Layouts empty: `dashboard`, "No layouts available", —
- Projects empty: `palette`, "Start your first project", "New Project" → `/projects/new`
- Generations empty: `auto_awesome`, "Your first artwork is one click away", "Generate" → CTA
- Credits empty: `token`, "No credit activity yet", "Generate your first artwork" → `/projects/new`
- Admin table empty: `inbox`, "Nothing here yet", "Create {entity}" → admin create URL

### F.2 Loading Spinner
- Inline: `<flux:icon.loading class="size-5 text-primary" />`
- Block (full-page): centered `w-24 h-24 rounded-full border-2 border-primary/20 p-2` containing `border-t-2 border-primary animate-spin`

### F.3 Error Banner
```html
<div class="bg-error-container/20 border border-error/40 rounded-lg p-4 flex items-start gap-stack-md">
  <span class="material-symbols-outlined text-error">error</span>
  <div>
    <p class="font-label-md text-label-md font-bold text-error">{{ $title }}</p>
    <p class="font-body-md text-body-md text-on-surface mt-1">{{ $message }}</p>
  </div>
</div>
```

---

## G. Admin-Specific

### G.1 Data Table — `components/admin/data-table.blade.php`
**Props:** `columns` (array of `{ key, label, type }`), `rows` (collection), `actions` (array).

- Per DESIGN.md: zero horizontal borders; row-hover `bg-surface-container-high`; cell padding `py-stack-md` (16px).
- Status badges: pill with lookup slug → color (active=`text-primary`, inactive=`text-on-surface-variant opacity-60`).
- Action column: edit / delete icon buttons.

### G.2 Audit Log Row — `components/admin/audit-log-row.blade.php`
**Props:** `entry` (AuditLog with relationships eager-loaded).

- timestamp (mono-sm) | actor.email | action.name | target link | payload (truncated to 80 chars, click to expand).

### G.3 Confirm Modal — Flux `<flux:modal>` with custom content
**Use:** delete project, toggle admin on others.

- Title (headline-md), body (body-md), confirm (primary red variant), cancel (ghost).

---

## H. Status Pill Patterns

| Status | Background | Text | Icon |
|---|---|---|---|
| `waiting` | `bg-surface-container-high` | `text-on-surface-variant` | `schedule` |
| `processing` | `bg-primary/20` | `text-primary` | `sync` (spinning) |
| `completed` | `bg-primary/10` | `text-primary` | `check_circle` |
| `failed` | `bg-error-container/20` | `text-error` | `error` |

```html
<span class="px-3 py-1 rounded-full font-label-md text-label-md flex items-center gap-1 {{ $classes[$status] }}">
  <span class="material-symbols-outlined text-[14px] {{ $status === 'processing' ? 'animate-spin' : '' }}">{{ $icons[$status] }}</span>
  {{ $labels[$status] }}
</span>
```

---

## I. Asset Naming & Sizing

- **Thumbnails (categories, styles, layouts):** square 320×320, optimized WebP, lazy-loaded.
- **Project covers:** 4:5 aspect (per dashboard mock), 800×1000.
- **Generated artwork preview:** max-w-4xl, max-h-[85%] on result page; full-bleed in dashboard cards.
- **Icons:** Material Symbols variable font; outlined for nav (FILL 0), filled for active/brand (FILL 1).
- **Avatars:** 8×8 rounded-full, 32×32 from OAuth provider.

---

## Appendix: Build Order

When implementing UI phases, build components in this order so later phases can compose them:

1. **Phase 4.1:** A.5 app shell + A.1 sidebar + A.2 topbar + B.1 glass-card + B.2 stat-card + D.1 credit-balance-widget
2. **Phase 5.1:** A.1 sidebar extended with admin nav + B.1 glass-card (admin layout)
3. **Phase 5.2–5.7:** G.1 data-table + C.1–C.4 Flux forms + C.5 tag-multiselect + C.7 json-editor + C.6 dropzone
4. **Phase 5.8:** G.2 audit-log-row + B.1 glass-card metrics
5. **Phase 6:** A.3 wizard-topbar + A.4 wizard-footer + A.6 wizard-shell + B.5 wizard-tile + C.1–C.6 wizard forms + D.2 wizard step wrapper
6. **Phase 7.5:** D.4 generation controller (loader + timeline) + E.1 shimmer + E.5 toast
7. **Phase 8.1:** B.3 project-card + B.4 generation-card + B.6 result-side-panel + D.3 project-show
8. **Phase 4.2:** S6 list view (table pattern from G.1)
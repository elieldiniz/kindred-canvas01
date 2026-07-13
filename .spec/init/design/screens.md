# Kindred Canvas — Screens Inventory

<!-- inputs: project-description.md@sha256:4fb8c4284951 user-stories.md@sha256:880bd7ad3732 database-schema.md@sha256:2906da65676a -->

> Each screen maps to user stories, design source (mock HTML/screenshot in `stitch_kindred_canvas_ai_interface/`), and the Flux/Tailwind components to use. The layout shell (`resources/views/layouts/app.blade.php`) is shared across all authenticated screens.

## Conventions

- **Layout shell (authenticated):** fixed left sidebar (260px) + topbar (64px) + scrollable main canvas with `margin-page` (40px) and `container-max` (1440px).
- **Layout shell (wizard):** no sidebar, minimal topbar with logo + Exit button (per `creation_wizard/code.html:134-145`); centered max-wizard content; sticky footer with Back / Continue.
- **Component order in every screen:** logo (10×10) + product wordmark → primary CTA (`New Project`) → nav → footer (Help/Sign Out).

---

## S1. Auth Screens (Phase 3)

**Status:** Mostly built by Laravel Fortify starter kit. Style pass needed.

| ID | Screen | Mock Source | Stories | Flux Components |
|---|---|---|---|---|
| S1.1 | Login | Fortify default | US-1.2 | `<flux:card>`, `<flux:input>`, `<flux:button>`, `<flux:link>` |
| S1.2 | Register | Fortify default | US-1.1 | Same + "Continue with Google" `<flux:button variant="ghost">` |
| S1.3 | Forgot Password | Fortify default | US-1.5 | `<flux:input>`, `<flux:button>` |
| S1.4 | Reset Password | Fortify default | US-1.5 | Same |
| S1.5 | Two-Factor Challenge | Fortify default (Passkeys/2FA shipped) | — | Fortify default |
| S1.6 | Passkey Enrollment | `pages::auth.*` (shipped) | — | Fortify default |

**Style pass:** All auth pages share a centered glass card on the dark background; logo above the card; Geist "AI Mug Design" subtitle.

---

## S2. Dashboard (Phase 4.1) — `dashboard/code.html`

**Layout:** sidebar + topbar + scrollable main. Hero → bento stats → recent projects.

### S2.1 Hero Section
- **Welcome headline:** "Welcome back, Curator." (headline-lg, on-surface)
- **Subtitle:** "Your creative studio is ready. What will you manifest today?" (body-lg, on-surface-variant)
- **Credits card** (right column, glass-card, max-w-xs):
  - Label "AVAILABLE CREDITS" (label-md, uppercase, tracking-widest, on-surface-variant)
  - Big number (display-lg, primary) + "/ total" (headline-md, on-surface-variant)
  - Progress bar (h-1.5, surface-variant track, primary fill with `0 0 10px #c0c1ff` shadow)

### S2.2 Stats Bento Grid (3 cards, col-span-4 each, h-40, glass-card)
1. **Total Generations** — `auto_awesome` icon (primary-fixed-dim), count (headline-md), label (label-md), "+12% this week" mono-sm primary
2. **Style Popularity** — `trending_up` (secondary), top style name (headline-md), label (label-md), "Cyberpunk Peak" mono-sm secondary
3. **Upgrade Pack** (full primary hover) — `rocket_launch` icon, "Premium Pack" (headline-md), "Unlock 4K Export" (label-md); on hover: bg-primary-container, text-on-primary

### S2.3 Recent Projects Grid (3 cols, aspect-[4/5])
- Project card pattern:
  - Full-bleed image with `group-hover:scale-110` (duration-700)
  - Gradient overlay `from-background via-background/20 to-transparent` (opacity 60 → 80 on hover)
  - Bottom-left: mono-sm uppercase tracking-widest primary "category series" + headline-md "project name"
  - Bottom-right: circular icon button (`w-10 h-10 rounded-full bg-surface/40 backdrop-blur-md border border-white/10`) with `edit` icon; hover bg-primary text-on-primary
- "View all projects →" link at top-right of section (label-md primary with chevron)

**Components to use:** `<flux:card>` won't fit the image-bleed layout; build a custom `resources/views/components/project-card.blade.php`. Use `<livewire:components.credit-balance-widget>` for the hero card (wired to the broadcast in Phase 7.5).

---

## S3. Project Wizard (Phase 6) — `creation_wizard/code.html`

**Layout:** no sidebar; minimal topbar (logo + Exit); centered wizard canvas; sticky footer with Back / Current Selection / Continue.

### S3.1 Top Progress Indicator
- "STEP 01 OF 04" (mono-sm primary, uppercase, tracking-widest) + section name "Identity & Essence" (mono-sm on-surface-variant)
- Progress bar: `h-[2px] bg-surface-container-highest rounded-full`; fill `bg-primary` with `shadow-[0_0_10px_rgba(192,193,255,0.6)]`; width = `(step / total) * 100%`

### S3.2 Step Content (centered, max-w-5xl or 6xl)
- **Display headline** (display-lg): "Who is the star of this canvas?"
- **Subtitle** (body-lg on-surface-variant, max-w-xl mx-auto): "Select the primary subject…"
- **Tile grid:**
  - **Category step (3 cols, md):** glass-card `rounded-xl p-stack-lg text-left group`. Header row: 12×12 icon tile (`bg-surface-container-high text-primary group-hover:scale-110`) + check_circle badge (opacity 0 → 100 on `.active-selection`). Title (headline-md) + label-md description.
  - **Style step (4 cols, lg):** `aspect-square` thumbnails; full-bleed image with hover-scale-105; gradient overlay bottom; label row `icon (primary, FILL 1) + name (label-md bold)`; `border-outline-variant` → `selection-glow` on `.active-selection`.
  - **Layout step:** similar grid; 4-col aspect-square tiles with safe-area preview overlay.
  - **Source Image step:** large dashed-border drop zone (2px dashed `border-primary/10`, `hover: fill-primary/5`); uploaded state shows thumbnail with Replace/Remove buttons.
  - **Inputs step:** form with `<flux:input>` fields for name/phrase/theme/dedicatoria; maxlength indicators.
  - **Review step:** read-only summary card list with Edit buttons per row; sticky CTA "Generate" (primary button, disabled when `credit_balance == 0` with Geist tooltip "You're out of credits").

### S3.3 Footer (sticky, `border-t border-outline-variant bg-surface-container/30 backdrop-blur-sm`)
- Back button (left): `<flux:button variant="ghost">` with `arrow_back`; disabled state on first step
- Current Selection (center, hidden on mobile): mono-sm uppercase label + label-md primary value (e.g., "Pets • Watercolor")
- Continue button (right): primary, `px-stack-lg py-3 rounded-full`, `hover:scale-105 hover:shadow-[0_0_20px_rgba(192,193,255,0.4)]` with `arrow_forward` icon

**Components to use:** Build custom `wizard-step-card`, `wizard-tile` (for category/style/layout), `wizard-progress-bar`. For inputs, use `<flux:input>` with `wire:model.live`.

---

## S4. AI Generating (Phase 7.5) — `ai_generating.../code.html`

**Layout:** full sidebar + topbar; main canvas = immersive centered generation controller.

### S4.1 Atmospheric Background
- Two floating orbs: `w-[500px] h-[500px] bg-primary/5 blur-[120px] rounded-full -top-40 -right-40 float-anim`; second `w-[400px] h-[400px] bg-secondary-container/10 blur-[100px] rounded-full -bottom-40 -left-40 float-anim animation-delay -2s`. Pure decoration, `pointer-events: none`.

### S4.2 Hero Status (centered)
- Minimalist loader: `w-24 h-24 rounded-full border-2 border-primary/20 p-2` containing `border-t-2 border-primary animate-spin`; center icon `auto_awesome animate-pulse text-primary`
- Headline: "IA gerando imagem..." (display-lg on-surface) — **Brazilian Portuguese copy** per mock
- Status message (body-lg on-surface-variant, max-w-md): "We're weaving your concepts into a high-fidelity masterpiece." — Livewire-bound, updates as the job progresses.

### S4.3 Live Timeline (glass panel `bg-surface-container/60 backdrop-blur-xl border border-white/5 rounded-2xl p-8 shadow-2xl`)
6-step stepper (grid-cols-6):
1. **Photo analyzed** — completed (`bg-primary/20`, check icon)
2. **Prompt created** — completed
3. **Estilo aplicado** — completed
4. **Generating** — active (`bg-primary text-on-primary w-10 h-10 -mt-1 shadow-lg shadow-primary/20 active-glow` + `cycle animate-spin duration-3s`)
5. **Composition** — pending (`bg-surface-variant/40`, `architecture` icon, opacity-50 label)
6. **Finalizing** — pending (`done_all` icon)
Connective line: `bg-outline-variant/30` track + `bg-primary w-[X%] shadow-[0_0_10px_#c0c1ff]` fill

### S4.4 Progress Bar
- "Estimated completion: 18s" (left, label-md on-surface-variant) + "64%" (right, label-md primary bold)
- Bar: `h-1.5 bg-surface-container-highest rounded-full`; fill `bg-primary shimmer-effect rounded-full transition-all duration-700 ease-out`

### S4.5 Metadata Cards (3-col grid)
- **Image Model:** "Kindred-Diffusion v4.2"
- **Dimensions:** "1024 x 1024 (HD)"
- **Style Preset:** "Minimalist Ceramic"

### S4.6 Toast (bottom-right, animate-bounce duration-3s)
- `bg-surface-container-high/80 backdrop-blur-md border border-primary/20 px-4 py-3 rounded-lg shadow-xl`; `lightbulb` icon (primary, FILL 1) + tip text

**Components:** Custom Livewire component `pages::projects.generating`; no Flux component fits this custom shell.

---

## S5. Generation Result (Phase 8.1) — `generation_result/code.html`

**Layout:** full sidebar + topbar; main canvas split 70/30 hero + side panel, then full-width history grid below.

### S5.1 Hero Section (h-[calc(100vh-64px-300px)])
- **Left 70%:** image canvas on `bg-surface-container-lowest`; centered max-w-4xl max-h-[85%] `rounded-2xl overflow-hidden shadow-2xl border border-white/10 group`. Hover overlay: `bg-black/40 opacity-0 group-hover:opacity-100` containing two circular icon buttons (`zoom_in`, `fullscreen`) with `p-4 bg-white/10 backdrop-blur-md rounded-full text-white hover:bg-white/20 active:scale-90`.
- **Right 30% side panel** (`border-l border-outline-variant bg-surface-container flex flex-col p-8 overflow-y-auto custom-scrollbar`):
  - Heading: "Result Details" (headline-md bold)
  - Tag row: pills (`px-3 py-1 bg-surface-container-high rounded-full font-label-md text-label-md text-primary`) for category, style, layout
  - "Prompt Used" card (`bg-surface-container-low p-4 rounded-xl border border-outline-variant/30`): icon label "auto_awesome + Prompt Used" + body-md italic quote of the actual prompt snapshot
  - Action stack (mt-auto):
    - **Download Design** — primary, `py-4 bg-primary text-on-primary rounded-xl font-bold` with `download` icon, `hover:shadow-[0_0_20px_rgba(192,193,255,0.4)] active:scale-98`
    - **Regenerate / Edit Art** — 2-col grid of outlined buttons (`border border-outline-variant rounded-xl hover:bg-surface-container-highest active:scale-95`)
    - **Create Mockup** — secondary, `bg-surface-container-highest text-on-surface border border-white/5 hover:bg-surface-bright` with `coffee` icon

### S5.2 History Section (p-margin-page bg-surface)
- Header row: "Your History" (headline-lg) + subtitle (body-md on-surface-variant); right: "View all N designs →" link (label-md primary)
- Grid (4 cols lg / 2 cols sm): history cards
  - `bg-surface-container rounded-2xl overflow-hidden border border-outline-variant hover:border-primary/50 transition-all hover:shadow-xl`
  - Top: `aspect-[4/3] object-cover group-hover:scale-105 duration-500`; top-right corner: status pill (`px-2 py-1 bg-black/60 backdrop-blur-md rounded-md font-mono-sm text-mono-sm text-white`) — values: READY / PROCESSING (with spinner) / FAILED
  - Bottom: title (label-md bold truncate) + row (timestamp mono-sm on-surface-variant + favorite + more_vert icons)

**Components:** Custom `resources/views/components/generation-card.blade.php`, `components/result-side-panel.blade.php`, `components/history-card.blade.php`. Use `<flux:button variant="primary">` for Download.

---

## S6. Credits History (Phase 4.2)

**Layout:** same authenticated shell, no hero, simple list view.

- **Header:** "Credits" (headline-lg) + subtitle body-md
- **List:** paginated (25/page) list of `credit_transactions` rows, newest first
  - Each row: date (mono-sm) | reason label | delta (signed, primary if +, on-error if -) | balance_after (mono-sm on-surface-variant) | notes (if admin_grant, label-md italic) | optional reference link
- **Empty state:** centered glass-card with `token` icon (h-12 w-12 text-on-surface-variant) + "No credit activity yet" + "Generate your first artwork" CTA → `/projects/new`

---

## S7. Admin Back-Office (Phase 5)

**Layout:** same sidebar + topbar; main = `<aside class="w-sidebar-width bg-surface-container">` removed; instead a slim **secondary sidebar** (`w-64 border-r border-outline-variant bg-surface-container`) listing admin sections, with main on the right.

### S7.1 Admin Dashboard (Phase 5.8)
- Metrics tiles (4-col): Total Users, New Users (7d), Total Generations, Credits in Circulation
- Generation status donut (5 statuses)
- Recent admin actions table (last 20 audit_logs)

### S7.2 CRUD Index Pages (Phase 5.2–5.7)
- **Pattern:** page header (headline-lg + "New {Entity}" primary button right) → search input + status filter (Flux `<flux:select>`) → data table.
- **Table style (per DESIGN.md):** zero horizontal borders; row-hover `bg-surface-container-high`; cell padding `py-stack-md` (16px vertical); primary text label-md, secondary mono-sm.
- **Columns:** name (headline-md bold), slug (mono-sm on-surface-variant), status badge (pill: `bg-surface-container-high rounded-full text-primary label-md`), updated_at (mono-sm), actions (edit / delete icons)
- **Pagination:** Flux default at bottom

### S7.3 CRUD Form Pages
- **Pattern:** single-column `max-w-2xl` glass-card with sections. Each section: section heading (label-md uppercase tracking-widest on-surface-variant) + field rows.
- **Fields:**
  - text inputs → `<flux:input>`
  - textareas → `<flux:textarea>`
  - select (status, mode, etc.) → `<flux:select>` populated from lookup table
  - multi-select (style associations) → custom checkable tag list with `<flux:badge>` and click toggle
  - image upload (thumbnails) → `<flux:input type="file">` with preview pane
  - JSON editor (layout safe_area_overlay) → `<flux:textarea>` with validation hint

### S7.4 Audit Log Viewer (Phase 5.8)
- Table: timestamp (mono-sm) | actor email | action label | target (polymorphic link) | payload (truncated, expandable)
- Filters: actor (search), action (`<flux:select>` from audit_log_actions)

---

## S8. Project Show Page (Phase 8.1)

Combines S5.1 hero (latest completed) + S5.2 history (all generations) with the wizard's previous selections visible in a small panel.

- **Top:** project meta strip (headline-md title + mono-sm created_at + status badge)
- **Below:** split S5 hero pattern but with the wizard's inputs visible at top (`bg-surface-container-low p-4 rounded-xl border border-outline-variant/30` showing name/phrase/theme)
- **Bottom:** full history grid (S5.2)

---

## S9. Settings (Phase 3.3, already shipped)

Laravel Fortify provides profile / appearance / security pages. **Style pass needed:**
- Wrap each page in the same glass-card aesthetic
- Match the dashboard sidebar

---

## Out of MVP Screens (Phase 9, deferred)

- Mockup composer / multi-mockup picker
- Payment / plan upgrade screens
- Marketplace (mocked in dashboard nav as `Marketplace` link)
- Multi-product wizard entry (only `mug` shown for now)
- Light mode

---

## Appendix: Screen-to-Phase-to-Story Mapping

| Screen | Phase | Stories |
|---|---|---|
| S1.1–S1.6 | Phase 3.3 | US-1.1, US-1.2, US-1.3, US-1.4, US-1.5 |
| S2 Dashboard | Phase 4.1 | US-2.1 |
| S3 Wizard | Phase 6 | US-3.1–3.7, US-6.1 |
| S4 Generating | Phase 7.5 | US-4.2 |
| S5 Result | Phase 8.1, 8.2 | US-5.1, US-5.2 |
| S6 Credits | Phase 4.2 | US-2.2 |
| S7 Admin | Phase 5 | US-7.1–7.8, US-8.3 |
| S8 Project Show | Phase 8.1 | US-5.1, US-5.3 |
| S9 Settings | Phase 3.3 | — |
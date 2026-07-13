# Kindred Canvas — Design Tokens

<!-- inputs: project-description.md@sha256:4fb8c4284951 user-stories.md@sha256:880bd7ad3732 database-schema.md@sha256:2906da65676a -->

> Source: `stitch_kindred_canvas_ai_interface/kindred_canvas/DESIGN.md` (Material 3 token set) + the 4 mock screens (`dashboard`, `creation_wizard`, `ai_generating...`, `generation_result`).

## Overview

Design system based on **Material 3 token names** mapped onto Tailwind 4 via `theme.extend.colors`. The aesthetic is **"Intelligent Creativity"** — a dark, cinematic, professional SaaS feel where the user's generated artwork is the focal point. Two voices: **Inter** for human-readable copy, **Geist** for technical/mono data. Single primary accent (`#c0c1ff` Electric Violet) used sparingly so it stays "electric".

## Brand Voice

- **Tone:** "curator, not customer" — premium, sophisticated, AI-as-creative-partner
- **Aesthetic:** Neo-minimalism × monochromatic precision, inspired by high-end dev tools and AI research platforms
- **Dark-mode first** — sophisticated and cinematic; light mode deferred

## Color Tokens (Material 3 → Tailwind)

These tokens already appear in the starter kit's `dashboard/code.html` tailwind config. Apply the same set globally via `tailwind.config.js`.

| Token | Hex | Role |
|---|---|---|
| `background` | `#081425` | Page background (Level 0) |
| `surface` | `#081425` | Same as background |
| `surface-dim` | `#081425` | — |
| `surface-bright` | `#2f3a4c` | — |
| `surface-container-lowest` | `#040e1f` | Modal/scrim base |
| `surface-container-low` | `#111c2d` | Inputs, topbar |
| `surface-container` | `#152031` | Cards, sidebar (Level 1) |
| `surface-container-high` | `#1f2a3c` | Elevated cards, hover |
| `surface-container-highest` | `#2a3548` | Modals/popovers (Level 2) |
| `surface-variant` | `#2a3548` | Dividers, ghost buttons |
| `on-surface` | `#d8e3fb` | Primary text |
| `on-surface-variant` | `#c7c4d7` | Secondary text |
| `outline` | `#908fa0` | High-contrast borders |
| `outline-variant` | `#464554` | Low-contrast borders |
| `primary` | `#c0c1ff` | **Electric Violet** — primary CTA, AI progress, active selection |
| `on-primary` | `#1000a9` | Text on primary |
| `primary-container` | `#8083ff` | Tinted primary background |
| `on-primary-container` | `#0d0096` | — |
| `primary-fixed` | `#e1e0ff` | — |
| `primary-fixed-dim` | `#c0c1ff` | Active nav row tint |
| `surface-tint` | `#c0c1ff` | Tinted surfaces |
| `inverse-primary` | `#494bd6` | — |
| `secondary` | `#bec6e0` | Secondary accent |
| `on-secondary` | `#283044` | — |
| `secondary-container` | `#3f465c` | — |
| `on-secondary-container` | `#adb4ce` | — |
| `secondary-fixed` | `#dae2fd` | — |
| `secondary-fixed-dim` | `#bec6e0` | **Active sidebar item background** |
| `on-secondary-fixed` | `#131b2e` | Text on secondary-fixed-dim |
| `tertiary` | `#c4c7c9` | — |
| `on-tertiary` | `#2d3133` | — |
| `error` | `#ffb4ab` | Error states |
| `on-error` | `#690005` | — |
| `error-container` | `#93000a` | Error backgrounds |
| `on-error-container` | `#ffdad6` | — |

## Typography

| Token | Family | Size / Line / Weight / Tracking | Use |
|---|---|---|---|
| `display-lg` | Inter | 48 / 56 / 700 / -0.04em | Hero "IA gerando imagem..." headlines |
| `headline-lg` | Inter | 32 / 40 / 600 / -0.02em | Section headings ("Recent Projects") |
| `headline-lg-mobile` | Inter | 28 / 36 / 600 / — | Same on small screens |
| `headline-md` | Inter | 24 / 32 / 600 / -0.01em | Card titles, panel headings |
| `body-lg` | Inter | 18 / 28 / 400 / — | Subtitles, descriptions |
| `body-md` | Inter | 16 / 24 / 400 / — | Default body |
| `label-md` | Geist | 14 / 20 / 500 / 0.02em | Nav items, button labels, form labels |
| `mono-sm` | Geist | 12 / 16 / 400 / — | AI metadata (model name, timestamps, status pills) |

**Rules:**
- Headlines: SemiBold/Bold with tight letter-spacing (editorial feel).
- Body: line-height 1.5× for dark-mode readability.
- Mono: only for technical readouts — AI model, dimensions, status pills, timestamps.

## Spacing Tokens

| Token | Value | Use |
|---|---|---|
| `container-max` | 1440 px | Max content width |
| `sidebar-width` | 260 px | Fixed left nav width |
| `gutter` | 24 px | Grid gaps, page horizontal padding |
| `margin-page` | 40 px | Outer page padding |
| `stack-sm` | 8 px | Tight vertical stacks |
| `stack-md` | 16 px | Default vertical stacks |
| `stack-lg` | 32 px | Section internal spacing |
| `section-gap` | 64 px | **Between major functional blocks** (hero → bento → recent projects) |

## Radius Tokens

| Token | Value | Use |
|---|---|---|
| `rounded-sm` | 0.25 rem | Tags, pills |
| `DEFAULT` | 0.5 rem | — |
| `rounded-md` | 0.5 rem | Inputs, small buttons |
| `rounded-lg` | 0.75 rem | Default cards |
| `rounded-xl` | 1.5 rem | Hero cards, primary buttons, glass cards |
| `full` | 9999 px | Pills, circular icon buttons, search input |

**Rules:**
- Standard: `rounded-lg` (cards/inputs/buttons) — software-like, modern but not bubbly.
- Large: `rounded-xl` for hero/upload areas.
- Shape geometry stays rigid on hover; only border color brightens.

## Elevation

| Level | Surface | Use |
|---|---|---|
| 0 | `background` (`#081425`) | Page |
| 1 | `surface-container` (`#152031`) | Cards, sidebar |
| 2 | `surface-container-highest` (`#2a3548`) | Modals, popovers |

**Shadows:** Linear-style, multi-stop, highly diffused. Example: `0 10px 15px -3px rgba(0,0,0,0.5), 0 4px 6px -2px rgba(0,0,0,0.5)`.

**Borders:** Every card and interactive element uses a 1px border with a low-opacity white top-stroke to "catch the light" against dark surfaces. Glass cards use `rgba(255,255,255,0.05)` border that brightens to `rgba(192,193,255,0.3)` on hover.

## Reusable Effects

| Effect | CSS | Use |
|---|---|---|
| `.glass-card` | `background: rgba(15,23,42,0.6); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.05);` | Cards, panels, stats |
| `.selection-glow` | `box-shadow: 0 0 0 2px #c0c1ff` (on `.active-selection`) | Wizard choice cards |
| `.active-glow` | `box-shadow: 0 0 20px rgba(192,193,255,0.4)` | Primary CTA hover, active timeline step |
| `.shimmer-effect` | `linear-gradient` keyframe sliding across | Generation progress bar |
| `.float-anim` | `transform: translateY(...)` keyframe | Decorative background orbs |
| Custom scrollbar | 4px wide, `#2a3548` thumb, transparent track | Sidebar, history grid |
| Hover lift | `hover:-translate-y-2` + duration-500 transition-transform | Project cards |

## Icons

Use **Material Symbols Outlined** with variable font settings:
- Default: `'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24`
- Filled (active/logo): `'FILL' 1`
- Default size: 20px (sidebar), 24px (header), 32px (wizard tiles)

## Responsiveness

- **Desktop:** sidebar `260px` + main grid; 12-column at `container-max 1440`.
- **Tablet (md):** sidebar collapses to a rail; grids collapse to 2-col / 3-col.
- **Mobile:** single-stack layout, `16px` horizontal margins, sidebar becomes a hamburger drawer.

## Implementation Notes

- The Tailwind config in `dashboard/code.html` is the canonical source — copy that block into `tailwind.config.js` and replace CDN with `@tailwindcss/vite` plugin (already configured in `kindrad-canvas/package.json`).
- Add the custom CSS (`.glass-card`, `.selection-glow`, etc.) to `resources/css/app.css`.
- The two scripts (Inter, Geist, Material Symbols) must be loaded via `@import url(...)` at the top of `app.css` or via the `@font-face` Vite plugin.
- Status pill colors: `READY` uses default; `PROCESSING` uses `primary`; `FAILED` uses `error`. Add `success` later when needed.
---
name: Kindred Canvas
colors:
  surface: '#081425'
  surface-dim: '#081425'
  surface-bright: '#2f3a4c'
  surface-container-lowest: '#040e1f'
  surface-container-low: '#111c2d'
  surface-container: '#152031'
  surface-container-high: '#1f2a3c'
  surface-container-highest: '#2a3548'
  on-surface: '#d8e3fb'
  on-surface-variant: '#c7c4d7'
  inverse-surface: '#d8e3fb'
  inverse-on-surface: '#263143'
  outline: '#908fa0'
  outline-variant: '#464554'
  surface-tint: '#c0c1ff'
  primary: '#c0c1ff'
  on-primary: '#1000a9'
  primary-container: '#8083ff'
  on-primary-container: '#0d0096'
  inverse-primary: '#494bd6'
  secondary: '#bec6e0'
  on-secondary: '#283044'
  secondary-container: '#3f465c'
  on-secondary-container: '#adb4ce'
  tertiary: '#c4c7c9'
  on-tertiary: '#2d3133'
  tertiary-container: '#8e9193'
  on-tertiary-container: '#272a2c'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#e1e0ff'
  primary-fixed-dim: '#c0c1ff'
  on-primary-fixed: '#07006c'
  on-primary-fixed-variant: '#2f2ebe'
  secondary-fixed: '#dae2fd'
  secondary-fixed-dim: '#bec6e0'
  on-secondary-fixed: '#131b2e'
  on-secondary-fixed-variant: '#3f465c'
  tertiary-fixed: '#e0e3e5'
  tertiary-fixed-dim: '#c4c7c9'
  on-tertiary-fixed: '#191c1e'
  on-tertiary-fixed-variant: '#444749'
  background: '#081425'
  on-background: '#d8e3fb'
  surface-variant: '#2a3548'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.04em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '600'
    lineHeight: 40px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Geist
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
    letterSpacing: 0.02em
  mono-sm:
    fontFamily: Geist
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 16px
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '600'
    lineHeight: 36px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  container-max: 1440px
  sidebar-width: 260px
  gutter: 24px
  margin-page: 40px
  stack-sm: 8px
  stack-md: 16px
  stack-lg: 32px
  section-gap: 64px
---

## Brand & Style
The design system is engineered to evoke a sense of **Intelligent Creativity**. It moves away from the "toy-like" interfaces of consumer photo editors toward a high-performance, professional SaaS environment. The aesthetic identity is a fusion of **Neo-Minimalism** and **Monochromatic Precision**, drawing inspiration from high-end developer tools and AI research platforms.

The system prioritizes clarity and focus, using a "dark-mode first" logic that feels sophisticated and cinematic. It utilizes expansive whitespace (structured as "negative space") to reduce cognitive load while maintaining an organized, high-density layout for power users. The emotional goal is to make the user feel like a curator rather than just a customer, providing a canvas that feels as premium as the final physical product.

## Colors
The palette is rooted in **deep blacks and refined grayscales** to create a high-contrast environment where user-generated content (the mugs) becomes the focal point.

- **Primary (Electric Violet):** Used exclusively for high-priority calls to action, AI progress states, and active selection indicators. It should be used sparingly to maintain its "electric" impact.
- **Base Surfaces:** The background uses a near-black (`#020617`), while containers use a slightly lighter slate (`#0F172A`) to create depth.
- **Accents:** Borders use a low-opacity white (10-15%) to create the "subtle border" effect characteristic of high-end SaaS platforms.
- **Functional Grays:** A range of slates is used for secondary text and disabled states, ensuring readability without competing with the primary content.

## Typography
The system utilizes **Inter** for its neutral, systematic clarity and high-performance legibility. To lean into the "AI/Modern" aesthetic, **Geist** is introduced for labels and technical data, providing a precise, developer-centric feel.

- **Hierarchy:** High contrast in weight is essential. Headlines should be SemiBold or Bold with tight letter-spacing for a "tight" editorial look.
- **Body Text:** Maintains a generous line-height (1.5x) to ensure readability within the dark-mode interface.
- **Technical Readouts:** Use the mono-variant for AI parameters, generation seeds, and coordinate data in the editor to reinforce the "intelligent tool" personality.

## Layout & Spacing
The layout follows a **structured fluid grid** with a fixed sidebar for primary navigation. 

- **Grid System:** A 12-column grid is used for the dashboard and admin views. Gaps are generous (24px) to avoid visual clutter.
- **Sidebar:** A collapsed or fixed 260px sidebar provides a consistent anchor. It should use a subtle vertical border rather than a shadow to separate from the main canvas.
- **Whitespace:** Use "Section Gaps" (64px+) between major functional blocks (e.g., between the Hero AI prompt and the Recent Projects gallery) to create the "Notion-like" organized feel.
- **Responsive:** On tablets, the sidebar collapses into a rail. On mobile, the 12-column grid collapses into a single-stack with 16px horizontal margins.

## Elevation & Depth
Elevation is achieved through **Tonal Layering** and **Multi-layered Shadows** rather than traditional skeuomorphism.

- **Surface Levels:** 
  - Level 0: Background (`#020617`).
  - Level 1: Cards and Sidebar (`#0F172A`).
  - Level 2: Modals and Popovers (`#1E293B`).
- **Shadows:** Use "Linear-style" shadows—highly diffused, multi-stop shadows. Example: `0 10px 15px -3px rgba(0,0,0,0.5), 0 4px 6px -2px rgba(0,0,0,0.5)`.
- **Borders:** Every card and interactive element should have a 1px border with a subtle gradient or a low-opacity white top-stroke to catch the "light" and define edges against the dark background.

## Shapes
The shape language is **geometric and precise**. 

- **Corner Radius:** A consistent 8px (`rounded-md`) is the standard for cards, inputs, and buttons. This provides a software-like feel that is modern but not "bubbly."
- **Large Components:** Hero sections or large image upload areas can scale to 16px (`rounded-xl`) to soften the visual impact of large containers.
- **Interactive States:** On hover, borders can subtly brighten, but shape geometry should remain rigid to maintain the professional aesthetic.

## Components
- **AI Prompt Input:** A large, centered text area with a subtle inner glow. The "Generate" button should be the only primary-colored element.
- **Project Cards:** Eschew simple tables. Use high-aspect-ratio cards with a large preview of the mug design, metadata in Geist Mono, and a "Glassmorphism" overlay for quick actions (Edit, Export).
- **Timeline/Progress:** A thin, horizontal line with a glowing pulse effect in the primary color. Use "stepper" labels that update dynamically as the AI cycles through "Ideating," "Refining," and "Finalizing."
- **Drag & Drop:** Use a dashed border with a 2px stroke and a 10% opacity primary color fill when an item is hovered over the area.
- **Sidebar:** Icons should be 20px, light-weight strokes (2px), with high-contrast active states (white icon on a subtle slate background).
- **Admin Tables:** While tables are used here for CRUD operations, they should have zero horizontal borders. Use row-hover highlights and generous cell padding (16px vertical) to maintain the premium feel.
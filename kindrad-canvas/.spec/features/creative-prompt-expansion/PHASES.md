# PHASES: creative-prompt-expansion

## Phase 1 — Schema and domain compatibility
- Add reversible columns and foreign key for `scene_presets.occasion_slug` and `scene_presets.subject_scope`.
- Add `projects.subject_types` and `projects.primary_subject_index`.
- Add `project_photos.instructions` and `project_photos.reference_image_id`.
- Update model casts, relationships, factories, and legacy fallbacks.

## Phase 2 — Seed data
- Add stable occasion slugs and descriptions.
- Add presets for Christmas, New Year, Mother's Day, Valentine's Day, Easter, Halloween, graduation, anniversary, and baby shower.
- Add non-person presets for object, landscape, and illustration use cases.
- Ensure idempotency and one valid default per category/scope.

## Phase 3 — Subject selection
- Add multi-select subject types to the Configurator.
- Persist the primary subject index.
- Preserve legacy `subject_type` reads and existing pose/slot behavior.
- Add server-side validation for supported combinations.

## Phase 4 — Scene filtering
- Add occasion state and filtering to BlockScene.
- Filter by category, occasion, and subject scope.
- Preserve generic presets as fallback.
- Reset incompatible presets and select a valid default after category or subject changes.
- Add explicit empty states.

## Phase 5 — Per-slot reference workflow
- Add instructions textarea to each photo slot.
- Add one optional reference upload per slot.
- Validate type, size, dimensions, ownership, and private storage.
- Persist and remove reference assets safely.

## Phase 6 — PromptEngine expansion
- Add OccasionModule.
- Add SubjectCompositionModule.
- Add PhotoInstructionsModule.
- Add ReferenceImageModule or attachment collector.
- Add NoPersonSceneModule.
- Preserve fragment priorities and the existing `{prompt, constraints}` output contract, exposing attachments through a compatible adapter if needed.

## Phase 7 — Generation integration
- Define deterministic ordering for references and subject images.
- Update Gemini serialization for multiple images.
- Keep providers that do not support references compatible through an adapter.
- Enforce aggregate payload limits and failure handling.

## Phase 8 — Tests and validation
- Unit-test all new modules and legacy fallback behavior.
- Livewire-test subject selection, occasion filtering, defaults, and empty states.
- Test reference upload persistence, ownership rejection, sanitization, and removal.
- Test seeder idempotency and preset compatibility.
- Test generation image ordering and provider compatibility.
- Run performance, Pint, static analysis, migrations, and full regression suite.

## Release gates

- No implementation starts until Phase 1 schema decisions are reviewed.
- Every phase requires focused tests before the next phase.
- Full suite and migration rollback must pass before release.

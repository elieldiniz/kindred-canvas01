# SPEC: creative-prompt-expansion

## Metadata
- Source: developer-approved expansion of creative-prompt-engine
- Service: kindrad-canvas
- Tier: planned
- Version: 1.0
- Depends on: `.spec/features/creative-prompt-engine/SPEC.md`

## Objective
Expand the Creative Prompt Engine without changing the existing feature's acceptance criteria. Add occasion-aware scenes, multiple subject types, non-person compositions, one optional reference image per photo slot, and per-photo instructions.

## Scope

### In
- Scene preset filtering by occasion and subject scope.
- Subject types: person, couple, family, pet, object, landscape, illustration, other.
- Multiple subject types per project with a primary subject index.
- One optional reference image and instructions per project photo slot.
- Secure upload validation and ownership checks.
- Prompt fragments for occasion, subject composition, reference usage, photo instructions, and non-person scenes.
- Gemini payload support for ordered subject and reference images.
- Seed data for special occasions and non-person scenes.
- Livewire configurator UI and regression tests.

### Out
- Admin UI for editing presets and occasion data.
- Visual final-prompt preview.
- New AI providers.
- Removal of legacy `subject_type` or `PromptAssembler` compatibility.

## Data model

### `scene_presets`
Add nullable indexed `occasion_slug` and `subject_scope`, where `subject_scope` is `any`, `with_person`, or `without_person`. Keep category ownership, unique `(category_id, slug)`, and exactly one default per category/scope set.

### `projects`
Add nullable JSON `subject_types` and nullable unsigned small integer `primary_subject_index`. Keep `subject_type` as a backward-compatible fallback. `subject_types` is preferred when non-empty.

### `project_photos`
Add nullable text `instructions` and nullable foreign key `reference_image_id` to `source_images`, nulling on delete. A reference belongs to the same project owner and is associated with one slot.

## Functional requirements

- RF-EXP-01: Changing subject types updates compatible scene presets without losing category selection.
- RF-EXP-02: BlockScene filters by selected category, occasion, and compatible subject scope.
- RF-EXP-03: Projects support multiple subject types and a primary subject index; legacy `subject_type` remains supported.
- RF-EXP-04: Projects without people can select object, landscape, or illustration scenes and do not require a person photo.
- RF-EXP-05: Each photo slot accepts at most one optional reference image.
- RF-EXP-06: Each photo slot accepts sanitized instructions limited to 280 characters.
- RF-EXP-07: Reference uploads accept JPEG, PNG, or WebP up to 10 MB, are private, and require ownership authorization.
- RF-EXP-08: PromptEngine adds occasion, subject composition, photo instructions, reference guidance, and non-person scene context when applicable.
- RF-EXP-09: Generation sends source and reference images in deterministic order, with reference images clearly represented as visual anchors.
- RF-EXP-10: Existing creative-prompt-engine tests and legacy subject_type behavior remain passing.
- RF-EXP-11: Seeders are idempotent and provide occasions including Christmas, New Year, Mother's Day, Valentine's Day, Easter, Halloween, graduation, anniversary, and baby shower.
- RF-EXP-12: Prompt assembly remains under 50ms excluding database and image encoding operations.

## Contracts

- `subject_types`: `list<string>` with supported values `person`, `couple`, `family`, `pet`, `object`, `landscape`, `illustration`, `other`.
- `subject_scope`: `any|with_person|without_person`.
- Reference image association is one-to-one from a project photo slot to a source image.
- Provider changes must be backward-compatible through an optional references argument or an adapter.

## Acceptance criteria

- Selecting an occasion displays only matching presets plus explicitly allowed generic presets.
- Selecting a subject type with no people displays compatible non-person scenes and does not require a person source photo.
- Selecting a category auto-selects its valid default preset; changing category resets the previous preset.
- A project can persist multiple subject types and a primary subject index while legacy projects continue to assemble prompts.
- A photo slot can persist one reference image and instructions; another user's image is rejected.
- Prompt output includes occasion and slot instructions when present, and includes non-person context when applicable.
- Generation tests prove deterministic ordering of source and reference images.
- Seeder tests prove idempotency, valid occasion slugs, non-empty fragments, compatible scopes, and one default per category/scope.
- Existing engine, generation, upload, and legacy compatibility tests remain green.

## Security and privacy

- Authorize project updates before modifying slots.
- Validate referenced source images against the authenticated user's ownership.
- Store uploads on the configured private generation disk.
- Escape instructions in rendered HTML and never log raw image data or secrets.
- Enforce file type, size, and image-dimension validation server-side.

# PLAN: creative-prompt-expansion

## Architecture

Build the expansion additively on top of the existing PromptEngine and Configurator. Preserve `subject_type`, existing scene selection, `GenerationProvider` compatibility, private source-image storage, and all creative-prompt-engine acceptance criteria.

## Phases

1. Confirm current schemas, component conventions, upload pipeline, provider contract, and existing tests.
2. Add reversible migrations for scene preset occasion/scope, project subject types/primary index, and project photo instructions/reference image.
3. Update models, casts, relationships, factories, policies, and authorization checks.
4. Add idempotent occasion and non-person scene seed data.
5. Implement subject-type selection and primary-subject state in Configurator.
6. Extend BlockScene with occasion and subject-scope filtering while preserving category defaults.
7. Extend BlockPhotos with per-slot instructions and one optional reference-image upload.
8. Add PromptEngine modules for occasion, subject composition, photo instructions, references, and non-person scenes.
9. Extend generation orchestration and Gemini payload handling through a backward-compatible adapter.
10. Add unit, Livewire, upload-security, seeder, job/provider, regression, and performance tests.
11. Run Pint, focused Pest tests, full Pest suite, static analysis, and migration rollback verification.

## Decisions

- Use one new expansion feature directory; do not rewrite the original SPEC.
- Support multiple subject types now, with legacy `subject_type` fallback.
- Allow one reference image per photo slot.
- Implement occasion as a filter inside BlockScene, not a separate configurator block.
- Keep reference-image metadata separate from prompt text; the provider receives the actual image attachment.
- Do not add admin editing or visual prompt preview.

## Risks and mitigations

- Provider signature break: use an adapter or optional parameter and update every implementation before switching callers.
- Gemini image ordering: define and test deterministic reference-first/source-second ordering.
- Legacy projects: read `subject_type` when `subject_types` is null or empty.
- Unauthorized references: validate owner and project association server-side.
- Large uploads: enforce per-file and total-payload limits.
- Duplicate defaults: seed with stable keys and test exactly one default per category/scope.

## Definition of done

- All SPEC acceptance criteria pass.
- Existing creative-prompt-engine and legacy PromptAssembler tests pass.
- Uploads and references are authorized and private.
- Seeder is idempotent.
- Pint and static analysis pass.
- Full test suite passes.

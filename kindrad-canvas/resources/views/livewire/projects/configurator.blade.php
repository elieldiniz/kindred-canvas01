<div class="flex flex-col gap-section p-margin-page" data-test="configurator-page">
    <header class="flex items-center justify-between gap-stack-md">
        <h1 class="font-display-lg text-display-lg text-on-surface">
            {{ __('Create new artwork') }}
        </h1>

        <div class="credits-badge" data-test="credits-badge">
            <span class="material-symbols-outlined text-[14px]" aria-hidden="true">bolt</span>
            <span>{{ $this->creditBalance }} {{ __('credits') }}</span>
        </div>
    </header>

    <div class="grid gap-section lg:grid-cols-[1fr_320px]">
        <div class="flex flex-col gap-section">
            <livewire:projects.configurator.block-product
                :product-id="$productId"
                :key="'block-product-'.$productId"
            />

            <livewire:projects.configurator.block-subject-type
                :subject-type="$subjectType"
                :key="'block-subject-type-'.$subjectType"
            />

            <livewire:projects.configurator.block-photos
                :project-id="$projectId"
                :subject-type="$subjectType"
                :key="'block-photos-'.$subjectType.'-'.($projectId ?? 'null')"
            />

            <livewire:projects.configurator.block-category
                :product-id="$productId"
                :category-id="$categoryId"
                :key="'block-category-'.$productId.'-'.$categoryId"
            />

            <livewire:projects.configurator.block-style
                :category-id="$categoryId"
                :style-id="$styleId"
                :key="'block-style-'.$categoryId.'-'.$styleId"
            />

            @if ($this->needsPose())
                <livewire:projects.configurator.block-pose
                    :pose-id="$poseId"
                    :key="'block-pose-'.$poseId"
                />
            @endif

            <livewire:projects.configurator.block-prompt
                :custom-prompt="$customPrompt"
                :key="'block-prompt'"
            />
        </div>

        <aside class="hidden lg:block" data-test="configurator-preview-aside">
            <div class="sticky top-margin-page glass-card flex flex-col gap-stack-md p-stack-lg">
                <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                    {{ __('Live preview') }}
                </p>
                <x-blocks.preview-row icon="inventory_2" label="Product" :value="\App\Models\Product::find($productId)?->name" />
                <x-blocks.preview-row icon="group" label="Subject" :value="$subjectType ? \Illuminate\Support\Str::headline($subjectType) : null" />
                <x-blocks.preview-row icon="photo_library" label="Photos" :value="$projectId ? \App\Models\Project::find($projectId)?->photos()->count().' / '.$this->slotCount() : null" />
                <x-blocks.preview-row icon="category" label="Category" :value="\App\Models\Category::find($categoryId)?->name" />
                <x-blocks.preview-row icon="palette" label="Style" :value="\App\Models\Style::find($styleId)?->name" />
                @if ($this->needsPose())
                    <x-blocks.preview-row icon="directions_walk" label="Pose" :value="\App\Models\Pose::find($poseId)?->name" />
                @endif
                <x-blocks.preview-row icon="edit_note" label="Custom prompt" :value="$customPrompt ? \Illuminate\Support\Str::limit($customPrompt, 40) : null" />
            </div>
        </aside>
    </div>

    <footer class="sticky bottom-0 -mx-margin-page border-t border-outline-variant bg-surface-container/95 px-margin-page py-stack-sm backdrop-blur-md" data-test="configurator-footer">
        <div class="mx-auto max-w-3xl">
            <button
                type="button"
                wire:click="generate"
                @disabled(! $this->canGenerate)
                data-test="configurator-generate-button"
                class="gradient-generate flex w-full items-center justify-center gap-stack-sm rounded-full py-3 font-label-md text-label-md font-bold text-on-primary shadow-lg shadow-primary/20"
            >
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">auto_awesome</span>
                <span>{{ __('Generate') }} (1 {{ __('credit') }})</span>
            </button>

            <p class="mt-1 text-center font-body-xs text-body-xs text-on-surface-variant" data-test="configurator-footer-message">
                @if (! $this->canGenerate)
                    {{ __('Complete all required blocks to enable Generate.') }}
                @else
                    {{ __('Ready to generate. Your credit will be debited.') }}
                @endif
            </p>
        </div>
    </footer>
</div>

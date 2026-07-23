<div class="flex flex-col gap-section p-margin-page" data-test="configurator-page">
    <header class="flex items-center justify-between gap-8 py-8 pb-2">
        <div>
            <h1 class="font-display-lg text-display-lg font-extrabold">
                <span class="bg-gradient-to-r from-white via-white to-white/60 bg-clip-text text-transparent">
                    {{ __('Create new') }}
                </span>
                <br>
                <span class="bg-gradient-to-r from-primary via-purple-400 to-cyan-400 bg-clip-text text-transparent">
                    {{ __('artwork') }}.
                </span>
            </h1>
        </div>

        <div class="shrink-0" data-test="credits-badge">
            <a
                href="{{ route('credits.index') }}"
                wire:navigate
                class="group flex items-center gap-3 rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-500/10 to-transparent px-5 py-4 shadow-[0_0_20px_rgba(52,211,153,0.1)] hover:shadow-[0_0_30px_rgba(52,211,153,0.2)] hover:border-emerald-500/50 transition-all duration-300"
            >
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400">
                    <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;" aria-hidden="true">bolt</span>
                </span>
                <div>
                    <p class="text-xl font-extrabold text-white leading-none">{{ $this->creditBalance }}</p>
                    <p class="text-xs text-white/50 mt-0.5 uppercase tracking-widest">{{ __('credits') }}</p>
                </div>
                <span class="ml-2 text-white/30 group-hover:text-white/60 transition-colors text-lg">→</span>
            </a>
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

            <livewire:projects.configurator.block-scene
                :category-id="$categoryId"
                :scene-preset-id="$scenePresetId"
                :key="'block-scene-'.$categoryId.'-'.$scenePresetId"
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
                <x-blocks.preview-row icon="landscape" label="Scene" :value="$scenePresetId ? \App\Models\ScenePreset::find($scenePresetId)?->name : null" />
                @if ($this->needsPose())
                    <x-blocks.preview-row icon="directions_walk" label="Pose" :value="\App\Models\Pose::find($poseId)?->name" />
                @endif
                <x-blocks.preview-row icon="edit_note" label="Custom prompt" :value="$customPrompt ? \Illuminate\Support\Str::limit($customPrompt, 40) : null" />
            </div>
        </aside>
    </div>

    <footer class="sticky bottom-0 -mx-margin-page border-t border-white/5 bg-[#060f1d]/95 px-8 py-4 backdrop-blur-md" data-test="configurator-footer">
        <div class="mx-auto max-w-3xl">
            <button
                type="button"
                wire:click="generate"
                @disabled(! $this->canGenerate)
                data-test="configurator-generate-button"
                class="gradient-generate flex w-full items-center justify-center gap-stack-sm rounded-full px-8 py-4 font-label-md text-label-md font-bold text-on-primary shadow-lg shadow-primary/20 whitespace-nowrap"
            >
                <span class="material-symbols-outlined text-[18px]" aria-hidden="true">auto_awesome</span>
                <span class="whitespace-nowrap">{{ __('Generate') }} (1 {{ __('credit') }})</span>
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

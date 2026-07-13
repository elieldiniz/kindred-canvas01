<x-layouts::wizard :title="__('New Project')">
    <x-wizard.progress-bar :step="$step" :total="7" :section-name="$this->sectionName()" />

    <section class="mt-stack-lg flex flex-col items-center text-center">
        <h1 class="font-display-lg text-display-lg font-bold tracking-tight text-on-surface">
            @switch($step)
                @case(1) {{ __('Who is the star of this canvas?') }} @break
                @case(2) {{ __('Pick a category') }} @break
                @case(3) {{ __('Pick a style') }} @break
                @case(4) {{ __('Pick a layout') }} @break
                @case(5) {{ __('Add a source photo (optional)') }} @break
                @case(6) {{ __('Tell us about it') }} @break
                @case(7) {{ __('Review and generate') }} @break
                @default {{ __('New Project') }}
            @endswitch
        </h1>

        @if ($step === 1)
            <div class="mt-stack-md w-full">
                @error('modeId')
                    <p class="font-label-md text-label-md text-error" data-test="wizard-mode-error">{{ $message }}</p>
                @enderror

                <livewire:projects.wizard.steps.mode
                    :project-id="$projectId"
                    :mode-id="$modeId"
                    :key="'wizard-mode-'.$projectId"
                />
            </div>
        @elseif ($step === 2)
            <div class="mt-stack-md w-full">
                @error('categoryId')
                    <p class="font-label-md text-label-md text-error" data-test="wizard-category-error">{{ $message }}</p>
                @enderror

                <livewire:projects.wizard.steps.category
                    :project-id="$projectId"
                    :category-id="$categoryId"
                    :key="'wizard-category-'.$projectId"
                />
            </div>
        @elseif ($step === 3 && $categoryId !== null)
            <div class="mt-stack-md w-full">
                <livewire:projects.wizard.steps.style
                    :project-id="$projectId"
                    :category-id="$categoryId"
                    :style-id="$styleId"
                    :key="'wizard-style-'.$projectId"
                />
            </div>
        @elseif ($step === 4 && $styleId !== null)
            <div class="mt-stack-md w-full">
                <livewire:projects.wizard.steps.layout
                    :project-id="$projectId"
                    :style-id="$styleId"
                    :layout-id="$layoutId"
                    :key="'wizard-layout-'.$projectId"
                />
            </div>
        @elseif ($step === 5 && $layoutId !== null)
            <div class="mt-stack-md w-full">
                <livewire:projects.wizard.steps.source-image
                    :project-id="$projectId"
                    :source-image-id="$sourceImageId"
                    :key="'wizard-source-image-'.$projectId"
                />
            </div>
        @elseif ($step === 6 && $layoutId !== null)
            <div class="mt-stack-md w-full">
                <livewire:projects.wizard.steps.inputs
                    :project-id="$projectId"
                    :inputs="$inputs"
                    :key="'wizard-inputs-'.$projectId"
                />
            </div>
        @elseif ($step === 7 && $layoutId !== null)
            <div class="mt-stack-md w-full">
                <livewire:projects.wizard.steps.review
                    :project-id="$projectId"
                    :mode-id="$modeId"
                    :category-id="$categoryId"
                    :style-id="$styleId"
                    :layout-id="$layoutId"
                    :source-image-id="$sourceImageId"
                    :inputs="$inputs"
                    :key="'wizard-review-'.$projectId"
                />
            </div>
        @endif
    </section>

    <x-slot:back>
        @if ($step > 1)
            <button
                type="button"
                wire:click="back"
                data-test="wizard-back-button"
                class="inline-flex items-center gap-2 rounded-lg px-3 py-2 font-label-md text-label-md text-on-surface-variant transition-colors hover:bg-surface-container-highest hover:text-on-surface"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>{{ __('Back') }}</span>
            </button>
        @else
            <button
                type="button"
                disabled
                data-test="wizard-back-button"
                class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg px-3 py-2 font-label-md text-label-md text-on-surface-variant opacity-50"
            >
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                <span>{{ __('Back') }}</span>
            </button>
        @endif
    </x-slot:back>

    <x-slot:current>
        @php($labels = ['mode' => $modeId, 'category' => $categoryId, 'style' => $styleId, 'layout' => $layoutId])
        @php($first = collect($labels)->filter()->keys()->first())
        @if ($first)
            <span class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">{{ __(ucfirst($first)) }}</span>
            <span class="ml-2 font-label-md text-label-md text-primary">#{{ $labels[$first] }}</span>
        @else
            <span class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">{{ __('No selection yet') }}</span>
        @endif
    </x-slot:current>

    <x-slot:continue>
        @if ($step === 7)
            <button
                type="button"
                disabled
                data-test="wizard-continue-button"
                class="inline-flex cursor-not-allowed items-center gap-2 rounded-full px-stack-lg py-3 font-label-md text-label-md text-on-surface opacity-50"
            >
                <span>{{ __('Generate') }}</span>
            </button>
        @else
            <div class="flex items-center gap-stack-sm">
                @if ($step === 5)
                    <button
                        type="button"
                        wire:click="next"
                        wire:loading.attr="disabled"
                        data-test="wizard-skip-button"
                        class="inline-flex items-center gap-2 rounded-full border border-outline-variant px-stack-lg py-3 font-label-md text-label-md text-on-surface-variant transition-colors hover:bg-surface-container-high hover:text-on-surface"
                    >
                        <span class="material-symbols-outlined text-[18px]">skip_next</span>
                        <span>{{ __('Skip image') }}</span>
                    </button>
                @endif

                <button
                    type="button"
                    wire:click="next"
                    wire:loading.attr="disabled"
                    @disabled($step === 1 && ! $modeId)
                    data-test="wizard-continue-button"
                    class="inline-flex items-center gap-2 rounded-full bg-primary px-stack-lg py-3 font-label-md text-label-md font-bold text-on-primary shadow-sm shadow-primary/30 transition-all duration-150 hover:scale-105 hover:shadow-[0_0_20px_rgba(192,193,255,0.4)] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100"
                >
                    <span>{{ $step === 5 ? __('Continue with image') : __('Continue') }}</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>
            </div>
        @endif
    </x-slot:continue>

    <x-slot:modals>
        <flux:modal name="exit-confirm" class="max-w-md" data-test="wizard-exit-modal">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-[28px] text-error" style="font-variation-settings: 'FILL' 1;">logout</span>
                    <flux:heading size="lg">{{ __('Exit wizard?') }}</flux:heading>
                </div>

                <flux:subheading>
                    {{ __('Your draft will be saved.') }}
                </flux:subheading>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled" data-test="wizard-exit-cancel">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button
                    variant="primary"
                    wire:click="exit"
                    data-test="wizard-exit-confirm"
                >
                    {{ __('Exit') }}
                </flux:button>
            </div>
        </flux:modal>
    </x-slot:modals>
</x-layouts::wizard>

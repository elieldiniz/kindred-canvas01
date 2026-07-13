@php
    $sections = [
        [
            'key' => 'mode',
            'label' => __('Mode'),
            'step' => 1,
            'value' => $this->modeLabel(),
            'test' => 'wizard-review-mode',
        ],
        [
            'key' => 'category',
            'label' => __('Category'),
            'step' => 2,
            'value' => $this->categoryLabel(),
            'test' => 'wizard-review-category',
        ],
        [
            'key' => 'style',
            'label' => __('Style'),
            'step' => 3,
            'value' => $this->styleLabel(),
            'test' => 'wizard-review-style',
        ],
        [
            'key' => 'layout',
            'label' => __('Layout'),
            'step' => 4,
            'value' => $this->layoutLabel(),
            'test' => 'wizard-review-layout',
        ],
        [
            'key' => 'source-image',
            'label' => __('Source image'),
            'step' => 5,
            'value' => null,
            'test' => 'wizard-review-source-image',
        ],
        [
            'key' => 'inputs',
            'label' => __('Personalization'),
            'step' => 6,
            'value' => null,
            'test' => 'wizard-review-inputs',
        ],
    ];

    $sourceImageUrl = $this->sourceImageUrl();
    $sourceImageFilename = $this->sourceImageFilename();
    $creditBalance = $this->creditBalance();
    $hasCredits = $creditBalance > 0;
@endphp

<div class="mx-auto flex w-full max-w-3xl flex-col gap-stack-md" data-test="wizard-review-step">
    @foreach ($sections as $section)
        <div
            class="glass-card flex items-center justify-between gap-stack-md rounded-xl p-stack-md"
            data-test="{{ $section['test'] }}"
        >
            <div class="flex min-w-0 flex-col text-left">
                <span class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                    {{ $section['label'] }}
                </span>

                @if ($section['key'] === 'source-image')
                    @if ($sourceImageUrl !== null)
                        <div class="mt-1 flex items-center gap-stack-sm">
                            <img
                                src="{{ $sourceImageUrl }}"
                                alt="{{ $sourceImageFilename ?? '' }}"
                                class="h-12 w-12 rounded-md object-cover ring-1 ring-white/10"
                                data-test="wizard-review-source-image-thumb"
                            />
                            <span class="truncate font-label-md text-label-md text-on-surface" data-test="wizard-review-source-image-name">
                                {{ $sourceImageFilename ?? __('Uploaded image') }}
                            </span>
                        </div>
                    @else
                        <span class="mt-1 font-label-md text-label-md italic text-on-surface-variant" data-test="wizard-review-source-image-skipped">
                            {{ __('Skipped (no image)') }}
                        </span>
                    @endif
                @elseif ($section['key'] === 'inputs')
                    <dl class="mt-2 grid grid-cols-1 gap-x-stack-md gap-y-1 sm:grid-cols-2" data-test="wizard-review-inputs-list">
                        <div class="flex flex-col">
                            <dt class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">{{ __('Name') }}</dt>
                            <dd class="truncate font-label-md text-label-md text-on-surface" data-test="wizard-review-input-name">
                                {{ $inputs['name'] ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex flex-col">
                            <dt class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">{{ __('Phrase') }}</dt>
                            <dd class="truncate font-label-md text-label-md text-on-surface" data-test="wizard-review-input-phrase">
                                {{ $inputs['phrase'] ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex flex-col">
                            <dt class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">{{ __('Theme') }}</dt>
                            <dd class="truncate font-label-md text-label-md text-on-surface" data-test="wizard-review-input-theme">
                                {{ $inputs['theme'] ?? '—' }}
                            </dd>
                        </div>
                        <div class="flex flex-col">
                            <dt class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">{{ __('Dedicatoria') }}</dt>
                            <dd class="truncate font-label-md text-label-md text-on-surface" data-test="wizard-review-input-dedicatoria">
                                {{ $inputs['dedicatoria'] ?? '—' }}
                            </dd>
                        </div>
                    </dl>
                @else
                    <span class="mt-1 font-label-md text-label-md text-on-surface" data-test="wizard-review-{{ $section['key'] }}-value">
                        {{ $section['value'] ?? __('Not selected') }}
                    </span>
                @endif
            </div>

            <button
                type="button"
                wire:click="$dispatch('go-to-step', { step: {{ (int) $section['step'] }} })"
                data-test="wizard-review-edit-{{ $section['key'] }}"
                class="inline-flex shrink-0 items-center gap-1 rounded-lg px-3 py-2 font-label-md text-label-md text-on-surface-variant transition-colors hover:bg-surface-container-highest hover:text-on-surface"
            >
                <span class="material-symbols-outlined text-[18px]">edit</span>
                <span>{{ __('Edit') }}</span>
            </button>
        </div>
    @endforeach

    <div class="mt-stack-md flex justify-end" data-test="wizard-review-generate-row">
        <button
            type="button"
            @disabled(! $hasCredits)
            wire:click="$dispatch('submit-wizard')"
            data-test="wizard-review-generate"
            @if (! $hasCredits) title="{{ __("You're out of credits") }}" aria-describedby="wizard-review-generate-tooltip" @endif
            class="inline-flex items-center gap-2 rounded-full bg-primary px-stack-lg py-3 font-label-md text-label-md font-bold text-on-primary shadow-sm shadow-primary/30 transition-all duration-150 hover:scale-105 hover:shadow-[0_0_20px_rgba(192,193,255,0.4)] disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100"
        >
            <span class="material-symbols-outlined text-[18px]" style="font-variation-settings: 'FILL' 1;">auto_awesome</span>
            <span>{{ __('Generate') }}</span>
        </button>

        @if (! $hasCredits)
            <span id="wizard-review-generate-tooltip" class="sr-only">{{ __("You're out of credits") }}</span>
        @endif
    </div>
</div>

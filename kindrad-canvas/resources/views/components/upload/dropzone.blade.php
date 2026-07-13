@props([
    'wireModel' => 'photo',
    'accept' => 'image/jpeg,image/png,image/webp',
    'maxSizeMb' => 10,
    'previewUrl' => null,
])

@php($hasPreview = ! empty($previewUrl))

@if ($hasPreview)
    <div
        data-test="wizard-source-dropzone"
        data-has-preview="1"
        class="mx-auto flex w-full max-w-xl flex-col items-center gap-stack-md rounded-2xl bg-primary/5 p-stack-md"
    >
        <img
            src="{{ $previewUrl }}"
            alt="{{ __('Source photo preview') }}"
            class="aspect-square w-full max-w-xs rounded-2xl border border-outline-variant object-cover shadow-md"
        />

        <div class="flex flex-wrap items-center justify-center gap-stack-sm">
            <label
                tabindex="0"
                wire:keydown.enter.prevent
                data-test="wizard-source-replace"
                class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-outline-variant px-stack-lg py-2 font-label-md text-label-md text-on-surface transition-colors hover:bg-surface-container-high focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
            >
                <input
                    type="file"
                    wire:model="{{ $wireModel }}"
                    accept="{{ $accept }}"
                    data-test="wizard-source-replace-input"
                    class="sr-only"
                />
                <span class="material-symbols-outlined text-[18px]">swap_horiz</span>
                <span>{{ __('Replace') }}</span>
            </label>

            <button
                type="button"
                wire:click="remove"
                data-test="wizard-source-remove-button"
                class="inline-flex items-center gap-2 rounded-full border border-error/40 px-stack-lg py-2 font-label-md text-label-md text-error transition-colors hover:bg-error/10"
            >
                <span class="material-symbols-outlined text-[18px]">delete</span>
                <span>{{ __('Remove') }}</span>
            </button>
        </div>
    </div>
@else
    <label
        tabindex="0"
        wire:keydown.enter.prevent
        data-test="wizard-source-dropzone"
        data-has-preview="0"
        class="group relative mx-auto flex w-full max-w-xl cursor-pointer flex-col items-center justify-center gap-stack-md rounded-2xl border-2 border-dashed border-primary/20 bg-primary/5 p-stack-lg text-center transition-colors focus:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40 hover:fill-primary/10"
    >
        <input
            type="file"
            wire:model="{{ $wireModel }}"
            accept="{{ $accept }}"
            data-test="wizard-source-input"
            class="sr-only"
        />

        <span class="material-symbols-outlined text-[32px] text-primary" style="font-variation-settings: 'FILL' 0, 'wght' 400;">cloud_upload</span>

        <h3 class="font-headline-md text-headline-md text-on-surface">{{ __('Drag your photo here') }}</h3>

        <p class="font-label-md text-label-md text-on-surface-variant">
            {{ __('JPEG / PNG / WEBP up to :max MB', ['max' => $maxSizeMb]) }}
        </p>

        <span class="mt-2 inline-flex items-center gap-2 rounded-full bg-primary px-stack-lg py-2 font-label-md text-label-md font-bold text-on-primary shadow-sm shadow-primary/30 transition-transform group-hover:scale-105">
            <span class="material-symbols-outlined text-[18px]">add_photo_alternate</span>
            <span>{{ __('Choose a file') }}</span>
        </span>
    </label>
@endif

@error($wireModel)
    <p
        data-test="wizard-source-error"
        class="mt-stack-sm font-label-md text-label-md text-error"
        role="alert"
    >{{ $message }}</p>
@enderror

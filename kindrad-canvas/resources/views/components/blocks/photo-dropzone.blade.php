@props([
    'wireUpload' => null,
    'wireModel' => null,
    'wireRemove' => 'remove',
    'slotIndex' => 0,
    'slotCount' => 1,
    'preview' => null,
    'error' => null,
    'label' => null,
])

@php($wireBind = $wireModel ?: ($wireUpload ?: 'photo'))
@php($slotLabel = $label ?? __('Photo :n', ['n' => $slotIndex + 1]))
@php($showRemove = $slotCount > 1 || $preview !== null)

<div
    class="flex flex-col gap-stack-sm"
    data-test="photo-dropzone-slot-{{ $slotIndex }}"
>
    <div class="flex items-center justify-between">
        <p class="font-label-md text-label-md text-on-surface">
            {{ $slotLabel }}
        </p>
        @if ($preview && $showRemove)
            <button
                type="button"
                wire:click="{{ $wireRemove === 'remove' ? 'removePhoto('.$slotIndex.')' : $wireRemove }}"
                class="inline-flex items-center gap-stack-sm rounded-full border border-error/40 px-stack-md py-1 font-label-md text-label-md text-error transition-colors hover:bg-error/10"
                data-test="photo-remove-slot-{{ $slotIndex }}"
            >
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">delete</span>
                {{ __('Remove') }}
            </button>
        @endif
    </div>

    @if ($error)
        <div
            class="flex items-start gap-stack-sm rounded-lg border border-error/40 bg-error-container/20 p-stack-md"
            data-test="photo-error-slot-{{ $slotIndex }}"
            role="alert"
        >
            <span class="material-symbols-outlined text-[20px] text-error" aria-hidden="true">error</span>
            <p class="font-label-md text-label-md text-error">{{ $error }}</p>
        </div>
    @endif

    @if ($preview)
        <div
            class="relative max-h-[240px] overflow-hidden rounded-xl border border-outline-variant"
            data-test="photo-preview-slot-{{ $slotIndex }}"
        >
            <img
                src="{{ $preview }}"
                alt="{{ $slotLabel }}"
                class="w-full object-cover"
            />
        </div>
    @else
        <label
            tabindex="0"
            class="group flex cursor-pointer flex-col items-center justify-center gap-stack-sm rounded-xl border-2 border-dashed border-primary/20 bg-primary/5 p-stack-lg text-center transition-colors hover:border-primary hover:bg-primary/10 focus:border-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
            data-test="photo-dropzone-{{ $slotIndex }}"
            data-has-preview="0"
        >
            <span
                class="material-symbols-outlined text-[40px] text-primary transition-transform group-hover:scale-110"
                style="font-variation-settings: 'FILL' 0, 'wght' 400;"
                aria-hidden="true"
            >cloud_upload</span>

            <p class="font-headline-md text-headline-md text-on-surface">
                {{ __('Drag your photo here') }}
            </p>
            <p class="font-body-md text-body-md text-on-surface-variant">
                {{ __('JPEG / PNG / WEBP up to 10 MB') }}
            </p>

            <input
                type="file"
                wire:model="{{ $wireBind }}"
                accept="image/jpeg,image/png,image/webp"
                class="sr-only"
                data-test="photo-input-slot-{{ $slotIndex }}"
            />
        </label>
    @endif
</div>
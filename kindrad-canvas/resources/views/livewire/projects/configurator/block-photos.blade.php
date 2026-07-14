<x-blocks.card
    icon="photo_library"
    title="Photos"
    :helper="$slotCount > 1 ? __('Upload two photos — one per person.') : __('Upload one photo of the subject.')"
>
    @if (! $subjectType)
        <p class="font-body-md text-body-md text-on-surface-variant" data-test="block-photos-empty">
            {{ __('Select a subject type first to see photo slots.') }}
        </p>
    @else
        <div class="grid gap-stack-md {{ $slotCount > 1 ? 'sm:grid-cols-2' : '' }}" data-test="block-photos-grid">
            <x-blocks.photo-dropzone
                wire-upload="photo0"
                wire-remove="removePhoto(0)"
                :slot-index="0"
                :slot-count="$slotCount"
                :preview="$photo0"
                data-test="block-photos-slot-0"
            />

            @if ($slotCount > 1)
                <x-blocks.photo-dropzone
                    wire-upload="photo1"
                    wire-remove="removePhoto(1)"
                    :slot-index="1"
                    :slot-count="$slotCount"
                    :preview="$photo1"
                    data-test="block-photos-slot-1"
                />
            @endif
        </div>
    @endif
</x-blocks.card>
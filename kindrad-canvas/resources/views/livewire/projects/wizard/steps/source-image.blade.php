<div data-test="wizard-source-image-step">
    @php($preview = $this->previewUrl())

    <div class="flex flex-col items-center">
        <x-upload.dropzone
            wire-model="photo"
            :preview-url="$preview"
        />

        @if ($sourceImageId !== null && $preview === null)
            <p class="mt-stack-sm font-label-md text-label-md text-error" data-test="wizard-source-image-missing">
                {{ __('The previous image is no longer available. Re-upload to continue.') }}
            </p>
        @endif
    </div>
</div>

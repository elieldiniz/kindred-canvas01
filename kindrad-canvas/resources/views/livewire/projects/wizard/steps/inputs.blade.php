<div class="mx-auto flex w-full max-w-xl flex-col gap-stack-lg text-left" data-test="wizard-inputs-step">
    @error('inputs.name')
        <p class="font-label-md text-label-md text-error" data-test="wizard-inputs-name-error">{{ $message }}</p>
    @enderror

    <div>
        <flux:input
            wire:model.live="name"
            :label="__('Name')"
            type="text"
            required
            maxlength="80"
            autocomplete="off"
            data-test="wizard-input-name"
        />

        <div class="mt-1 flex justify-end">
            <span class="font-mono-sm text-mono-sm text-on-surface-variant" data-test="wizard-input-name-counter">
                {{ mb_strlen($name) }}/80
            </span>
        </div>

        @error('inputs.name')
            <flux:error name="inputs.name" />
        @enderror
    </div>

    <div>
        <flux:input
            wire:model.live="phrase"
            :label="__('Phrase')"
            type="text"
            maxlength="240"
            autocomplete="off"
            data-test="wizard-input-phrase"
        />

        <div class="mt-1 flex justify-end">
            <span class="font-mono-sm text-mono-sm text-on-surface-variant" data-test="wizard-input-phrase-counter">
                {{ mb_strlen($phrase) }}/240
            </span>
        </div>

        @error('inputs.phrase')
            <flux:error name="inputs.phrase" />
        @enderror
    </div>

    <div>
        <flux:input
            wire:model.live="theme"
            :label="__('Theme')"
            type="text"
            maxlength="120"
            autocomplete="off"
            data-test="wizard-input-theme"
        />

        <div class="mt-1 flex justify-end">
            <span class="font-mono-sm text-mono-sm text-on-surface-variant" data-test="wizard-input-theme-counter">
                {{ mb_strlen($theme) }}/120
            </span>
        </div>

        @error('inputs.theme')
            <flux:error name="inputs.theme" />
        @enderror
    </div>

    <div>
        <flux:textarea
            wire:model.live="dedicatoria"
            :label="__('Dedicatoria')"
            rows="4"
            maxlength="500"
            data-test="wizard-input-dedicatoria"
        />

        <div class="mt-1 flex justify-end">
            <span class="font-mono-sm text-mono-sm text-on-surface-variant" data-test="wizard-input-dedicatoria-counter">
                {{ mb_strlen($dedicatoria) }}/500
            </span>
        </div>

        @error('inputs.dedicatoria')
            <flux:error name="inputs.dedicatoria" />
        @enderror
    </div>
</div>
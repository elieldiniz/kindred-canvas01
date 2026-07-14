<x-blocks.card
    icon="edit_note"
    title="Custom prompt"
    helper="Describe anything extra — colors, mood, objects, scene."
>
    <div class="flex flex-col gap-stack-sm">
        <flux:textarea
            wire:model.live="customPrompt"
            :placeholder="__('e.g. Sunset background, blue clothing, a small dog in the corner...')"
            rows="4"
            maxlength="500"
            data-test="block-prompt-textarea"
        />
        <div class="flex justify-end">
            <span class="font-mono-sm text-mono-sm text-on-surface-variant" data-test="block-prompt-counter">
                {{ mb_strlen($customPrompt) }}/500
            </span>
        </div>
    </div>
</x-blocks.card>
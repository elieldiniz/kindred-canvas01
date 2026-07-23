<x-blocks.card
    icon="landscape"
    title="Scene"
    helper="Pick a scene preset for the background."
>
    @if ($categoryId === null)
        <p class="font-body-md text-body-md text-on-surface-variant" data-test="block-scene-empty">
            {{ __('Select a category first to see scene presets.') }}
        </p>
    @elseif ($presets->isEmpty())
        <p class="font-body-md text-body-md text-on-surface-variant" data-test="block-scene-empty">
            {{ __('No scenes available for this category.') }}
        </p>
    @else
        <div class="flex flex-wrap gap-2" data-test="block-scene-grid">
            @foreach ($presets as $preset)
                <x-blocks.selection-card
                    wire-click="$dispatch('scene-selected', { scenePresetId: {{ $preset->id }} })"
                    icon="landscape"
                    :name="$preset->name"
                    :description="\Illuminate\Support\Str::limit((string) $preset->prompt_fragment, 60)"
                    :selected="$preset->id === $scenePresetId"
                    compact
                    :test-id="'scene-preset-card-'.$preset->slug"
                />
            @endforeach
        </div>
    @endif
</x-blocks.card>

<x-blocks.card
    icon="palette"
    title="Art style"
    helper="Pick the visual style for the artwork."
>
    @if ($styles->isEmpty())
        <p class="font-body-md text-body-md text-on-surface-variant" data-test="block-style-empty">
            {{ __('Select a category first to see styles.') }}
        </p>
    @else
        <div class="flex flex-wrap gap-2" data-test="block-style-grid">
            @foreach ($styles as $style)
                <x-blocks.selection-card
                    wire-click="$dispatch('style-selected', { styleId: {{ $style->id }} })"
                    :icon="match ($style->slug) { 'watercolor' => 'water_drop', 'cartoon' => 'auto_awesome', 'realistic' => 'photo_camera', 'pixel_art' => 'grid_on', 'minimalist_line' => 'draw', default => 'palette' }"
                    :name="$style->name"
                    :description="\Illuminate\Support\Str::limit((string) $style->prompt_fragment, 60)"
                    :selected="$style->id === $styleId"
                    compact
                    :test-id="'style-card-'.$style->slug"
                />
            @endforeach
        </div>
    @endif
</x-blocks.card>
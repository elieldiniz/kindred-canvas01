<x-blocks.card
    icon="category"
    title="Category"
    helper="What is the occasion?"
>
    @if ($categories->isEmpty())
        <p class="font-body-md text-body-md text-on-surface-variant" data-test="block-category-empty">
            {{ __('Select a product first to see categories.') }}
        </p>
    @else
        <div class="flex flex-wrap gap-2" data-test="block-category-grid">
            @foreach ($categories as $category)
                <x-blocks.selection-card
                    wire-click="$dispatch('category-selected', { categoryId: {{ $category->id }} })"
                    :icon="match ($category->slug) { 'birthday' => 'cake', 'wedding' => 'favorite', 'pets' => 'pets', 'family' => 'family_restroom', 'couples' => 'people', 'kids' => 'child_care', default => 'category' }"
                    :name="$category->name"
                    :description="$category->description"
                    :selected="$category->id === $categoryId"
                    compact
                    :test-id="'category-card-'.$category->slug"
                />
            @endforeach
        </div>
    @endif
</x-blocks-card>
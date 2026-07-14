<x-blocks.card
    icon="inventory_2"
    title="Product"
    helper="What are we creating?"
>
    <div class="grid gap-stack-sm sm:grid-cols-2" data-test="block-product-grid">
        @foreach ($products as $product)
            <x-blocks.selection-card
                wire-click="$dispatch('product-selected', { productId: {{ $product->id }} })"
                :icon="match ($product->slug) { 'mug' => 'coffee', 'free_art' => 'image', default => 'package_2' }"
                :name="$product->name"
                :description="$product->slug === 'mug' ? __('Panoramic ready for print') : __('Free format, any proportion')"
                :selected="$product->id === $productId"
                :test-id="'product-card-'.$product->slug"
                compact
            />
        @endforeach
    </div>
</x-blocks.card>

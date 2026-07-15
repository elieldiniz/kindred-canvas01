<div class="flex flex-col gap-section" data-test="admin-category-create">

    <header class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                {{ __('New category') }}
            </h1>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface-variant">
                {{ __('Create a category and assign styles to it') }}
            </p>
        </div>

        <flux:button
            icon="arrow-left"
            variant="ghost"
            :href="route('admin.categories.index')"
            wire:navigate
        >
            {{ __('Back') }}
        </flux:button>
    </header>

    <form wire:submit="save" class="glass-card p-stack-lg space-y-stack-md">
        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:select wire:model="product_id" :label="__('Product')" required data-test="admin-category-product">
                <option value="">{{ __('Select product') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="status_id" :label="__('Status')" required data-test="admin-category-status">
                <option value="">{{ __('Select status') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:input
                wire:model.live="name"
                :label="__('Name')"
                required
                data-test="admin-category-name"
            />

            <flux:input
                wire:model="slug"
                :label="__('Slug')"
                required
                data-test="admin-category-slug"
            />
        </div>

        <flux:textarea
            wire:model="description"
            :label="__('Description')"
            rows="3"
            data-test="admin-category-description"
        />

        <flux:input
            wire:model="sort_order"
            :label="__('Sort order')"
            type="number"
            required
            data-test="admin-category-sort"
        />

        <div class="space-y-stack-sm">
            <flux:label>{{ __('Styles') }}</flux:label>
            <div class="grid gap-stack-sm md:grid-cols-3" data-test="admin-category-styles">
                @foreach ($styles as $style)
                    <label class="flex items-center gap-stack-sm cursor-pointer rounded-md border border-outline-variant p-stack-sm hover:bg-surface-container">
                        <input
                            type="checkbox"
                            value="{{ $style->id }}"
                            wire:model="selectedStyles"
                            class="rounded border-outline-variant bg-surface text-primary focus:ring-primary"
                        />
                        <span class="font-label-md text-label-md text-on-surface">{{ $style->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <flux:label>{{ __('Thumbnail') }}</flux:label>
            <input
                type="file"
                wire:model="thumbnail"
                accept="image/jpeg,image/png,image/webp"
                class="mt-1 block w-full text-sm text-on-surface-variant file:mr-4 file:rounded-md file:border-0 file:bg-primary/20 file:px-4 file:py-2 file:font-label-sm file:text-label-sm file:text-primary hover:file:bg-primary/30"
                data-test="admin-category-thumbnail"
            />
            <p class="mt-1 font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Optional. JPEG, PNG, or WebP up to 2MB.') }}</p>
            @error('thumbnail')
                <p class="mt-1 font-mono-xs text-mono-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-stack-sm pt-stack-md">
            <flux:button
                variant="ghost"
                :href="route('admin.categories.index')"
                wire:navigate
            >
                {{ __('Cancel') }}
            </flux:button>

            <flux:button
                type="submit"
                variant="primary"
                data-test="admin-category-save"
            >
                {{ __('Create category') }}
            </flux:button>
        </div>
    </form>
</div>
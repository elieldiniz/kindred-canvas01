<div class="flex flex-col gap-section" data-test="admin-product-edit">

    <header class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                {{ __('Edit product') }}
            </h1>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface-variant">
                {{ __('Update product details and print specifications') }}
            </p>
        </div>

        <flux:button
            icon="arrow-left"
            variant="ghost"
            :href="route('admin.products.index')"
            wire:navigate
        >
            {{ __('Back') }}
        </flux:button>
    </header>

    <form wire:submit="save" class="glass-card p-stack-lg space-y-stack-md">
        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:input
                wire:model="name"
                :label="__('Name')"
                required
                data-test="admin-product-name"
            />

            <flux:input
                wire:model="slug"
                :label="__('Slug')"
                required
                data-test="admin-product-slug"
            />
        </div>

        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:select wire:model="status_id" :label="__('Status')" required data-test="admin-product-status">
                <option value="">{{ __('Select status') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}" @selected($status->id === $status_id)>
                        {{ $status->name }}
                    </option>
                @endforeach
            </flux:select>

            <flux:select wire:model="color_mode_id" :label="__('Color mode')" required data-test="admin-product-color-mode">
                <option value="">{{ __('Select color mode') }}</option>
                @foreach ($colorModes as $mode)
                    <option value="{{ $mode->id }}" @selected($mode->id === $color_mode_id)>
                        {{ $mode->name }}
                    </option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:input
                wire:model="print_width_mm"
                :label="__('Print width (mm)')"
                type="number"
                step="0.01"
                min="1"
                required
                data-test="admin-product-width"
            />

            <flux:input
                wire:model="print_height_mm"
                :label="__('Print height (mm)')"
                type="number"
                step="0.01"
                min="1"
                required
                data-test="admin-product-height"
            />
        </div>

        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:input
                wire:model="min_dpi"
                :label="__('Minimum DPI')"
                type="number"
                min="72"
                required
                data-test="admin-product-dpi"
            />

            <flux:input
                wire:model="safe_area_mm"
                :label="__('Safe area (mm)')"
                type="number"
                step="0.01"
                min="0"
                required
                data-test="admin-product-safe-area"
            />
        </div>

        <div class="flex justify-end gap-stack-sm pt-stack-md">
            <flux:button
                variant="ghost"
                :href="route('admin.products.index')"
                wire:navigate
            >
                {{ __('Cancel') }}
            </flux:button>

            <flux:button
                type="submit"
                variant="primary"
                data-test="admin-product-save"
            >
                {{ __('Save changes') }}
            </flux:button>
        </div>
    </form>
</div>
<div class="flex flex-col gap-section" data-test="admin-layout-create">

    <header class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                {{ __('New layout') }}
            </h1>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface-variant">
                {{ __('Create a new print layout for the catalog') }}
            </p>
        </div>

        <flux:button
            icon="arrow-left"
            variant="ghost"
            :href="route('admin.layouts.index')"
            wire:navigate
        >
            {{ __('Back') }}
        </flux:button>
    </header>

    <form wire:submit="save" class="glass-card p-stack-lg space-y-stack-md">
        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:input
                wire:model.live="name"
                :label="__('Name')"
                required
                data-test="admin-layout-name"
            />

            <flux:input
                wire:model="slug"
                :label="__('Slug')"
                required
                data-test="admin-layout-slug"
            />
        </div>

        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:select wire:model="status_id" :label="__('Status')" required data-test="admin-layout-status">
                <option value="">{{ __('Select status') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model="proportion_ratio"
                :label="__('Proportion ratio')"
                placeholder="1:1"
                required
                data-test="admin-layout-ratio"
            />
        </div>

        <div>
            <label class="block font-label-md text-label-md text-on-surface mb-stack-sm">{{ __('Safe area overlay (JSON)') }}</label>
            <textarea
                wire:model="safe_area_overlay"
                rows="5"
                class="w-full rounded-xl border border-outline-variant bg-transparent px-4 py-3 font-mono-xs text-mono-xs text-on-surface placeholder:text-on-surface-variant/50 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                placeholder='{"top_mm": 5, "bottom_mm": 5, "left_mm": 5, "right_mm": 5}'
                data-test="admin-layout-safe-area"
            ></textarea>
            <p class="mt-1 font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Optional. Defines the safe area margins in millimeters.') }}</p>
        </div>

        <div>
            <flux:label>{{ __('Preview image') }}</flux:label>
            <input
                type="file"
                wire:model="preview"
                accept="image/jpeg,image/png,image/webp"
                class="mt-1 block w-full text-sm text-on-surface-variant file:mr-4 file:rounded-md file:border-0 file:bg-primary/20 file:px-4 file:py-2 file:font-label-sm file:text-label-sm file:text-primary hover:file:bg-primary/30"
                data-test="admin-layout-preview"
            />
            <p class="mt-1 font-mono-xs text-mono-xs text-on-surface-variant">{{ __('Optional. JPEG, PNG, or WebP up to 2MB.') }}</p>
            @error('preview')
                <p class="mt-1 font-mono-xs text-mono-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-stack-sm pt-stack-md">
            <flux:button
                variant="ghost"
                :href="route('admin.layouts.index')"
                wire:navigate
            >
                {{ __('Cancel') }}
            </flux:button>

            <flux:button
                type="submit"
                variant="primary"
                data-test="admin-layout-save"
            >
                {{ __('Create layout') }}
            </flux:button>
        </div>
    </form>
</div>

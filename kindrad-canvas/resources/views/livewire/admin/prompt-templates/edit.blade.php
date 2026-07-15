<div class="flex flex-col gap-section" data-test="admin-prompt-template-edit">

    <header class="flex items-center justify-between">
        <div>
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                {{ __('Edit prompt template') }}
            </h1>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface-variant">
                {{ __('Current version: :version — saving will bump to v:next', ['version' => $version, 'next' => $version + 1]) }}
            </p>
        </div>

        <flux:button
            icon="arrow-left"
            variant="ghost"
            :href="route('admin.prompt-templates.index')"
            wire:navigate
        >
            {{ __('Back') }}
        </flux:button>
    </header>

    <form wire:submit="save" class="glass-card p-stack-lg space-y-stack-md">
        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:select wire:model="product_id" :label="__('Product')" required>
                <option value="">{{ __('Select product') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" @selected($product->id === $product_id)>
                        {{ $product->name }}
                    </option>
                @endforeach
            </flux:select>

            <flux:select wire:model="category_id" :label="__('Category')" required>
                <option value="">{{ __('Select category') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($category->id === $category_id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid gap-stack-md md:grid-cols-2">
            <flux:select wire:model="style_id" :label="__('Style')" required>
                <option value="">{{ __('Select style') }}</option>
                @foreach ($styles as $style)
                    <option value="{{ $style->id }}" @selected($style->id === $style_id)>
                        {{ $style->name }}
                    </option>
                @endforeach
            </flux:select>

            <flux:select wire:model="layout_id" :label="__('Layout')" required>
                <option value="">{{ __('Select layout') }}</option>
                @foreach ($layouts as $layout)
                    <option value="{{ $layout->id }}" @selected($layout->id === $layout_id)>
                        {{ $layout->name }}
                    </option>
                @endforeach
            </flux:select>
        </div>

        <flux:textarea
            wire:model="body"
            :label="__('Body')"
            rows="6"
            required
            data-test="admin-prompt-template-body"
        />

        <div class="rounded-md border border-outline-variant bg-surface-container-low p-stack-sm">
            <p class="font-mono-sm text-mono-sm uppercase tracking-widest text-on-surface-variant">
                {{ __('Available placeholders') }}
            </p>
            <p class="mt-stack-sm font-mono-sm text-mono-sm text-on-surface">
                @verbatim
                    {{name}} · {{phrase}} · {{theme}} · {{dedicatoria}} · {{image_tags}} · {{custom_prompt}} · {{subject_type}} · {{pose}} · {{style_description}} · {{print_specs}} · {{layout_instructions}}
                @endverbatim
            </p>
        </div>

        <div class="flex justify-end gap-stack-sm pt-stack-md">
            <flux:button
                variant="ghost"
                :href="route('admin.prompt-templates.index')"
                wire:navigate
            >
                {{ __('Cancel') }}
            </flux:button>

            <flux:button
                type="submit"
                variant="primary"
                data-test="admin-prompt-template-save"
            >
                {{ __('Save & bump to v:next', ['next' => $version + 1]) }}
            </flux:button>
        </div>
    </form>
</div>
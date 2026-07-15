<div>
    <form wire:submit="save" class="space-y-6" data-test="admin-plan-edit-form">
        <div>
            <label class="block text-sm font-medium">Nome</label>
            <input wire:model="name" type="text" class="mt-1 w-full rounded-lg border border-white/10 bg-background/60 px-3 py-2" />
            @error('name') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Slug</label>
            <input wire:model="slug" type="text" class="mt-1 w-full rounded-lg border border-white/10 bg-background/60 px-3 py-2" />
            @error('slug') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium">Descrição</label>
            <textarea wire:model="description" class="mt-1 w-full rounded-lg border border-white/10 bg-background/60 px-3 py-2"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium">Créditos por ciclo</label>
                <input wire:model="credits_per_period" type="number" min="1" class="mt-1 w-full rounded-lg border border-white/10 bg-background/60 px-3 py-2" />
                @error('credits_per_period') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div
                x-data="{
                    display: $wire.entangle('price_display'),
                    get cents() {
                        const v = (this.display ?? '').toString().replace(/\./g, ',').replace(/[^0-9,]/g, '');
                        if (!v) return 0;
                        const parts = v.split(',');
                        const reais = parseInt(parts[0] || '0', 10);
                        let cents = reais * 100;
                        if (parts.length > 1) {
                            const dec = (parts[1] + '00').slice(0, 2);
                            cents += parseInt(dec || '0', 10);
                        }
                        return isNaN(cents) ? 0 : cents;
                    },
                    format(v) {
                        const cleaned = (v ?? '').toString().replace(/\./g, ',').replace(/[^0-9,]/g, '');
                        if (!cleaned) return '';
                        const [reais, dec] = cleaned.split(',');
                        const reaisFmt = (reais || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        return dec !== undefined ? `${reaisFmt},${dec}` : reaisFmt;
                    },
                    onInput(e) {
                        const cursor = e.target.selectionStart;
                        const before = (this.display ?? '').length;
                        const formatted = this.format(e.target.value);
                        this.display = formatted;
                        this.$nextTick(() => {
                            const after = formatted.length;
                            const newCursor = cursor + (after - before);
                            e.target.setSelectionRange(newCursor, newCursor);
                            e.target.dispatchEvent(new Event('input', { bubbles: true }));
                        });
                    }
                }"
                wire:ignore.self
            >
                <label class="block text-sm font-medium">Preço</label>
                <div class="relative mt-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-zinc-400">R$</span>
                    <input
                        type="text"
                        inputmode="decimal"
                        placeholder="0,00"
                        x-model="display"
                        x-on:input.debounce.150ms="onInput($event)"
                        class="w-full rounded-lg border border-white/10 bg-background/60 py-2 pl-10 pr-3"
                        data-test="admin-plan-price-display"
                    />
                </div>
                <p class="mt-1 text-xs text-zinc-500">
                    <span x-show="cents > 0">
                        <span x-text="'R$ ' + (() => {
                            const reais = Math.floor(cents / 100);
                            const dec = (cents % 100).toString().padStart(2, '0');
                            return reais.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + dec;
                        })()"></span>
                        ·
                        <span class="text-zinc-400" x-text="cents.toLocaleString('pt-BR') + ' centavos'"></span>
                    </span>
                    <span x-show="cents === 0" x-cloak>{{ __('Digite o preço em reais. Ex.: 19,90') }}</span>
                </p>
                @error('price_display') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium">Moeda</label>
                <input wire:model="currency" type="text" maxlength="3" class="mt-1 w-full rounded-lg border border-white/10 bg-background/60 px-3 py-2" />
                @error('currency') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium">Intervalo</label>
                <select wire:model="interval_id" class="mt-1 w-full rounded-lg border border-white/10 bg-background/60 px-3 py-2">
                    @foreach ($intervals as $interval)
                        <option value="{{ $interval->id }}">{{ $interval->name }}</option>
                    @endforeach
                </select>
                @error('interval_id') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium">Ordem de exibição</label>
                <input wire:model="sort_order" type="number" min="0" class="mt-1 w-full rounded-lg border border-white/10 bg-background/60 px-3 py-2" />
                @error('sort_order') <p class="mt-1 text-sm text-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" wire:model="is_active" />
                    <span>Plano ativo</span>
                </label>
            </div>
        </div>

        @error('stripe') <p class="text-sm text-error">{{ $message }}</p> @enderror

        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.plans.index') }}" class="rounded-lg border border-white/10 px-4 py-2 text-sm">Cancelar</a>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Salvar</button>
        </div>
    </form>
</div>
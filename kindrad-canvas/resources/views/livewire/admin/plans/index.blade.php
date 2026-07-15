<div>
    <header class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ __('Planos de Assinatura') }}</h1>
        <a href="{{ route('admin.plans.create') }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white" data-test="admin-plans-new">
            {{ __('Novo plano') }}
        </a>
    </header>

    <div class="overflow-hidden rounded-lg border border-white/5">
        <table class="min-w-full divide-y divide-white/5" data-test="admin-plans-table">
            <thead class="bg-background/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Nome</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Preço</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Créditos</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Intervalo</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Ordem</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @foreach ($plans as $plan)
                    <tr data-test="admin-plan-row-{{ $plan->slug }}">
                        <td class="px-4 py-3 font-medium">{{ $plan->name }}</td>
                        <td class="px-4 py-3">{{ $plan->formattedPrice() }}</td>
                        <td class="px-4 py-3">{{ $plan->credits_per_period }}</td>
                        <td class="px-4 py-3">{{ $plan->interval?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $plan->sort_order }}</td>
                        <td class="px-4 py-3">
                            @if ($plan->is_active)
                                <flux:badge color="green">{{ __('Ativo') }}</flux:badge>
                            @else
                                <flux:badge color="zinc">{{ __('Inativo') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="text-sm text-primary hover:underline">{{ __('Editar') }}</a>
                            <button type="button" wire:click="toggleActive({{ $plan->id }})" class="ml-3 text-sm text-zinc-300 hover:underline">
                                {{ $plan->is_active ? __('Desativar') : __('Ativar') }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
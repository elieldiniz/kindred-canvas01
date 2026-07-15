<div>
    <header class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-semibold">{{ __('Assinaturas') }}</h1>
        <select wire:model.live="statusFilter" class="rounded-lg border border-white/10 bg-background/60 px-3 py-2 text-sm">
            <option value="">{{ __('Todos os status') }}</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->slug }}">{{ $status->name }}</option>
            @endforeach
        </select>
    </header>

    <div class="overflow-hidden rounded-lg border border-white/5">
        <table class="min-w-full divide-y divide-white/5" data-test="admin-subscriptions-table">
            <thead class="bg-background/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Usuário</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Plano</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Status</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Próxima cobrança</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">Encerra em</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse ($subscriptions as $sub)
                    <tr data-test="admin-subscription-row-{{ $sub->id }}">
                        <td class="px-4 py-3 font-medium">{{ $sub->user?->email ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $sub->subscriptionPlan?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $sub->status?->name ?? ucfirst($sub->stripe_status) }}</td>
                        <td class="px-4 py-3">{{ optional($sub->current_period_end)->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ optional($sub->ends_at)->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($sub->cancel_at_period_end)
                                <flux:badge color="warning">{{ __('Cancelará em fim do período') }}</flux:badge>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-400">{{ __('Nenhuma assinatura encontrada.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $subscriptions->links() }}</div>
</div>
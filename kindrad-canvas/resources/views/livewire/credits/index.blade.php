<div class="flex flex-col gap-section p-margin-page" data-test="credits-history-page">

    <header>
        <h1 class="font-headline-lg text-headline-lg text-on-surface">
            {{ __('Credits') }}
        </h1>
        <p class="mt-stack-sm font-body-sm text-body-sm text-on-surface-variant">
            {{ __('Every change to your balance, newest first.') }}
        </p>
    </header>

    @if ($transactions->isEmpty())
        <div class="glass-card mx-auto flex max-w-md flex-col items-center gap-stack-md p-stack-lg text-center" data-test="credits-empty-state">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-container-high">
                <span class="material-symbols-outlined text-[24px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">token</span>
            </div>
            <h2 class="font-headline-sm text-headline-sm text-on-surface">
                {{ __('No credit activity yet') }}
            </h2>
            <p class="max-w-sm font-body-sm text-body-sm text-on-surface-variant">
                {{ __('Generate your first artwork to start tracking how you spend and earn credits.') }}
            </p>
            <a
                href="{{ route('projects.new') }}"
                wire:navigate
                class="gradient-generate inline-flex items-center gap-stack-sm rounded-full px-stack-md py-2 font-label-md text-label-md font-bold text-on-primary"
            >
                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">add</span>
                {{ __('Generate your first artwork') }}
            </a>
        </div>
    @else
        <div class="glass-card overflow-x-auto p-stack-md" data-test="credits-transactions-table">
            <table class="w-full min-w-[640px] text-left">
                <thead>
                    <tr>
                        <th class="px-stack-md py-stack-sm text-left font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                            {{ __('When') }}
                        </th>
                        <th class="px-stack-md py-stack-sm text-left font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                            {{ __('Reason') }}
                        </th>
                        <th class="px-stack-md py-stack-sm text-right font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                            {{ __('Delta') }}
                        </th>
                        <th class="px-stack-md py-stack-sm text-right font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant">
                            {{ __('Balance') }}
                        </th>
                        <th class="hidden px-stack-md py-stack-sm text-left font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant sm:table-cell">
                            {{ __('Reference') }}
                        </th>
                        <th class="hidden px-stack-md py-stack-sm text-left font-mono-xs text-mono-xs uppercase tracking-widest text-on-surface-variant md:table-cell">
                            {{ __('Notes') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transactions as $transaction)
                        @php($delta = (int) $transaction->delta)
                        <tr class="border-t border-outline-variant/30" data-test="credits-transaction-row">
                            <td class="whitespace-nowrap px-stack-md py-stack-sm font-mono-xs text-mono-xs text-on-surface-variant">
                                {{ $transaction->created_at?->format('M j, Y · H:i') }}
                            </td>
                            <td class="px-stack-md py-stack-sm">
                                <flux:badge size="sm" :color="$delta >= 0 ? 'green' : 'red'">
                                    {{ $this->reasonLabel($transaction) }}
                                </flux:badge>
                            </td>
                            <td class="px-stack-md py-stack-sm text-right font-mono text-sm {{ $delta >= 0 ? 'text-primary' : 'text-error' }}">
                                {{ ($delta > 0 ? '+' : '').$delta }}
                            </td>
                            <td class="px-stack-md py-stack-sm text-right font-mono text-sm text-on-surface-variant">
                                {{ (int) $transaction->balance_after }}
                            </td>
                            <td class="hidden px-stack-md py-stack-sm text-left font-label-md text-label-md sm:table-cell">
                                @php($referenceRoute = $this->referenceRoute($transaction))
                                @if ($referenceRoute)
                                    <a href="{{ $referenceRoute }}" wire:navigate class="text-primary hover:underline">
                                        {{ $this->referenceLabel($transaction) }}
                                    </a>
                                @else
                                    <span class="text-on-surface-variant">{{ $this->referenceLabel($transaction) ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="hidden px-stack-md py-stack-sm font-label-md text-label-md italic text-on-surface-variant md:table-cell">
                                {{ $transaction->notes ?: '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div data-test="credits-pagination" class="flex items-center justify-between">
            <span class="font-mono-xs text-mono-xs text-on-surface-variant">
                {{ __('Showing :from to :to of :total results', [
                    'from' => $transactions->firstItem(),
                    'to' => $transactions->lastItem(),
                    'total' => $transactions->total(),
                ]) }}
            </span>
            {{ $transactions->links() }}
        </div>
    @endif
</div>

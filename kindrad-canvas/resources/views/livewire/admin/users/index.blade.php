<div class="flex flex-col gap-section" data-test="admin-users-index">

    <header>
        <h1 class="font-bold text-3xl text-white">
            {{ __('Users') }}
        </h1>
        <p class="mt-stack-sm font-body-sm text-body-sm text-on-surface-variant">
            {{ __('Manage users, grant credits, and toggle admin status') }}
        </p>
    </header>

    @if ($users->isEmpty())
        <div class="glass-card p-stack-lg text-center" data-test="admin-users-empty">
            <span class="material-symbols-outlined text-[36px] text-on-surface-variant" style="font-variation-settings: 'FILL' 0, 'wght' 400;">user-group</span>
            <p class="mt-stack-sm font-body-md text-body-md text-on-surface-variant">
                {{ __('No users yet.') }}
            </p>
        </div>
    @else
        <div class="glass-card overflow-x-auto p-2 bg-surface-container/40 border border-white/10 rounded-3xl shadow-2xl backdrop-blur-md" data-test="admin-users-table">
            <table class="w-full min-w-[800px] text-left">
                <thead>
                    <tr>
                        <th class="w-[40%] px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('User') }}
                        </th>
                        <th class="w-[15%] px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Credits') }}
                        </th>
                        <th class="w-[15%] px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Role') }}
                        </th>
                        <th class="w-[15%] px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Joined') }}
                        </th>
                        <th class="w-[15%] px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-white/50 border-b border-white/5">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr class="border-b border-white/5 hover:bg-white/[0.02] transition-colors" data-test="admin-user-row" wire:key="user-{{ $u->id }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <flux:avatar :name="$u->name" size="sm" />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-sm text-white">
                                            {{ $u->name }}
                                        </p>
                                        <p class="truncate text-xs text-white/50 mt-0.5">
                                            {{ $u->email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-gradient-to-r from-primary/20 to-transparent border border-primary/20 px-3 py-1 text-xs font-semibold text-primary shadow-sm">
                                    <span class="material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">bolt</span>
                                    {{ $u->credit_balance }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($u->is_admin)
                                    <flux:badge variant="primary" size="sm" data-test="admin-user-is-admin">
                                        {{ __('Admin') }}
                                    </flux:badge>
                                @else
                                    <flux:badge variant="default" size="sm">
                                        {{ __('User') }}
                                    </flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-white/60">
                                {{ $u->created_at?->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="openGrantModal({{ $u->id }})"
                                        class="text-xs font-semibold text-white/70 hover:text-white px-3 py-1.5 rounded-lg border border-white/10 hover:bg-white/5 transition-all"
                                        data-test="admin-user-grant-button"
                                    >
                                        {{ __('Grant') }}
                                    </button>
                                    @if ($u->id !== auth()->id())
                                        <button
                                            type="button"
                                            wire:click="toggleAdmin({{ $u->id }})"
                                            class="text-xs font-semibold {{ $u->is_admin ? 'text-red-400 hover:text-red-300 border-red-500/20 hover:bg-red-500/10' : 'text-primary/90 hover:text-primary border-primary/20 hover:bg-primary/10' }} px-3 py-1.5 rounded-lg border transition-all"
                                            data-test="admin-user-toggle-admin"
                                        >
                                            {{ $u->is_admin ? __('Demote') : __('Promote') }}
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @error('toggleAdmin')
        <flux:callout variant="danger" icon="exclamation-triangle">
            {{ $message }}
        </flux:callout>
    @enderror

    <flux:modal wire:model="showGrantModal" data-test="admin-user-grant-modal">
        <div class="space-y-stack-md">
            <div>
                <flux:heading size="lg">{{ __('Grant credits') }}</flux:heading>
                <flux:text class="mt-stack-sm text-on-surface-variant">
                    {{ __('Add credits to a user account. The transaction is logged with your name as the actor.') }}
                </flux:text>
            </div>

            <form wire:submit="grant" class="space-y-stack-md">
                <flux:input
                    wire:model="grantAmount"
                    type="number"
                    min="1"
                    :label="__('Amount')"
                    required
                    data-test="admin-user-grant-amount"
                />

                <flux:textarea
                    wire:model="grantNotes"
                    :label="__('Notes')"
                    :placeholder="__('Reason for granting credits (visible in audit log)')"
                    rows="2"
                    required
                    data-test="admin-user-grant-notes"
                />

                <div class="flex justify-end gap-stack-sm pt-stack-sm">
                    <flux:modal.close>
                        <flux:button variant="ghost" wire:click="$set('showGrantModal', false)">
                            {{ __('Cancel') }}
                        </flux:button>
                    </flux:modal.close>

                    <flux:button
                        type="submit"
                        variant="primary"
                        data-test="admin-user-grant-confirm"
                    >
                        {{ __('Grant credits') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
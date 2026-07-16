<div>
    {{-- Metrics dashboard card --}}
    <section
        class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
        data-test="admin-users-metrics"
    >
        <x-admin.metric-card
            icon="group"
            :label="__('Total')"
            :value="$metrics['total']"
            data-test="admin-users-metric-total"
        />
        <x-admin.metric-card
            icon="person"
            :label="__('Active')"
            :value="$metrics['active']"
            data-test="admin-users-metric-active"
        />
        @if ($metrics['admins'] > 0 && $metrics['admins'] < $metrics['total'])
            <x-admin.metric-card
                icon="shield_person"
                :label="__('Admins')"
                :value="$metrics['admins']"
                data-test="admin-users-metric-admins"
            />
        @endif
        @if ($metrics['deleted'] > 0)
            <x-admin.metric-card
                icon="delete"
                :label="__('Soft-deleted')"
                :value="$metrics['deleted']"
                data-test="admin-users-metric-deleted"
            />
        @endif
        @if ($metrics['suspended'] > 0)
            <x-admin.metric-card
                icon="block"
                :label="__('Suspended')"
                :value="$metrics['suspended']"
                data-test="admin-users-metric-suspended"
            />
        @endif
        @if ($metrics['past_due'] > 0)
            <x-admin.metric-card
                icon="priority_high"
                :label="__('past_due')"
                :value="$metrics['past_due']"
                data-test="admin-users-metric-past-due"
            />
        @endif
        @if ($metrics['with_active_subscription'] > 0)
            <x-admin.metric-card
                icon="workspace_premium"
                :label="__('With active subscription')"
                :value="$metrics['with_active_subscription']"
                data-test="admin-users-metric-active-subscription"
            />
        @endif
    </section>

    {{-- Users table --}}
    <div class="overflow-hidden rounded-lg border border-white/5" data-test="admin-users-table-wrapper">
        <table class="min-w-full divide-y divide-white/5" data-test="admin-users-table">
            <thead class="bg-background/50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">{{ __('User') }}</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">{{ __('Credits') }}</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">{{ __('Role') }}</th>
                    <th class="px-4 py-3 text-left text-xs uppercase tracking-widest text-zinc-400">{{ __('Joined') }}</th>
                    <th class="px-4 py-3 text-right text-xs uppercase tracking-widest text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse ($users as $row)
                    <tr
                        data-test="admin-users-row-{{ $row->id }}"
                        @class(['opacity-60' => $row->trashed()])
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-primary to-purple-600 text-xs font-bold text-white">
                                    {{ $row->initials() }}
                                </div>
                                <div>
                                    <div class="font-medium text-on-surface">{{ $row->name }}</div>
                                    <div class="text-xs text-on-surface-variant">{{ $row->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 text-sm">
                                <span class="material-symbols-outlined text-yellow-500 text-[16px]" style="font-variation-settings: 'FILL' 1, 'wght' 400;">bolt</span>
                                {{ (int) $row->credit_balance }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($row->is_admin)
                                <flux:badge color="primary">{{ __('Admin') }}</flux:badge>
                            @else
                                <flux:badge>{{ __('User') }}</flux:badge>
                            @endif
                            @if ($row->is_suspended)
                                <flux:badge color="warning" class="ml-1">{{ __('Suspended') }}</flux:badge>
                            @endif
                            @if ($row->trashed())
                                <flux:badge color="danger" class="ml-1">{{ __('Deleted') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-on-surface-variant">{{ $row->created_at->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                type="button"
                                wire:click="openSettings({{ $row->id }})"
                                class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-on-surface hover:bg-white/10"
                                data-test="admin-users-settings-{{ $row->id }}"
                            >
                                <span class="material-symbols-outlined text-[16px]" aria-hidden="true">settings</span>
                                {{ __('Configurações') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-on-surface-variant" data-test="admin-users-empty">
                            {{ __('No users yet.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>

    {{-- Settings modal --}}
    <flux:modal wire:model="showSettingsModal" class="md:w-[36rem]" data-test="admin-users-settings-modal">
        @if ($targetUser)
            <div class="space-y-6">
                <header class="space-y-1">
                    <h2 class="text-lg font-semibold">{{ $targetUser->name }}</h2>
                    <p class="text-xs text-on-surface-variant">{{ $targetUser->email }}</p>
                </header>

                {{-- Role --}}
                <section class="space-y-2">
                    <h3 class="text-xs uppercase tracking-widest text-on-surface-variant">{{ __('Role') }}</h3>
                    <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <div>
                            <p class="font-medium">{{ $targetUser->is_admin ? __('Admin') : __('User') }}</p>
                            <p class="text-xs text-on-surface-variant">{{ __('Toggle admin access for this account.') }}</p>
                        </div>
                        <flux:button wire:click="toggleAdmin" size="sm" variant="primary" data-test="admin-users-toggle-admin">
                            {{ $targetUser->is_admin ? __('Demote') : __('Promote') }}
                        </flux:button>
                    </div>
                </section>

                {{-- Grant credits --}}
                <section class="space-y-2">
                    <h3 class="text-xs uppercase tracking-widest text-on-surface-variant">{{ __('Credits') }}</h3>
                    <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <form wire:submit="grant" class="space-y-3">
                            <flux:input
                                wire:model="grantAmount"
                                type="number"
                                :label="__('Amount')"
                                min="1"
                                data-test="admin-users-grant-amount"
                            />
                            <flux:input
                                wire:model="grantNotes"
                                type="text"
                                :label="__('Reason / notes')"
                                data-test="admin-users-grant-notes"
                            />
                            @error('grantAmount') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                            @error('grantNotes') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                            <flux:button type="submit" variant="primary" data-test="admin-users-grant-submit">
                                {{ __('Grant') }}
                            </flux:button>
                        </form>
                    </div>
                </section>

                {{-- Suspend --}}
                <section class="space-y-2">
                    <h3 class="text-xs uppercase tracking-widest text-on-surface-variant">{{ __('Access') }}</h3>
                    <div class="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <div>
                            <p class="font-medium">
                                @if ($targetUser->is_suspended)
                                    {{ __('User is suspended') }}
                                @else
                                    {{ __('User is active') }}
                                @endif
                            </p>
                            <p class="text-xs text-on-surface-variant">{{ __('Suspended users cannot log in.') }}</p>
                        </div>
                        @if ($targetUser->is_suspended)
                            <flux:button wire:click="unsuspend" variant="primary" data-test="admin-users-unsuspend">
                                {{ __('Reactivate') }}
                            </flux:button>
                        @else
                            <flux:button wire:click="suspend" variant="danger" data-test="admin-users-suspend">
                                {{ __('Suspender') }}
                            </flux:button>
                        @endif
                    </div>
                </section>

                {{-- Reset password --}}
                <section class="space-y-2">
                    <h3 class="text-xs uppercase tracking-widest text-on-surface-variant">{{ __('Security') }}</h3>
                    <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <div class="mb-3">
                            <p class="font-medium">{{ __('Reset password') }}</p>
                            <p class="text-xs text-on-surface-variant">{{ __('Set a new password for this user.') }}</p>
                        </div>

                        <form wire:submit="resetPassword" class="space-y-3">
                            <flux:input
                                wire:model="newPassword"
                                type="text"
                                viewable
                                :label="__('New password')"
                                data-test="admin-users-new-password"
                            />
                            @error('newPassword') <p class="text-xs text-red-400">{{ $message }}</p> @enderror

                            <div class="flex items-center justify-between gap-3">
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    wire:click="generatePassword"
                                    data-test="admin-users-generate-password"
                                >
                                    <span class="material-symbols-outlined text-[16px]" aria-hidden="true">autorenew</span>
                                    {{ __('Generate') }}
                                </flux:button>

                                <div class="flex items-center gap-2">
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        wire:click="cancelPasswordReset"
                                        data-test="admin-users-password-cancel"
                                    >
                                        {{ __('Cancel') }}
                                    </flux:button>
                                    <flux:button
                                        type="submit"
                                        variant="primary"
                                        data-test="admin-users-reset-password"
                                    >
                                        {{ __('Update password') }}
                                    </flux:button>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <footer class="flex justify-end">
                    <flux:button wire:click="closeSettings" variant="ghost" data-test="admin-users-settings-close">
                        {{ __('Close') }}
                    </flux:button>
                </footer>
            </div>
        @endif
    </flux:modal>
</div>

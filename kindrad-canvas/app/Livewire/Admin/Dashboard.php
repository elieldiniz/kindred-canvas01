<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function totalUsers(): int
    {
        return User::query()->count();
    }

    public function newUsersLast7Days(): int
    {
        return User::query()->where('created_at', '>=', now()->subDays(7))->count();
    }

    public function totalGenerations(): int
    {
        return Generation::query()->count();
    }

    public function creditsInCirculation(): int
    {
        return (int) User::query()->sum('credit_balance');
    }

    public function creditsSpent(): int
    {
        return (int) CreditTransaction::query()
            ->whereHas('reason', fn ($q) => $q->where('expected_sign', '-'))
            ->sum('delta');
    }

    public function recentGenerations(): array
    {
        return Generation::query()
            ->with('status')
            ->latest('id')
            ->limit(5)
            ->get()
            ->groupBy(fn (Generation $g) => $g->status?->slug ?? 'unknown')
            ->map->count()
            ->toArray();
    }

    public function recentAuditLogs(): array
    {
        return AuditLog::query()
            ->with(['actor', 'action'])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'actor' => $log->actor?->email,
                'action' => $log->action?->name ?? $log->action?->slug ?? 'unknown',
                'target' => $log->target_type.':'.$log->target_id,
                'created_at' => $log->created_at?->format('M j, Y · H:i'),
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.admin.dashboard')->layout('components.layouts.admin', [
            'header' => __('Admin Dashboard'),
        ]);
    }
}

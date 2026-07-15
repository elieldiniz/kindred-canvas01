<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\Layout;
use App\Models\Product;
use App\Models\PromptTemplate;
use App\Models\Style;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionStatus;
use App\Models\User;
use Illuminate\Contracts\View\View;
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

    /**
     * @return array<int, array{id: int, actor: ?string, action: string, target_label: string, target_href: ?string, created_at: ?string}>
     */
    public function recentAuditLogs(): array
    {
        return AuditLog::query()
            ->with(['actor', 'action'])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(function (AuditLog $log): array {
                return [
                    'id' => $log->id,
                    'actor' => $log->actor?->email,
                    'action' => $log->action?->name ?? $log->action?->slug ?? 'unknown',
                    'target_label' => $this->describeAuditTarget($log),
                    'target_href' => $this->auditTargetHref($log),
                    'created_at' => $log->created_at?->format('M j, Y · H:i'),
                ];
            })
            ->all();
    }

    private function describeAuditTarget(AuditLog $log): string
    {
        return match ($log->target_type) {
            SubscriptionPlan::class => 'Plano #'.$log->target_id,
            User::class => 'Usuário #'.$log->target_id,
            Product::class => 'Produto #'.$log->target_id,
            Category::class => 'Categoria #'.$log->target_id,
            Style::class => 'Estilo #'.$log->target_id,
            Layout::class => 'Layout #'.$log->target_id,
            PromptTemplate::class => 'Prompt template #'.$log->target_id,
            default => '#'.$log->target_id,
        };
    }

    private function auditTargetHref(AuditLog $log): ?string
    {
        return match ($log->target_type) {
            SubscriptionPlan::class => route('admin.plans.edit', $log->target_id, false),
            User::class => route('admin.users.index', [], false),
            default => null,
        };
    }

    public function totalSubscriptions(): int
    {
        return Subscription::query()->count();
    }

    /**
     * @return array<string, int> slug => count
     */
    public function subscriptionStatusBreakdown(): array
    {
        return Subscription::query()
            ->with('status')
            ->get()
            ->groupBy(function (Subscription $s): string {
                $status = $s->status;
                if ($status !== null) {
                    return $status->slug;
                }

                return $s->stripe_status ?: 'unknown';
            })
            ->map->count()
            ->toArray();
    }

    /**
     * Active paying subscriptions grouped by plan, with normalized monthly revenue.
     *
     * @return array<int, object{plan: SubscriptionPlan, active_count: int, monthly_revenue_cents: int}>
     */
    public function plansRevenue(): array
    {
        $activeId = SubscriptionStatus::where('slug', 'active')->value('id');

        if ($activeId === null) {
            return [];
        }

        $plans = SubscriptionPlan::query()
            ->with('interval')
            ->withCount(['subscriptions as active_count' => fn ($q) => $q->where('status_id', $activeId)])
            ->orderBy('sort_order')
            ->get();

        $rows = [];
        foreach ($plans as $plan) {
            /** @var int $count */
            $count = (int) ($plan->getAttribute('active_count') ?? 0);
            if ($count === 0) {
                continue;
            }
            $interval = $plan->interval;
            $isYearly = $interval->slug === 'year';
            $monthlyCents = $isYearly
                ? intdiv((int) $plan->price_cents, 12)
                : (int) $plan->price_cents;

            $rows[] = (object) [
                'plan' => $plan,
                'active_count' => $count,
                'monthly_revenue_cents' => $monthlyCents * $count,
            ];
        }

        return $rows;
    }

    public function mrrCents(): int
    {
        $sum = 0;
        foreach ($this->plansRevenue() as $row) {
            $sum += (int) $row->monthly_revenue_cents;
        }

        return $sum;
    }

    public function mrrFormatted(): string
    {
        $cents = $this->mrrCents();
        $value = number_format($cents / 100, 2, ',', '.');

        return "R$ {$value}";
    }

    public function arrCents(): int
    {
        return $this->mrrCents() * 12;
    }

    public function arrFormatted(): string
    {
        $value = number_format($this->arrCents() / 100, 2, ',', '.');

        return "R$ {$value}";
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard')->layout('components.layouts.admin', [
            'header' => __('Admin Dashboard'),
        ]);
    }
}

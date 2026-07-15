<?php

namespace App\Livewire\Admin\Plans;

use App\Models\SubscriptionPlan;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function toggleActive(int $id, AuditLogger $audit): void
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $plan->is_active = ! $plan->is_active;
        $plan->save();

        $audit->record(
            actor: auth()->user(),
            actionSlug: 'edit_subscription_plan',
            target: $plan,
            payload: [
                'event' => 'toggled_active',
                'after' => ['is_active' => $plan->is_active],
            ],
        );
    }

    public function render(): View
    {
        return view('livewire.admin.plans.index', [
            'plans' => SubscriptionPlan::with('interval')
                ->ordered()
                ->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Planos de Assinatura'),
        ]);
    }
}

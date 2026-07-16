<?php

namespace App\Livewire\Admin\Subscriptions;

use App\Models\PaymentFailure;
use App\Models\Subscription;
use App\Models\SubscriptionStatus;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Subscription::with(['user', 'subscriptionPlan.interval', 'status'])
            ->withCount('paymentFailures')
            ->latest('id');

        if ($this->statusFilter !== '') {
            $query->whereHas('status', fn ($q) => $q->where('slug', $this->statusFilter));
        }

        $recentFailures = PaymentFailure::query()
            ->with(['user', 'subscription'])
            ->recent()
            ->limit(10)
            ->get();

        return view('livewire.admin.subscriptions.index', [
            'subscriptions' => $query->paginate(25),
            'statuses' => SubscriptionStatus::orderBy('name')->get(),
            'recentFailures' => $recentFailures,
        ])->layout('components.layouts.admin', [
            'header' => __('Assinaturas'),
        ]);
    }
}

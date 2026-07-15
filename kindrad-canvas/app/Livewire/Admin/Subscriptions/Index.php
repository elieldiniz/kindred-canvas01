<?php

namespace App\Livewire\Admin\Subscriptions;

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
            ->latest('id');

        if ($this->statusFilter !== '') {
            $query->whereHas('status', fn ($q) => $q->where('slug', $this->statusFilter));
        }

        return view('livewire.admin.subscriptions.index', [
            'subscriptions' => $query->paginate(25),
            'statuses' => SubscriptionStatus::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Assinaturas'),
        ]);
    }
}

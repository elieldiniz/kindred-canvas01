<?php

namespace App\Livewire\Admin\AuditLog;

use App\Models\AuditLog;
use App\Models\AuditLogAction;
use App\Models\User;
use Livewire\Component;

class Index extends Component
{
    public ?string $filterActor = null;

    public ?string $filterAction = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function render()
    {
        $query = AuditLog::with('actor', 'action', 'target')
            ->latest();

        if ($this->filterActor !== null && $this->filterActor !== '') {
            $query->where('actor_user_id', $this->filterActor);
        }

        if ($this->filterAction !== null && $this->filterAction !== '') {
            $query->where('action_id', $this->filterAction);
        }

        return view('livewire.admin.audit-log.index', [
            'logs' => $query->paginate(25),
            'actors' => User::whereIn('id', AuditLog::distinct()->pluck('actor_user_id'))->orderBy('name')->get(),
            'actions' => AuditLogAction::orderBy('name')->get(),
        ])->layout('components.layouts.admin', [
            'header' => __('Audit Log'),
        ]);
    }
}

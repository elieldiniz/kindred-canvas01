<?php

namespace App\Livewire\Admin\AuditLog;

use App\Models\AuditLog;
use App\Models\AuditLogAction;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?string $filterActor = null;

    public ?string $filterAction = null;

    /**
     * Logs whose details row is currently expanded. Keyed by log id.
     *
     * @var array<int, bool>
     */
    public array $expanded = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }

    public function toggleDetails(int $logId): void
    {
        if (isset($this->expanded[$logId])) {
            unset($this->expanded[$logId]);
        } else {
            $this->expanded[$logId] = true;
        }
    }

    public function updatingFilterActor(): void
    {
        $this->expanded = [];
        $this->resetPage();
    }

    public function updatingFilterAction(): void
    {
        $this->expanded = [];
        $this->resetPage();
    }

    /**
     * Map action slugs to a Flux badge variant. Severity uses keywords:
     * dangerous actions get `danger`, credentials/security get `warning`,
     * everything else falls back to `primary` for visibility on dark glass.
     */
    public function badgeVariantFor(?string $slug): string
    {
        if ($slug === null || $slug === '') {
            return 'primary';
        }

        $dangerKeywords = ['delete', 'destroy', 'force', 'remove', 'suspend_user'];
        $warningKeywords = ['password', 'reset', 'suspend', 'grant', 'tier change'];
        $successKeywords = ['create', 'activate', 'restore'];

        foreach ($dangerKeywords as $keyword) {
            if (str_contains($slug, $keyword)) {
                return 'danger';
            }
        }

        foreach ($warningKeywords as $keyword) {
            if (str_contains($slug, $keyword)) {
                return 'warning';
            }
        }

        foreach ($successKeywords as $keyword) {
            if (str_contains($slug, $keyword)) {
                return 'success';
            }
        }

        return 'primary';
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

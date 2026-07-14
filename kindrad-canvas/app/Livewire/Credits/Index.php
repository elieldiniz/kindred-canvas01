<?php

namespace App\Livewire\Credits;

use App\Models\CreditTransaction;
use App\Models\Generation;
use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function mount(): void
    {
        if (Auth::id() === null) {
            abort(401);
        }
    }

    /**
     * @return LengthAwarePaginator<int, CreditTransaction>
     */
    public function paginator(): LengthAwarePaginator
    {
        $userId = (int) Auth::id();

        return CreditTransaction::query()
            ->with(['reason', 'user'])
            ->forUser($userId)
            ->latest('id')
            ->paginate(25);
    }

    public function reasonLabel(CreditTransaction $transaction): string
    {
        return (string) ($transaction->reason?->name ?? __('Unknown'));
    }

    public function deltaLabel(int $delta): string
    {
        return ($delta > 0 ? '+' : '').$delta;
    }

    public function deltaClass(int $delta): string
    {
        return $delta >= 0 ? 'text-emerald-500' : 'text-error';
    }

    public function referenceLabel(CreditTransaction $transaction): ?string
    {
        $reference = $transaction->reference;

        if ($reference === null) {
            return null;
        }

        return match ($reference::class) {
            Project::class => $reference->title ?: __('Untitled project'),
            Generation::class => __('Generation #:id', ['id' => $reference->id]),
            default => $reference::class.':'.$reference->getKey(),
        };
    }

    public function referenceRoute(CreditTransaction $transaction): ?string
    {
        $reference = $transaction->reference;

        if ($reference === null) {
            return null;
        }

        return match ($reference::class) {
            Project::class => route('projects.show', $reference),
            Generation::class => $reference->project
                ? route('projects.show', $reference->project)
                : null,
            default => null,
        };
    }

    public function render()
    {
        return view('livewire.credits.index', [
            'transactions' => $this->paginator(),
        ]);
    }
}

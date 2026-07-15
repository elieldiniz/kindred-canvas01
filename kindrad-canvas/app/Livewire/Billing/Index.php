<?php

namespace App\Livewire\Billing;

use App\Actions\Billing\CreateBillingPortalSessionAction;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Index extends Component
{
    public ?Subscription $subscription = null;

    public bool $showSuccessBanner = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        if (Auth::id() === null) {
            abort(401);
        }

        $this->showSuccessBanner = request()->boolean('success');

        $this->loadSubscription();
    }

    public function openPortal(CreateBillingPortalSessionAction $action): void
    {
        try {
            $url = $action->handle($this->currentUser());
            $this->redirect($url);
        } catch (NotFoundHttpException $e) {
            abort(404, $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.billing.index', [
            'user' => $this->currentUser(),
        ]);
    }

    protected function loadSubscription(): void
    {
        $this->subscription = Subscription::query()
            ->with(['subscriptionPlan.interval', 'status'])
            ->where('user_id', Auth::id())
            ->latest('id')
            ->first();
    }

    protected function currentUser(): User
    {
        return User::find(Auth::id());
    }
}

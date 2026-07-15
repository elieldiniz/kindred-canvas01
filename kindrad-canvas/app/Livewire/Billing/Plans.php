<?php

namespace App\Livewire\Billing;

use App\Actions\Billing\StartCheckoutAction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Plans extends Component
{
    public function mount(): void
    {
        if (Auth::id() === null) {
            abort(401);
        }
    }

    public function subscribe(int $planId, StartCheckoutAction $action): mixed
    {
        $plan = SubscriptionPlan::active()->find($planId);

        if ($plan === null) {
            $this->addError('plan', 'Esse plano não está disponível.');

            return null;
        }

        try {
            $url = $action->handle($this->currentUser(), $plan);

            return $this->redirect($url);
        } catch (\Throwable $e) {
            $this->addError('plan', $e->getMessage());

            return null;
        }
    }

    public function render(): View
    {
        return view('livewire.billing.plans', [
            'plans' => SubscriptionPlan::active()
                ->ordered()
                ->with('interval')
                ->get(),
            'user' => $this->currentUser(),
        ]);
    }

    protected function currentUser(): User
    {
        return User::find(Auth::id());
    }
}

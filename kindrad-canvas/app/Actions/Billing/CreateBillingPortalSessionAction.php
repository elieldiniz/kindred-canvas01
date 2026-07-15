<?php

namespace App\Actions\Billing;

use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateBillingPortalSessionAction
{
    public function handle(User $user): string
    {
        if (empty($user->stripe_id)) {
            throw new NotFoundHttpException('User has no Stripe customer; subscribe to a plan first.');
        }

        return $user->billingPortalUrl(route('billing.index', ['portal_return' => 1]));
    }
}

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dunning Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days a subscription in `past_due` status is allowed to remain
    | usable before new generations are blocked. The countdown is anchored to
    | the subscription's current_period_end (the date the unpaid invoice
    | originally targeted); once now() exceeds current_period_end + grace_days,
    | SubmitGeneration throws BillingAccessDeniedException.
    |
    */

    'grace_days' => (int) env('BILLING_GRACE_DAYS', 7),
];

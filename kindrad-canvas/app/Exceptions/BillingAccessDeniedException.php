<?php

namespace App\Exceptions;

use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BillingAccessDeniedException extends HttpException
{
    public function __construct(string $message = 'Billing access denied.', int $statusCode = 402)
    {
        parent::__construct($statusCode, $message);
    }

    public static function forUser(User $user): self
    {
        return new self("User {$user->id} is in past_due status beyond the grace period and cannot submit new generations.");
    }
}

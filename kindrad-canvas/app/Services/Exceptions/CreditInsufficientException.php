<?php

namespace App\Services\Exceptions;

use RuntimeException;

class CreditInsufficientException extends RuntimeException
{
    public static function for(int $currentBalance, int $requested): self
    {
        return new self(sprintf(
            'User has %d credits but %d were requested.',
            $currentBalance,
            $requested,
        ));
    }
}

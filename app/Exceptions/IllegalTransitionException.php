<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an entry status transition is not allowed by the approval state
 * machine (e.g. approving something that isn't `submitted`).
 */
class IllegalTransitionException extends RuntimeException
{
    public static function from(string $from, string $to): self
    {
        return new self("Illegal status transition: {$from} → {$to}.");
    }
}

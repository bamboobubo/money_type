<?php

namespace Re2bit\Types;

use DomainException;
use Throwable;

class PrecisionException extends DomainException
{
    private const MESSAGE = 'Precision mismatch. %s expected but %s given';

    private function __construct(string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function createPrecisionException(int $expectedPrecision, int $actualPrecision): self
    {
        return new self(sprintf(self::MESSAGE, $expectedPrecision, $actualPrecision));
    }
}

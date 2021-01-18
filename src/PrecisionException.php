<?php

/**
 * This file is part of the re2bit/money_type library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) René Gerritsen <https://re2bit.de>
 * @license http://opensource.org/licenses/MIT MIT
 */

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

<?php

namespace Re2bit\Types;

use InvalidArgumentException;
use Money\Currency as PhpMoneyCurrency;
use Re2bit\Types\Currency\ISO4217;

class Currency
{
    const CODE_DELIMITER = '_';

    private string $code;

    private int $precision;

    /**
     * @param string   $currencyCode
     * @param int|null $precision
     */
    public function __construct(string $currencyCode, ?int $precision = null)
    {
        if (!isset(ISO4217::PRECISION[$currencyCode])) {
            throw new InvalidArgumentException('Unknown Currency Code:' . $currencyCode);
        }
        $ISO4217Precision = ISO4217::PRECISION[$currencyCode];

        $this->code = $currencyCode
            . (
                null !== $precision && $precision !== $ISO4217Precision
                    ? self::CODE_DELIMITER . $precision
                    : ''
            );
        $this->precision = $precision ?? $ISO4217Precision;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function toPhpMoneyCurrency(): PhpMoneyCurrency
    {
        return new PhpMoneyCurrency($this->code);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCodeWithoutPrecision(): string
    {
        $codeParts = explode(self::CODE_DELIMITER, $this->getCode());
        return (string)reset($codeParts);
    }

    public function hasPrecisionOtherThanIso4217(): bool
    {
        return isset(ISO4217::PRECISION[$this->getCode()]);
    }

    public function __toString(): string
    {
        return $this->getCode();
    }
}

<?php

namespace Re2bit\Types;

use InvalidArgumentException;
use Re2bit\Types\Money\ISO4217;
use Symfony\Component\Validator\Constraints as Assert;

class Currency
{
    const CODE_DELIMITER = '_';
    /**
     * @Assert\Type(type="string")
     * @Assert\Currency()
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    private string $code;

    /**
     * @Assert\Type(type="integer")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     */
    private int $precision;

    /**
     * @param string $currencyCode
     * @param int    $precision
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

    public function toPhpMoneyCurrency(): \Money\Currency
    {
        return new \Money\Currency($this->code);
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

    public function __toString(): string
    {
        return $this->getCode();
    }
}

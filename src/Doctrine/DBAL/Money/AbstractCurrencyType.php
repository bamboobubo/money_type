<?php

namespace Re2bit\Types\Doctrine\DBAL\Money;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DecimalType;
use Exception;
use Re2bit\Types\Currency;
use Re2bit\Types\Money;

abstract class AbstractCurrencyType extends DecimalType
{
    public const NAME = 'money';

    public const PRECISION = 2;

    public const CURRENCY = '';

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof Money && $value->getCurrency()->getPrecision() === static::PRECISION) {
            return $value->toDecimalString(static::PRECISION);
        }

        if ($value === null) {
            return null;
        }

        throw ConversionException::conversionFailedInvalidType(
            $value,
            $this->getName(),
            [
                'null',
                Money::class . ' with precision ' . static::PRECISION . ' and Currency ' . static::CURRENCY,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof Currency) {
            return $value;
        }

        try {
            $money = Money::fromDecimalString(
                $value,
                new Currency(
                    static::CURRENCY,
                    static::PRECISION,
                )
            );
        } catch (Exception $e) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                '%.' . static::PRECISION . 'F'
            );
        }

        return $money;
    }
}

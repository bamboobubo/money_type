<?php

namespace Re2bit\Types\Doctrine\DBAL\Money;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;
use Exception;
use Re2bit\Types\Currency;
use Re2bit\Types\Money\ISO4217;

class CurrencyType extends StringType
{
    public const NAME = 'money_currency';

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultLength(AbstractPlatform $platform)
    {
        return 3;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof Currency) {
            return $value->getCode();
        }

        if (isset(ISO4217::PRECISION[$value])) {
            return $value;
        }

        throw ConversionException::conversionFailedInvalidType(
            $value,
            $this->getName(),
            ['ISO4217-STRING', Currency::class]
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
            $currency = new Currency($value);
        } catch (Exception $e) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString()
            );
        }

        return $currency;
    }
}

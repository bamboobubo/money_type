<?php

namespace Re2bit\Types\DBAL\Money;

use Doctrine\DBAL\Types\DecimalType;

class AmountType extends DecimalType
{
    public const NAME = 'money_amount';

    /**
     * Gets the name of this type.
     *
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}

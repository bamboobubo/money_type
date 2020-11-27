<?php

namespace Re2bit\Types\Doctrine\DBAL\Money;

class MoneyEurType extends AbstractCurrencyType
{
    public const NAME = 'money_eur';
    public const PRECISION = 2;
    public const CURRENCY = 'EUR';
}

<?php

namespace Re2bit\Types\Tests;

use NumberFormatter;
use PHPUnit\Framework\TestCase;
use Re2bit\Types\Money;

class ConversionTest extends TestCase
{
    public function testToFloat(): void
    {
        $numberFormatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
        $money = Money::fromFormattedString('1,23 €', $numberFormatter);
        static::assertSame(1.23, $money->toFloat());
    }

    public function testToString(): void
    {
        $currencyFormatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
        $money = Money::fromFormattedString('1,23 €', $currencyFormatter);
        static::assertSame(1.23, $money->toFloat());
        static::assertSame("1,23\xc2\xa0€", $money->toString($currencyFormatter));
    }

    public function testToInt(): void
    {
        $currencyFormatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
        $money = Money::fromFormattedString('1,23 €', $currencyFormatter);
        static::assertEquals(123, $money->toInt());
    }
}

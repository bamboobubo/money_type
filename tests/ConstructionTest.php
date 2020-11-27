<?php

namespace Re2bit\Types\Tests;

use DomainException;
use NumberFormatter;
use PHPUnit\Framework\TestCase;
use Re2bit\Types\Currency;
use Re2bit\Types\Money;

class ConstructionTest extends TestCase
{
    public function testCreateFromFloat(): void
    {
        $money = Money::fromFloat(1.23, new Currency('EUR'));
        static::assertSame('123', $money->getAmount());
    }

    public function problematicFloatTest(): void
    {
        $a = 0.1;
        $b = 0.2;
        $c = $a + $b;
        $problematicFloat = $c * 10;

        $money = Money::fromFloat($problematicFloat, new Currency('EUR'));

        static::assertSame('300', $money->getAmount());
        static::assertFalse(3.0 === $problematicFloat);          // Floating Point imprecision
        static::assertFalse(3.0 == $problematicFloat);           // Floating Point imprecision
        static::assertSame(3.0, $problematicFloat);       // PhpUnit uses Epsilon for Double compare
        static::assertTrue(3.0 === $money->toFloat());           // after conversion to Money its rounded
    }

    public function testCreateFromInt(): void
    {
        $money = Money::fromInt(123, new Currency('EUR'));
        static::assertSame('123', $money->getAmount());
    }

    public function testCreateFromString(): void
    {
        $currencyFormatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
        $money = Money::fromFormattedString('1,23 €', $currencyFormatter);
        static::assertSame('123', $money->getAmount());
        static::assertSame('EUR', $money->getCurrency()->getCode());
        $money = Money::fromFormattedString("1,23\xc2\xa0€", $currencyFormatter);
        static::assertSame('123', $money->getAmount());
        static::assertSame('EUR', $money->getCurrency()->getCode());
    }

    public function testCreateValidation(): void
    {
        $this->expectException(DomainException::class);
        $currencyFormatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
        Money::fromFormattedString('1dass asdas', $currencyFormatter);
    }

    public function testFromString(): void
    {
        $currencyFormatter = new NumberFormatter('de_DE', NumberFormatter::DECIMAL);
        $money = Money::fromString('1,23', new Currency('EUR'), $currencyFormatter);
        static::assertSame('123', $money->getAmount());
        static::assertSame('EUR', $money->getCurrency()->getCode());
    }

    public function testFromArray(): void
    {
        $data = [
            'amount'   => '123',
            'currency' => [
                'code'      => 'EUR',
                'precision' => 2,
            ],
        ];
        $money = Money::fromArray($data);
        static::assertSame('12300', $money->getAmount());
        static::assertSame(123.0, $money->toFloat());
        static::assertSame('EUR', $money->getCurrency()->getCode());
        static::assertSame(2, $money->getCurrency()->getPrecision());
    }
}

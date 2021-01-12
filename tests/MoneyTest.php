<?php

namespace Re2bit\Types\Tests;

use PHPUnit\Framework\TestCase;
use Re2bit\Types\Currency;
use Re2bit\Types\Money;
use Re2bit\Types\PrecisionException;

class MoneyTest extends TestCase
{
    public function testAdd(): void
    {
        $eur = new Currency('EUR');
        $moneyEur1050 = Money::fromInt(1050, $eur);
        $moneyEur1030 = Money::fromInt(1030, $eur);
        $expectedSum1 = Money::fromInt(2080, $eur);
        $expectedSum2 = Money::fromInt(3130, $eur);
        $sum1 = $moneyEur1030->add($moneyEur1050);
        $sum2 = $moneyEur1030->add($moneyEur1050, $moneyEur1050);

        static::assertTrue($sum1->equals($expectedSum1));
        static::assertTrue($sum2->equals($expectedSum2));
    }

    public function testAddPrecisionException(): void
    {
        $this->expectException(PrecisionException::class);
        $moneyEur1050 = Money::fromInt(105049, new Currency('EUR', 4));
        $moneyEur1030 = Money::fromInt(1030, new Currency('EUR'));
        $moneyEur1030->add($moneyEur1050);
    }

    public function testSubtract(): void
    {
        $eur = new Currency('EUR');
        $moneyEur1050 = Money::fromInt(1050, $eur);
        $moneyEur1030 = Money::fromInt(1030, $eur);
        $expectedSum1 = Money::fromInt(20, $eur);
        $expectedSum2 = Money::fromInt(-1010, $eur);
        $sum1 = $moneyEur1050->subtract($moneyEur1030);
        $sum2 = $moneyEur1050->subtract($moneyEur1030, $moneyEur1030);

        static::assertTrue($sum1->equals($expectedSum1));
        static::assertTrue($sum2->equals($expectedSum2));
    }

    public function testSubtractPrecisionException(): void
    {
        $this->expectException(PrecisionException::class);
        $moneyEur1051 = Money::fromInt(105049, new Currency('EUR', 4));
        $moneyEur1030 = Money::fromInt(1030, new Currency('EUR'));
        $result = $moneyEur1030->subtract($moneyEur1051);
    }

    public function testMultiply(): void
    {
        $eur = new Currency('EUR');
        $moneyEur1050 = Money::fromInt(1050, $eur);
        $expected = Money::fromInt(2100, $eur);
        $result = $moneyEur1050->multiply(2);
        static::assertTrue($result->equals($expected));
    }

    public function testDivide(): void
    {
        $eur = new Currency('EUR');
        $moneyEur1050 = Money::fromInt(1049, $eur);
        $expected = Money::fromInt(525, $eur);
        $result = $moneyEur1050->divide(2);
        static::assertTrue($result->equals($expected));
    }

    public function testMod(): void
    {
        $eur = new Currency('EUR');
        $money = Money::fromInt(1051, $eur);
        $factor = Money::fromInt(50, $eur);
        $expectedResult = Money::fromInt(1, $eur);
        $result = $money->mod($factor);
        static::assertTrue($expectedResult->equals($result));
    }

    public function testModPrecisionException(): void
    {
        $this->expectException(PrecisionException::class);
        $money = Money::fromInt(105160, new Currency('EUR', 4));
        $factor = Money::fromInt(50, new Currency('EUR'));
        $money->mod($factor);
    }

    public function testMinPrecisionException(): void
    {
        $this->expectException(PrecisionException::class);
        $moneyEur1051 = Money::fromInt(105049, new Currency('EUR', 4));
        $moneyEur1030 = Money::fromInt(1030, new Currency('EUR'));
        $result = Money::min($moneyEur1030, $moneyEur1051);
    }

    public function testMaxPrecisionException(): void
    {
        $this->expectException(PrecisionException::class);
        $moneyEur1051 = Money::fromInt(105049, new Currency('EUR', 4));
        $moneyEur1030 = Money::fromInt(1030, new Currency('EUR'));
        $result = Money::max($moneyEur1030, $moneyEur1051);
    }

    public function testSumPrecisionException(): void
    {
        $this->expectException(PrecisionException::class);
        $moneyEur1051 = Money::fromInt(105049, new Currency('EUR', 4));
        $moneyEur1030 = Money::fromInt(1030, new Currency('EUR'));
        $result = Money::sum($moneyEur1030, $moneyEur1051);
    }

    public function testAvgPrecisionException(): void
    {
        $this->expectException(PrecisionException::class);
        $moneyEur1051 = Money::fromInt(105049, new Currency('EUR', 4));
        $moneyEur1030 = Money::fromInt(1030, new Currency('EUR'));
        $result = Money::avg($moneyEur1030, $moneyEur1051);
    }
}

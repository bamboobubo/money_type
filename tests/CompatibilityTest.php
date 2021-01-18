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

namespace Re2bit\Types\Tests;

use PHPUnit\Framework\TestCase;
use Re2bit\Types\Money;

class CompatibilityTest extends TestCase
{
    public function testAdd(): void
    {
        $moneyA = Money::EUR(1.0);
        $moneyB = Money::EUR(4.0);
        $moneySum = $moneyA->add($moneyB);
        static::assertEquals(Money::EUR(5.0), $moneySum);
    }

    public function testSub(): void
    {
        $moneyA = Money::EUR(1.0);
        $moneyB = Money::EUR(4.0);
        $moneySum = $moneyA->subtract($moneyB);
        static::assertEquals(Money::EUR(-3.0), $moneySum);
    }

    public function testGetters(): void
    {
        $money = Money::EUR(1.0);
        static::assertEquals(1.0, $money->toFloat());
        static::assertEquals('EUR', $money->getCurrency());
    }

    public function testIsZero(): void
    {
        static::assertTrue(Money::EUR(0.0)->isZero());
        static::assertFalse(Money::EUR(0.01)->isZero());
        static::assertFalse(Money::EUR(-0.01)->isZero());
        static::assertFalse(Money::EUR(0.05)->isZero());

        static::assertFalse(Money::EUR(0.009)->isZero());
        static::assertFalse(Money::EUR(0.0099999999)->isZero());
        static::assertFalse(Money::EUR(-0.0099999999)->isZero());
        static::assertTrue(Money::EUR(0.0049)->isZero());
        static::assertFalse(Money::EUR(0.0050)->isZero());
        static::assertTrue(Money::EUR(0.000001)->isZero());
        static::assertFalse(Money::EUR(0.1)->isZero());
    }

    public function testIsLessOrEqualZero(): void
    {
        static::assertTrue(Money::EUR(0.0)->isLessOrEqualZero());
        static::assertTrue(Money::EUR(-0.01)->isLessOrEqualZero());
        static::assertTrue(Money::EUR(0.0025)->isLessOrEqualZero());
        static::assertFalse(Money::EUR(0.0051)->isLessOrEqualZero());
        static::assertTrue(Money::EUR(-0.000001)->isLessOrEqualZero());
    }

    public function testIsLessThan(): void
    {
        static::assertTrue(Money::EUR(5.99)->isLessThan(Money::EUR(6.00)), '5.99 < 6.00');
        static::assertFalse(Money::EUR(6.00)->isLessThan(Money::EUR(5.99)), '6.00 < 5.99');
        static::assertTrue(Money::EUR(3.00)->isLessThan(Money::EUR(3.0051)), '3.00 < 3.00');
        static::assertFalse(Money::EUR(3.00)->isLessThan(Money::EUR(3.0025)), '3.00 < 3.0025');
        static::assertFalse(Money::EUR(3.00)->isLessThan(Money::EUR(3.0025)), '3.00 < 3.0051');
        static::assertTrue(Money::EUR(-0.99)->isLessThan(Money::EUR(-0.98)), '-0.99 < -0.98');
    }

    public function testIsBiggerOrEqual(): void
    {
        static::assertFalse(Money::EUR(5.99)->isBiggerOrEqual(Money::EUR(6.00)), '5.99 >= 6.00');
        static::assertTrue(Money::EUR(6.00)->isBiggerOrEqual(Money::EUR(5.99)), '6.00 >= 5.99');
        static::assertTrue(Money::EUR(2.9951)->isBiggerOrEqual(Money::EUR(3.00)), '2.9951 >= 3.00');
        static::assertTrue(Money::EUR(3.00)->isBiggerOrEqual(Money::EUR(3.00)), '3.00 >= 3.00');
        static::assertFalse(Money::EUR(-0.99)->isBiggerOrEqual(Money::EUR(-0.98)), '-0.99 >= -0.98');
    }

    public function testIsEqual(): void
    {
        static::assertTrue(Money::EUR(5.00)->isEqual(Money::EUR(5.0)), '5.00 == 5.00');
        static::assertTrue(Money::EUR(5.00)->isEqual(Money::EUR(5.0025)), '5.00 == 5.0025');
        static::assertTrue(Money::EUR(5.0025)->isEqual(Money::EUR(5.0025)), '5.0025 == 5.0025');
        static::assertFalse(Money::EUR(5.0051)->isEqual(Money::EUR(5.00)), '5.0051 != 5.0025');
        static::assertTrue(Money::EUR(5.0051)->isEqual(Money::EUR(5.0081)), '5.0051 == 5.0081');
        static::assertFalse(Money::EUR(6.00)->isEqual(Money::EUR(5.0)), '6.00 != 6.00');

        static::assertTrue(Money::EUR(5.00000000001)->isEqual(Money::EUR(5.0)));
        static::assertFalse(Money::EUR(5.99)->isEqual(Money::EUR(6.0)));
    }

    public function testSameCurrency(): void
    {
        $moneyA = Money::create(1.0, 'EUR');
        $moneyB = Money::create(4.0, 'EUR');
        static::assertTrue($moneyB->hasSameCurrencyAs($moneyA));
    }
}

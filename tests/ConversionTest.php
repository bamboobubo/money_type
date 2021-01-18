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

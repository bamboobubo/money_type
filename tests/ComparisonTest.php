<?php

namespace Re2bit\Types\Tests;

use PHPUnit\Framework\TestCase;
use Re2bit\Types\Currency;
use Re2bit\Types\Money;
use Re2bit\Types\PrecisionException;

class ComparisonTest extends TestCase
{
    public function testEquality(): void
    {
        static::assertEquals(
            '123 EUR',
            Money::fromInt(
                123,
                new Currency('EUR')
            )
        );

        static::assertEquals(
            '100 JPY',
            Money::fromInt(
                100,
                new Currency('JPY')
            )
        );
    }

    public function testAlmostEqualTo(): void
    {
        $money1Percision4 = Money::fromInt(
            1235023,
            new Currency('EUR', 4)
        );
        $money2Percision4 = Money::fromInt(
            1235049,
            new Currency('EUR', 4)
        );
        static::assertFalse($money1Percision4->equals($money2Percision4));
        static::assertTrue($money1Percision4->almostEqualTo($money2Percision4, 0, 2));
        static::assertFalse($money1Percision4->notAlmostEqualTo($money2Percision4, 0, 2));

        $money3Percision4 = Money::fromFloat(
            123.50999231,
            new Currency('EUR', 4)
        );
        $money4Percision4 = Money::fromFloat(
            123.50490000001,
            new Currency('EUR', 4)
        );
        static::assertFalse($money3Percision4->equals($money4Percision4));
        static::assertTrue($money3Percision4->almostEqualTo($money4Percision4, 2, 2));
        static::assertFalse($money3Percision4->notAlmostEqualTo($money4Percision4, 2, 2));
        static::assertTrue($money3Percision4->almostEqualTo($money4Percision4, 100));
        static::assertFalse($money3Percision4->notAlmostEqualTo($money4Percision4, 100));
        static::assertFalse($money3Percision4->almostEqualTo($money4Percision4, 50));
        static::assertTrue($money3Percision4->notAlmostEqualTo($money4Percision4, 50));

        $money5Percision2 = Money::fromFloat(
            123.50,
            new Currency('EUR', 2)
        );
        $money6Percision2 = Money::fromFloat(
            123.51,
            new Currency('EUR', 2)
        );
        static::assertFalse($money5Percision2->equals($money6Percision2));
        static::assertFalse($money5Percision2->almostEqualTo($money6Percision2, 0));
        static::assertTrue($money5Percision2->almostEqualTo($money6Percision2, 1));
        static::assertFalse($money5Percision2->notAlmostEqualTo($money6Percision2, 1));
    }

    /**
     * @return array[]
     */
    public function sameCurrencyDataProvider(): array
    {
        $moneyEur16 = Money::fromInt(
            -161234567890123456,
            new Currency(
                'EUR',
                16
            )
        );

        $moneyEur8 = Money::fromInt(
            -1612345678,
            new Currency(
                'EUR',
                8
            )
        );
        $moneyEur = Money::fromInt(
            -1612,
            new Currency(
                'EUR'
            )
        );

        return [
            'eur-eur'     => [$moneyEur,$moneyEur, true],
            'eur-eur8'    => [$moneyEur,$moneyEur8, false],
            'eur-eur16'   => [$moneyEur,$moneyEur16, false],
            'eur8-eur'    => [$moneyEur8,$moneyEur, false],
            'eur8-eur8'   => [$moneyEur8,$moneyEur8, true],
            'eur8-eur16'  => [$moneyEur8,$moneyEur16, false],
            'eur16-eur'   => [$moneyEur16,$moneyEur, false],
            'eur16-eur8'  => [$moneyEur16,$moneyEur8, false],
            'eur16-eur16' => [$moneyEur16,$moneyEur16, true],
        ];
    }

    /**
     * @dataProvider sameCurrencyDataProvider
     */
    public function testIsSameCurrency(Money $moneyA, Money $moneyB, bool $equal): void
    {
        static::assertSame($equal, $moneyA->isSameCurrency($moneyB));
    }

    /**
     * @return array[]
     */
    public function equalsDataProvider(): array
    {
        $moneyEur16 = Money::fromInt(
            -161234567890123456,
            new Currency(
                'EUR',
                16
            )
        );

        $moneyEur8 = Money::fromInt(
            -1612345678,
            new Currency(
                'EUR',
                8
            )
        );
        $moneyEur = Money::fromInt(
            -1612,
            new Currency(
                'EUR'
            )
        );

        $moneyEurB = Money::fromInt(
            -1612,
            new Currency(
                'EUR'
            )
        );

        $moneyEurC = Money::fromInt(
            -1613,
            new Currency(
                'EUR'
            )
        );

        return [
            'eur-eur'     => [$moneyEur, $moneyEur, true, false],
            'eur-eur8'    => [$moneyEur, $moneyEur8, false, true],
            'eur-eur16'   => [$moneyEur, $moneyEur16, false, true],
            'eur8-eur'    => [$moneyEur8, $moneyEur, false, true],
            'eur8-eur8'   => [$moneyEur8, $moneyEur8, true, false],
            'eur8-eur16'  => [$moneyEur8, $moneyEur16, false, true],
            'eur16-eur'   => [$moneyEur16, $moneyEur, false, true],
            'eur16-eur8'  => [$moneyEur16, $moneyEur8, false, true],
            'eur16-eur16' => [$moneyEur16,$moneyEur16, true, false],
            'eur-eurB'    => [$moneyEur,$moneyEurB, true, false],
            'eur-eurC'    => [$moneyEur,$moneyEurC, false, false],
        ];
    }

    /**
     * @dataProvider equalsDataProvider
     */
    public function testEquals(Money $moneyA, Money $moneyB, bool $equal, bool $precisionException): void
    {
        if ($precisionException) {
            $this->expectException(PrecisionException::class);
        }
        static::assertSame($equal, $moneyA->equals($moneyB));
    }

    /**
     * @return array[]
     */
    public function compareDataProvider(): array
    {
        $moneyEur1050 = Money::fromInt(
            1050,
            new Currency(
                'EUR',
            )
        );

        $moneyEur1030 =  Money::fromInt(
            1030,
            new Currency(
                'EUR',
            )
        );

        $moneyEur103031  = Money::fromInt(
            103031,
            new Currency(
                'EUR',
                4
            )
        );

        return [
            'eur1050-1050'     => [$moneyEur1050, $moneyEur1050, 0, false],
            'eur1030-1030'     => [$moneyEur1030, $moneyEur1030, 0, false],
            'eur1050-1030'     => [$moneyEur1050, $moneyEur1030, 1, false],
            'eur1030-1050'     => [$moneyEur1030, $moneyEur1050, -1, false],
            'eur1030-103031'   => [$moneyEur1030, $moneyEur103031, 0, true],
            'eur1050-103031'   => [$moneyEur1050, $moneyEur103031, 1, true],
            'eur103031-103031' => [$moneyEur103031, $moneyEur103031, 0, false],
        ];
    }

    /**
     * @dataProvider compareDataProvider
     */
    public function testCompare(Money $moneyA, Money $moneyB, int $compare, bool $precisionException): void
    {
        if ($precisionException) {
            $this->expectException(PrecisionException::class);
        }
        if ($compare === 0) {
            static::assertFalse($moneyA->lessThan($moneyB));
            static::assertTrue($moneyA->lessThanOrEqual($moneyB));
            static::assertTrue($moneyA->greaterThanOrEqual($moneyB));
            static::assertFalse($moneyA->greaterThan($moneyB));
            static::assertTrue($moneyA->equals($moneyB));
        }
        if ($compare === -1) {
            static::assertTrue($moneyA->lessThan($moneyB));
            static::assertTrue($moneyA->lessThanOrEqual($moneyB));
            static::assertFalse($moneyA->greaterThanOrEqual($moneyB));
            static::assertFalse($moneyA->greaterThan($moneyB));
            static::assertFalse($moneyA->equals($moneyB));
        }
        if ($compare === 1) {
            static::assertFalse($moneyA->lessThan($moneyB));
            static::assertFalse($moneyA->lessThanOrEqual($moneyB));
            static::assertTrue($moneyA->greaterThanOrEqual($moneyB));
            static::assertTrue($moneyA->greaterThan($moneyB));
            static::assertFalse($moneyA->equals($moneyB));
        }
    }
}

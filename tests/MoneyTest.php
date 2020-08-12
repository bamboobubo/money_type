<?php

namespace Re2bit\Types\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use DomainException;
use Fixtures\Doctrine\Entity\MoneyTest\Basket;
use NumberFormatter;
use Re2bit\Types\Currency;
use Re2bit\Types\DBAL\Money\AmountType;
use Re2bit\Types\DBAL\Money\CurrencyType;
use Re2bit\Types\DBAL\Money\MoneyEur16Type;
use Re2bit\Types\DBAL\Money\MoneyEur5Type;
use Re2bit\Types\DBAL\Money\MoneyEur8Type;
use Re2bit\Types\DBAL\Money\MoneyEurType;
use Re2bit\Types\Money;

class MoneyTest extends DoctrineTest
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
        static::assertTrue($money3Percision4->almostEqualTo($money4Percision4, 2));
        static::assertFalse($money3Percision4->notAlmostEqualTo($money4Percision4, 2));

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

    public function testJmsDeserialize(): void
    {
        $serializer = $this->createArrayTransformer();
        /** @var Money $money */
        $money = $serializer->fromArray(
            [
                'amount'   => '123',
                'currency' => [
                    'code'      => 'EUR',
                    'precision' => 2,
                ],
            ],
            Money::class
        );
        static::assertInstanceOf(Money::class, $money);
        static::assertEquals(
            Money::fromFloat(
                1.23,
                new Currency('EUR')
            ),
            $money
        );
    }

    public function testValidationErrorsOnDeserialize(): void
    {
        $serializer = $this->createArrayTransformer();
        /** @var Money $money */
        $money = $serializer->fromArray(
            [
                'amount'   => '123',
                'currency' => [
                    'code'      => 'eur',
                    'precision' => 2,
                ],
            ],
            Money::class
        );
        $validator = $this->createValidator();
        $errors = $validator->validate($money);
        static::assertNotEmpty($errors);
    }

    public function testBasicPersistAndLoad(): void
    {
        $basket = new Basket();
        $basket->setId(1);
        $basket->setMoney(
            Money::fromFloat(
                1.23,
                new Currency(
                    'EUR'
                )
            )
        );
        $this->entityManager->persist($basket);
        $this->entityManager->flush();
        $this->entityManager->clear();
        /** @var Basket $baskedLoaded */
        $baskedLoaded = $this->entityManager->find(Basket::class, 1);

        static::assertInstanceOf(Basket::class, $baskedLoaded);
        static::assertEquals(123, $baskedLoaded->getMoney()->getAmount());
    }

    public function testMatchingOnRepository(): void
    {
        $basket = new Basket();
        $basket->setid(1);
        $basket->setMoney(
            Money::fromFloat(
                1.23,
                new Currency(
                    'EUR'
                )
            )
        );
        $this->entityManager->persist($basket);
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var EntityRepository $repo */
        $repo = $this->entityManager->getRepository(Basket::class);
        static::assertCount(
            1,
            $repo->matching(
                Criteria::create()->where(
                    Criteria::expr()->eq(
                        'money.currency',
                        'EUR'
                    )
                )->andWhere(
                    Criteria::expr()->eq(
                        'money.amount',
                        '123'
                    )
                )
            )
        );

        static::assertCount(
            1,
            $repo->matching(
                Criteria::create()->where(
                    Criteria::expr()->eq(
                        'money.currency',
                        new Currency('EUR')
                    )
                )->andWhere(
                    Criteria::expr()->eq(
                        'money.amount',
                        '123'
                    )
                )
            )
        );
    }

    /**
     * Match in Memory is not possible and not recomended since the Criteria Api only supports
     * scalar Values
     *
     * @return void
     */
    public function testMatchingInMemory(): void
    {
        $basket = new Basket();
        $basket->setid(1);
        $basket->setMoney(
            Money::fromFloat(
                1.23,
                new Currency(
                    'EUR'
                )
            )
        );

        $collection = new ArrayCollection([$basket]);

        // Cannot Compare Objects. Doctrine is limited to Scalar Values. Changes maybe with 3.0 ?
        static::assertCount(
            0,
            $collection->matching(
                Criteria::create()->where(
                    Criteria::expr()->eq(
                        'money',
                        Money::fromFloat(
                            1.23,
                            new Currency('EUR')
                        )
                    )
                )
            )
        );
    }

    public function testMoneyWithKnownCurrencyColumn(): void
    {
        $currencyFormatter = new NumberFormatter('de_DE', NumberFormatter::DECIMAL);
        $basket = new Basket();
        $basket->setid(1);
        $basket->setMoney(
            Money::fromFloat(
                0.00,
                new Currency(
                    'EUR'
                )
            )
        );
        $basket->setMoneyEur(
            Money::fromFloat(
                16.12345678,
                new Currency(
                    'EUR',
                    2
                )
            )
        );

        $basket->setMoneyEur5(
            Money::fromFloat(
                16.1234567890123456,
                new Currency(
                    'EUR',
                    5
                )
            )
        );

        $basket->setMoneyEur8(
            Money::fromFloat(
                16.1234567890123456,
                new Currency(
                    'EUR',
                    8
                )
            )
        );
        $basket->setMoneyEur16(
            Money::fromInt(
                -161234567890123456,
                new Currency(
                    'EUR',
                    16
                )
            )
        );
        $this->entityManager->persist($basket);
        $this->entityManager->flush();
        $this->entityManager->clear();

        /** @var Basket $basketReloaded */
        $basketReloaded = $this->entityManager->getRepository(Basket::class)->find(1);

        static::assertTrue(
            $basketReloaded
                ->getMoneyEur()
                ->equals(
                    Money::fromDecimalString('16.12', new Currency('EUR'))
                )
        );

        static::assertTrue(
            $basketReloaded
                ->getMoneyEur5()
                ->equals(
                    Money::fromFloat(
                        16.12346, // .123456 becomes .12346 :) since its rounded to 5 precision,
                        new Currency(
                            'EUR',
                            5
                        )
                    )
                )
        );

        static::assertTrue(
            $basketReloaded
                ->getMoneyEur8()
                ->equals(
                    Money::fromFloat(
                        16.12345679, // .12345678 becomes .12345679 :) since its rounded to 8 precision,
                        new Currency(
                            'EUR',
                            8
                        )
                    )
                )
        );

        static::assertTrue(
            $basketReloaded
                ->getMoneyEur16()
                ->equals(Money::fromInt(
                    -161234567890123, //SQLite Limit
                    new Currency(
                        'EUR',
                        16
                    )
                ))
        );
    }

    public function registerType(): void
    {
        Type::addType('money_amount', AmountType::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_amount', 'money_amount');

        Type::addType('money_eur', MoneyEurType::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_eur', 'money_eur');

        Type::addType('money_eur5', MoneyEur5Type::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_eur5', 'money_eur5');

        Type::addType('money_eur8', MoneyEur8Type::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_eur8', 'money_eur8');

        Type::addType('money_eur16', MoneyEur16Type::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_eur16', 'money_eur16');

        Type::addType('money_currency', CurrencyType::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_currency', 'money_currency');
    }
}

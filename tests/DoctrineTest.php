<?php

namespace Re2bit\Types\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Fixtures\Doctrine\Entity\DoctrineTest\Basket;
use Re2bit\Common\Collections\ComparableRegistryInterface;
use Re2bit\Types\Currency;
use Re2bit\Types\Money;
use Re2bit\Types\MoneyEmbeddable;

class DoctrineTest extends AbstractDoctrineTest
{
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
            MoneyEmbeddable::fromFloat(
                1.23,
                new Currency(
                    'EUR'
                )
            )
        );

        $basket->setMoneyEur(
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

        /** @var EntityRepository<Basket> $repo */
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

        static::assertCount(
            1,
            $repo->matching(
                Criteria::create()->where(
                    Criteria::expr()->eq(
                        'moneyEur',
                        Money::fromFloat(
                            1.23,
                            new Currency(
                                'EUR'
                            )
                        )
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
        if (!interface_exists(ComparableRegistryInterface::class)) {
            $this->expectNotToPerformAssertions();
            return;
        }

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

        /**
         * Cannot Compare Objects. Doctrine is limited to Scalar Values. Changes maybe with 3.0 ?
         * https://github.com/doctrine/collections/issues/260
         * use ```composer require re2bit/collections``` to replace with a collection that support Comparable
         */
        static::assertCount(
            1,
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
}

# bamboobubo/money_type

[![Build Status](https://travis-ci.org/bamboobubo/money_type.svg?branch=master)](https://travis-ci.org/bamboobubo/money_type)

[![Build Status](https://github.com/bamboobubo/money_type/workflows/Testing%20Money/badge.svg)](https://github.com/bamboobubo/money_type)

## !!! Work in Progress !!!

## Introduction

This Library allows u to use the PHP Money Object with Doctrine ORM and every desired precision.

###From String
```php
$currencyFormatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
$money = Money::fromFormattedString('1,23 €', $currencyFormatter);
```
###From Float
```php
$money = Money::fromFloat(3.0, new Currency('EUR'));
```
###From Int
```php
$money = Money::fromInt(123, new Currency('EUR'));
```

### Almost Equal
```php
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
```

### Doctrine
```php
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
```
#### Doctrine Value Objects
```php
/**
 * @ORM\Embedded(class="Re2bit\Types\Money")
 */
protected Money $money;

/**
 * @var Money
 * @ORM\Column(type="money_eur", nullable=true)
 */
protected Money $moneyEur;

/**
 * @var Money
 * @ORM\Column(type="money_eur5", nullable=true)
 */
protected Money $moneyEur5;

/**
 * @var Money
 * @ORM\Column(type="money_eur8", nullable=true)
 */
protected Money $moneyEur8;

/**
 * @var Money
 * @ORM\Column(type="money_eur16", nullable=true)
 */
protected Money $moneyEur16;
```
#### Type Mapping
```php
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
```

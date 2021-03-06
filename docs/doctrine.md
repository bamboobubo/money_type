---
title: Doctrine
permalink: /doctrine/
---
Doctrine
========================

### Basic load and save
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

[back](/)

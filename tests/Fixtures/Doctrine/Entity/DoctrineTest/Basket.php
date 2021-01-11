<?php

namespace Fixtures\Doctrine\Entity\DoctrineTest;

use Doctrine\ORM\Mapping as ORM;
use Re2bit\Types\Money;
use Re2bit\Types\MoneyEmbeddable;

/**
 * @ORM\Entity()
 */
class Basket
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Embedded(class=MoneyEmbeddable::class)
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

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return Money
     */
    public function getMoney(): Money
    {
        return $this->money;
    }

    /**
     * @param Money $money
     */
    public function setMoney(Money $money): void
    {
        $this->money = $money;
    }

    public function getMoneyEur(): Money
    {
        return $this->moneyEur;
    }

    public function setMoneyEur(Money $moneyEur): void
    {
        $this->moneyEur = $moneyEur;
    }

    public function getMoneyEur5(): Money
    {
        return $this->moneyEur5;
    }

    public function setMoneyEur5(Money $moneyEur5): void
    {
        $this->moneyEur5 = $moneyEur5;
    }

    public function getMoneyEur8(): Money
    {
        return $this->moneyEur8;
    }

    public function setMoneyEur8(Money $moneyEur8): void
    {
        $this->moneyEur8 = $moneyEur8;
    }

    public function getMoneyEur16(): Money
    {
        return $this->moneyEur16;
    }

    public function setMoneyEur16(Money $moneyEur16): void
    {
        $this->moneyEur16 = $moneyEur16;
    }
}

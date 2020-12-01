<?php

namespace Re2bit\Types\Tests\Fixtures\Doctrine\Entity\SymfonySerializerTest;

use Re2bit\Types\Money;
use Re2bit\Types\Symfony\Component\Serializer\Annotation;

class Basket
{
    public ?Money $moneyArray = null;

    /**
     * @Annotation\Money(type="DECIMAL", code="EUR", precision="6")
     */
    public ?Money $moneyDecimal = null;
}

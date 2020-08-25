<?php

namespace Fixtures\Doctrine\Entity\JmsTest;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Re2bit\Types\Money;

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
     * @Serializer\Type("Re2bit\Types\Money<'decimal', 'EUR', 6>")
     * @ORM\Embedded(class="Re2bit\Types\Money")
     */
    public ?Money $moneyDecimal;

    /**
     * @Serializer\Type("Re2bit\Types\Money<'string', 'DE_de'>")
     * @ORM\Embedded(class="Re2bit\Types\Money")
     */
    public ?Money $moneyString;

    /**
     * @Serializer\Type("Re2bit\Types\Money<'integer', 'EUR'>")
     * @ORM\Embedded(class="Re2bit\Types\Money")
     */
    public ?Money $moneyInteger;

    /**
     * @Serializer\Type("Re2bit\Types\Money<'float', 'EUR'>")
     * @ORM\Embedded(class="Re2bit\Types\Money")
     */
    public ?Money $moneyFloat;
}

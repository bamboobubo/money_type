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
    public ?Money $moneyString;
}

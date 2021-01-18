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

namespace Fixtures\Doctrine\Entity\JmsSerializerTest;

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
     */
    public ?Money $moneyDecimal;

    /**
     * @Serializer\Type("Re2bit\Types\Money<'string', 'DE_de'>")
     */
    public ?Money $moneyString;

    /**
     * @Serializer\Type("Re2bit\Types\Money<'integer', 'EUR'>")
     */
    public ?Money $moneyInteger;

    /**
     * @Serializer\Type("Re2bit\Types\Money<'float', 'EUR'>")
     */
    public ?Money $moneyFloat;

    /**
     * @Serializer\Type("Re2bit\Types\Money<'array'>")
     */
    public ?Money $moneyArray;
}

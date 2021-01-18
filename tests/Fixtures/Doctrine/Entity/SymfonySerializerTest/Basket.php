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

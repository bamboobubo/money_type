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

namespace Re2bit\Types\Doctrine\DBAL\Money;

class MoneyEur8Type extends MoneyEurType
{
    public const NAME = 'money_eur8';
    public const PRECISION = 8;
}

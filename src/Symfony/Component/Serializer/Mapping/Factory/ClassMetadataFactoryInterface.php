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

namespace Re2bit\Types\Symfony\Component\Serializer\Mapping\Factory;

use Re2bit\Types\Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

interface ClassMetadataFactoryInterface
{
    /**
     * @psalm-param class-string|object $value
     * @param string|object $value
     *
     * @throws InvalidArgumentException
     * @return ClassMetadataInterface
     *
     */
    public function getMetadataFor($value);

    /**
     * Checks if class has metadata.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function hasMetadataFor($value);
}

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

namespace Re2bit\Types\Symfony\Component\Serializer\Mapping;

use ReflectionClass;

interface ClassMetadataInterface
{
    /**
     * @psalm-return class-string
     */
    public function getName(): string;

    public function addAttributeMetadata(AttributeMetadataInterface $attributeMetadata): void;

    /**
     * @return AttributeMetadataInterface[]
     */
    public function getAttributesMetadata(): array;

    /**
     * @return ReflectionClass<object>
     */
    public function getReflectionClass(): ReflectionClass;
}

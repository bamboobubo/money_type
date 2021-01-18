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

/**
 * Stores metadata needed for serializing and deserializing attributes with Money Annotation.
 */
interface AttributeMetadataInterface
{
    public const TYPE_ARRAY = 'ARRAY';
    public const TYPE_DECIMAL = 'DECIMAL';
    public const TYPE_STRING = 'STRING';
    public const TYPE_INTEGER = 'INTEGER';
    public const TYPE_FLOAT = 'FLOAT';

    public function getName(): string;

    public function getCode(): ?string;

    public function getType(): string;

    public function getLocale(): ?string;

    public function getPrecision(): ?int;

    public function setName(string $name): void;

    public function setCode(?string $code): void;

    public function setType(string $type): void;

    public function setLocale(?string $locale): void;

    public function setPrecision(?int $precision): void;
}

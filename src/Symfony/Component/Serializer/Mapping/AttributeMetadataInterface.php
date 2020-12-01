<?php

namespace Re2bit\Types\Symfony\Component\Serializer\Mapping;

/**
 * Stores metadata needed for serializing and deserializing attributes with Money Annotation.
 *
 * @author René Gerritsen <rene.gerritsen@me.com>
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

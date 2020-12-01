<?php

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

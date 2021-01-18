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

class ClassMetadata implements ClassMetadataInterface
{
    /**
     * @psalm-var class-string
     */
    public string $name;

    /**
     * @var AttributeMetadataInterface[]
     */
    public array $attributesMetadata = [];

    /**
     * @var ReflectionClass<object>|null
     */
    private ?ReflectionClass $reflClass = null;

    /**
     * @psalm-param class-string $class
     */
    public function __construct(string $class)
    {
        $this->name = $class;
    }

    /**
     * @psalm-return class-string
     * @return  string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function addAttributeMetadata(AttributeMetadataInterface $attributeMetadata): void
    {
        $this->attributesMetadata[$attributeMetadata->getName()] = $attributeMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributesMetadata(): array
    {
        return $this->attributesMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getReflectionClass(): ReflectionClass
    {
        if (!$this->reflClass) {
            $this->reflClass = new ReflectionClass($this->getName());
        }

        return $this->reflClass;
    }

    /**
     * Returns the names of the properties that should be serialized.
     *
     * @return string[]
     */
    public function __sleep()
    {
        return [
            'name',
            'attributesMetadata',
        ];
    }
}

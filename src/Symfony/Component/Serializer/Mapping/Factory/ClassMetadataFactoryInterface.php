<?php

namespace Re2bit\Types\Symfony\Component\Serializer\Mapping\Factory;

use Re2bit\Types\Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
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

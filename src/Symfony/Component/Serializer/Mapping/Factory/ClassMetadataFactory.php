<?php

namespace Re2bit\Types\Symfony\Component\Serializer\Mapping\Factory;

use function get_class;
use function is_object;
use function is_string;
use Re2bit\Types\Symfony\Component\Serializer\Mapping\ClassMetadata;
use Re2bit\Types\Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Re2bit\Types\Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class ClassMetadataFactory implements ClassMetadataFactoryInterface
{
    private LoaderInterface $loader;

    /**
     * @var ClassMetadataInterface[]
     */
    private array $loadedClasses;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFor($value)
    {
        $class = $this->getClass($value);

        if (isset($this->loadedClasses[$class])) {
            return $this->loadedClasses[$class];
        }

        $classMetadata = new ClassMetadata($class);
        $this->loader->loadClassMetadata($classMetadata);

        $reflectionClass = $classMetadata->getReflectionClass();

        // Include metadata from the parent class
        if ($parent = $reflectionClass->getParentClass()) {
            //$classMetadata->merge($this->getMetadataFor($parent->name));
        }

        // Include metadata from all implemented interfaces
        foreach ($reflectionClass->getInterfaces() as $interface) {
            //$classMetadata->merge($this->getMetadataFor($interface->name));
        }

        return $this->loadedClasses[$class] = $classMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetadataFor($value)
    {
        return is_object($value) || (is_string($value) && (class_exists($value) || interface_exists($value, false)));
    }

    /**
     * @param object|string $value
     * @psalm-param object|class-string $value
     *
     * @return string
     * @psalm-return class-string
     */
    private function getClass($value): string
    {
        if (is_string($value)) {
            if (!class_exists($value) && !interface_exists($value, false)) {
                throw new InvalidArgumentException(sprintf('The class or interface "%s" does not exist.', $value));
            }

            /* @phpstan-ignore-next-line */
            return ltrim($value, '\\');
        }

        if (!is_object($value)) {
            throw new InvalidArgumentException(sprintf('Cannot create metadata for non-objects. Got: "%s".', get_debug_type($value)));
        }

        return get_class($value);
    }
}

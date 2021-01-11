<?php

namespace Re2bit\Types\Symfony\Component\Serializer\Mapping\Loader;

use Doctrine\Common\Annotations\Reader;
use Re2bit\Types\Symfony\Component\Serializer\Annotation\Money;
use Re2bit\Types\Symfony\Component\Serializer\Mapping\AttributeMetadata;
use Re2bit\Types\Symfony\Component\Serializer\Mapping\ClassMetadataInterface;

/**
 * Annotation loader.
 *
 * @author René Gerritsen <rene.gerritsen@me.com>
 * @SuppressWarnings(PHPMD.CyclomaticComplexity")
 * @SuppressWarnings(PHPMD.NPathComplexity")
 */
class AnnotationLoader implements LoaderInterface
{
    private Reader $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        $reflectionClass = $classMetadata->getReflectionClass();
        $className = $reflectionClass->name;
        $loaded = false;

        $attributesMetadata = $classMetadata->getAttributesMetadata();

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->getDeclaringClass()->name === $className) {
                foreach ($this->reader->getPropertyAnnotations($property) as $annotation) {
                    if ($annotation instanceof Money) {
                        if (!isset($attributesMetadata[$property->name])) {
                            $attributesMetadata[$property->name] = new AttributeMetadata($property->name);
                            $classMetadata->addAttributeMetadata($attributesMetadata[$property->name]);
                        }
                        $attributesMetadata[$property->name]->setCode($annotation->getCode());
                        $attributesMetadata[$property->name]->setType($annotation->getType());
                        $attributesMetadata[$property->name]->setPrecision($annotation->getPrecision());
                        $attributesMetadata[$property->name]->setLocale($annotation->getLocale());
                    }
                    $loaded = true;
                }
            }
        }

        foreach ($reflectionClass->getMethods() as $method) {
            if ($method->getDeclaringClass()->name !== $className) {
                continue;
            }

            foreach ($this->reader->getMethodAnnotations($method) as $annotation) {
                $attributeMetadata = null;

                $accessorOrMutator = preg_match('/^(get|set)(.+)$/i', $method->name, $matches);
                if ($accessorOrMutator) {
                    $attributeName = lcfirst($matches[2]);

                    if (isset($attributesMetadata[$attributeName])) {
                        $attributeMetadata = $attributesMetadata[$attributeName];
                    } else {
                        $attributesMetadata[$attributeName] = $attributeMetadata = new AttributeMetadata($attributeName);
                        $classMetadata->addAttributeMetadata($attributeMetadata);
                    }
                }

                if (null === $attributeMetadata) {
                    continue;
                }

                if ($annotation instanceof Money) {
                    $attributeMetadata->setCode($annotation->getCode());
                    $attributeMetadata->setType($annotation->getType());
                    $attributeMetadata->setPrecision($annotation->getPrecision());
                    $attributeMetadata->setLocale($annotation->getLocale());
                }

                $loaded = true;
            }
        }

        return $loaded;
    }
}

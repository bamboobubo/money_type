<?php

namespace Re2bit\Types\Symfony\Component\Serializer\ContextProvider;

use function is_object;
use Re2bit\Types\Money;
use Re2bit\Types\Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Encoder\NormalizationAwareInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Traversable;

class MoneyContextProvider implements
    NormalizerInterface,
    DenormalizerInterface,
    CacheableSupportsMethodInterface,
    DenormalizerAwareInterface,
    NormalizationAwareInterface
{
    use NormalizerAwareTrait, DenormalizerAwareTrait;

    private const ALREADY_CALLED = 'MONEY_CONTEXT_PROVIDER_ALREADY_CALLED';

    protected ClassMetadataFactoryInterface $classMetadataFactory;

    protected ?NameConverterInterface $nameConverter;

    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory,
        NameConverterInterface $nameConverter = null
    ) {
        $this->classMetadataFactory = $classMetadataFactory;
        $this->nameConverter = $nameConverter;
    }

    /**
     * @param mixed       $object
     * @param string|null $format
     * @param mixed[]     $context
     *
     * @return mixed[]
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Money) {
            return [];
        }
        return $object->toArray();
    }

    public function supportsNormalization($data, string $format = null)
    {
        return is_object($data) && !$data instanceof Traversable;
    }

    /**
     *
     * @psalm-param class-string $type
     *
     * @param mixed       $data
     * @param string      $type
     * @param string|null $format
     * @param mixed[]     $context
     *
     * @throws ExceptionInterface
     * @return mixed
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $metadata = $this->classMetadataFactory->getMetadataFor($type);
        $context[self::ALREADY_CALLED][md5(serialize($data))] = true;
        $attributeContextEmpty = !isset($context[AbstractNormalizer::ATTRIBUTES]);
        if ($attributeContextEmpty) {
            $context[AbstractNormalizer::ATTRIBUTES] = [];
        }
        foreach ($data as $propertyName => $propertyValue) {
            if ($attributeContextEmpty) {
                if ($this->nameConverter !== null) {
                    $propertyName = $this->nameConverter->denormalize($propertyName);
                }
                $context[AbstractNormalizer::ATTRIBUTES][$propertyName] = true;
            }
        }
        foreach ($metadata->getAttributesMetadata() as $attribute) {
            if ($attributeContextEmpty) {
                $context[AbstractNormalizer::ATTRIBUTES][$attribute->getName()] = $attribute;
            }
        }
        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    /**
     * @param mixed       $data
     * @param string      $type
     * @param string|null $format
     * @param mixed[]       $context
     *
     * @return bool
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED][md5(serialize($data))])) {
            return false;
        }
        if (!class_exists($type)) {
            return false;
        }

        $metadata = $this->classMetadataFactory->getMetadataFor($type);
        return !empty($metadata->getAttributesMetadata());
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return false;
    }
}

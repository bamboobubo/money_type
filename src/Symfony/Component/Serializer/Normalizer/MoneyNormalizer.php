<?php

namespace Re2bit\Types\Symfony\Component\Serializer\Normalizer;

use Re2bit\Types\Currency;
use Re2bit\Types\Money;
use Re2bit\Types\Symfony\Component\Serializer\Mapping\AttributeMetadataInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    protected ?ClassMetadataFactoryInterface $classMetadataFactory;

    protected ?NameConverterInterface $nameConverter;

    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory = null,
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
        return $data instanceof Money;
    }

    /**
     * @param mixed       $data
     * @param string      $type
     * @param string|null $format
     * @param mixed[]     $context
     *
     * @return Money
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): Money
    {
        $metadata = $context[AbstractNormalizer::ATTRIBUTES] ?? false;
        if ($metadata instanceof AttributeMetadataInterface) {
            switch ($metadata->getType()) {
                case AttributeMetadataInterface::TYPE_ARRAY:
                    return Money::fromArray($data);
                case AttributeMetadataInterface::TYPE_DECIMAL:
                    return Money::fromDecimalString(
                        $data,
                        new Currency(
                            $metadata->getCode() ?: '', // it is null just provide invalid str to enforce Exception
                            $metadata->getPrecision()
                        )
                    );
            }
        }
        return Money::fromArray($data);
    }


    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return $type === Money::class;
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }
}

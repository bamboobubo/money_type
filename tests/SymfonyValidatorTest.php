<?php

namespace Re2bit\Types\Tests;

use DomainException;
use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use Re2bit\Types\MetadataLoader\JmsMetadataDirectoryFactory;
use Re2bit\Types\MetadataLoader\SymfonyValidatorLoaderFactory;
use Re2bit\Types\Money;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

class SymfonyValidatorTest extends TestCase
{
    public function testValidationErrorsOnDeserialize(): void
    {
        $serializer = $this->createArrayTransformer();
        /** @var Money $money */
        $money = $serializer->fromArray(
            [
                'amount'   => '123',
                'currency' => [
                    'code'      => 'eur',
                    'precision' => 2,
                ],
            ],
            Money::class
        );
        $validator = $this->createValidator();
        $errors = $validator->validate($money);
        static::assertNotEmpty($errors);
    }

    protected function createArrayTransformer(): ArrayTransformerInterface
    {
        $serializerBuilder = SerializerBuilder::create();
        $serializerBuilder->addMetadataDir(JmsMetadataDirectoryFactory::create());
        $arrayTransformer = $serializerBuilder->build();
        if (!$arrayTransformer instanceof ArrayTransformerInterface) {
            throw new DomainException('No Array Transformer available');
        }
        return $arrayTransformer;
    }

    /**
     * @return RecursiveValidator|ValidatorInterface
     */
    protected function createValidator()
    {
        $builder = new ValidatorBuilder();
        $builder->addLoader(
            SymfonyValidatorLoaderFactory::create()
        );

        return $builder->getValidator();
    }
}

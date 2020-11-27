<?php

namespace Re2bit\Types\Tests;

use PHPUnit\Framework\TestCase;
use Re2bit\Types\Currency;
use Re2bit\Types\Money;
use Re2bit\Types\Symfony\Component\Serializer\Normalizer\MoneyNormalizer;
use Re2bit\Types\Tests\Fixtures\Doctrine\Entity\SymfonySerializerTest\Basket;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SymfonySerializerTest extends TestCase
{
    public function testSymfonyNormalize(): void
    {
        $serializer = $this->createSerializer();
        $normalizer = $this->createDenormalizer();

        $data = [
            'amount'   => 1.23,
            'currency' => [
                'code'      => 'EUR',
                'precision' => 2,
            ],
        ];

        /** @var Money $moneyFromArray */
        $moneyFromArray = $normalizer->denormalize(
            $data,
            Money::class
        );
        $expected = Money::fromFloat(
            1.23,
            new Currency('EUR')
        );
        $moneyFromJson = $serializer->deserialize(
            json_encode($data),
            Money::class,
            'json'
        );
        static::assertInstanceOf(Money::class, $moneyFromArray);
        static::assertInstanceOf(Money::class, $moneyFromJson);
        static::assertEquals($expected, $moneyFromJson);
        static::assertEquals($expected, $moneyFromArray);
    }

    public function testDeserializeOfDecimalCurrency(): void
    {
        $json = '{"money_array": {"amount": 12.443312, "currency": {"code": "EUR", "precision": 6}}}';
        $serializer = $this->createSerializer();
        /** @var Basket $model */
        $model = $serializer->deserialize($json, Basket::class, 'json');
        static::assertInstanceOf(Money::class, $model->moneyArray);
        if ($model->moneyArray instanceof Money) {
            static::assertSame(12.443312, $model->moneyArray->toFloat());
            static::assertEquals('EUR', $model->moneyArray->getCurrency()->getCodeWithoutPrecision());
            static::assertEquals(6, $model->moneyArray->getCurrency()->getPrecision());
        }
    }

    private function createNormalizer(): NormalizerInterface
    {
        return new MoneyNormalizer();
    }

    private function createDenormalizer(): DenormalizerInterface
    {
        return new MoneyNormalizer();
    }

    private function createSerializer(): SerializerInterface
    {
        return new Serializer(
            [
                $this->createNormalizer(),
                new PropertyNormalizer(
                    null,
                    new CamelCaseToSnakeCaseNameConverter(),
                    new ReflectionExtractor()
                ),
            ],
            [new JsonEncoder()]
        );
    }
}

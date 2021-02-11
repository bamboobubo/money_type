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

namespace Re2bit\Types\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\DocParser;
use PHPUnit\Framework\TestCase;
use Re2bit\Types\Currency;
use Re2bit\Types\Money;
use Re2bit\Types\Symfony\Component\Serializer\ContextProvider\MoneyContextProvider;
use Re2bit\Types\Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Re2bit\Types\Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Re2bit\Types\Symfony\Component\Serializer\Normalizer\MoneyNormalizer;
use Re2bit\Types\Tests\Fixtures\Doctrine\Entity\SymfonySerializerTest\Basket;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SymfonySerializerTest extends TestCase
{
    public function testSymfonyNormalize(): void
    {
        $serializer = $this->createSerializer();
        $normalizer = new MoneyNormalizer();

        $data = [
            'amount'   => 1.23,
            'currency' => [
                'code'      => 'EUR',
                'precision' => 2,
            ],
        ];

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
        $json = json_encode([
            'money_array' => [
                'amount'   => 12.4433124,
                'currency' => [
                    'code'      => 'EUR',
                    'precision' => 6,
                ],
            ],
            'money_decimal' => 12.443312,
        ]);
        $serializer = $this->createSerializer();
        /** @var Basket $model */
        $model = $serializer->deserialize($json, Basket::class, 'json');
        static::assertInstanceOf(Money::class, $model->moneyArray);
        if ($model->moneyArray) {
            static::assertSame(12.443312, $model->moneyArray->toFloat());
            static::assertEquals('EUR', $model->moneyArray->getCurrency()->getCodeWithoutPrecision());
            static::assertEquals(6, $model->moneyArray->getCurrency()->getPrecision());
        }

        static::assertInstanceOf(Money::class, $model->moneyDecimal);
        if ($model->moneyDecimal) {
            static::assertSame(12.443312, $model->moneyDecimal->toFloat());
            static::assertEquals('EUR', $model->moneyDecimal->getCurrency()->getCodeWithoutPrecision());
            static::assertEquals(6, $model->moneyDecimal->getCurrency()->getPrecision());
        }
    }

    private function createSerializer(): SerializerInterface
    {
        return new Serializer(
            [
                new MoneyContextProvider(
                    new ClassMetadataFactory(
                        new AnnotationLoader(
                            new AnnotationReader(
                                new DocParser()
                            )
                        )
                    ),
                    new CamelCaseToSnakeCaseNameConverter(),
                ),
                new MoneyNormalizer(),
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

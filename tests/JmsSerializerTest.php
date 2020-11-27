<?php

namespace Re2bit\Types\Tests;

use DomainException;
use Fixtures\Doctrine\Entity\JmsSerializerTest\Basket;
use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Re2bit\Types\Currency;
use Re2bit\Types\Jms\Handler\InvalidCurrencyException;
use Re2bit\Types\Jms\Handler\InvalidPrecisionException;
use Re2bit\Types\Jms\Handler\MoneyHandler;
use Re2bit\Types\MetadataLoader\JmsMetadataDirectoryFactory;
use Re2bit\Types\Money;

class JmsSerializerTest extends AbstractDoctrineTest
{
    public function testJmsDeserialize(): void
    {
        $serializer = $this->createArrayTransformer();
        /** @var Money $money */
        $money = $serializer->fromArray(
            [
                'amount'   => '123',
                'currency' => [
                    'code'      => 'EUR',
                    'precision' => 2,
                ],
            ],
            Money::class
        );
        static::assertInstanceOf(Money::class, $money);
        static::assertEquals(
            Money::fromFloat(
                1.23,
                new Currency('EUR')
            ),
            $money
        );
    }

    /**
     * @return array<string,array<string>>
     */
    public function deserializeOfDecimalCurrencyDataProvider(): array
    {
        return [
            'decimalEncodedAsString' => ['{"money_decimal": "12.443312"}'],
            'decimalEncodedAsFloat'  => ['{"money_decimal": 12.443312}'],
        ];
    }

    /**
     * @dataProvider deserializeOfDecimalCurrencyDataProvider
     */
    public function testDeserializeOfDecimalCurrency(string $json): void
    {
        $serializer = $this->createSerializer();
        /** @var Basket $model */
        $model = $serializer->deserialize($json, Basket::class, 'json');
        $moneyString = $model->moneyDecimal;
        static::assertInstanceOf(Money::class, $moneyString);
        if ($moneyString instanceof Money) {
            static::assertSame(12.443312, $moneyString->toFloat());
            static::assertEquals('EUR', $moneyString->getCurrency()->getCodeWithoutPrecision());
            static::assertEquals(6, $moneyString->getCurrency()->getPrecision());
        }
    }

    /**
     * @return array<string,array<string>>
     */
    public function deserializeFormattedStringDataProvider(): array
    {
        return [
            'formattedStringEUR' => [
                "{\"money_string\": \"1,23\xc2\xa0€\"}",
                'EUR',
            ],
            'formattedStringUSD' => [
                "{\"money_string\": \"1,23\xc2\xa0$\"}",
                'USD',
            ],
            'formattedStringEURWhitespace' => [
                '{"money_string": "1,23 €"}',
                'EUR',
            ],
            'formattedStringUSDWhitespace' => [
                '{"money_string": "1,23 $"}',
                'USD',
            ],
        ];
    }

    /**
     * @dataProvider deserializeFormattedStringDataProvider
     */
    public function testDeserializeOfFormattedString(string $json, string $expectedCurrencyCode): void
    {
        $serializer = $this->createSerializer();
        /** @var Basket $model */
        $model = $serializer->deserialize($json, Basket::class, 'json');
        $moneyFormattedString = $model->moneyString;
        static::assertInstanceOf(Money::class, $moneyFormattedString);
        if ($moneyFormattedString instanceof Money) {
            static::assertSame(1.23, $moneyFormattedString->toFloat());
            static::assertEquals($expectedCurrencyCode, $moneyFormattedString->getCurrency()->getCodeWithoutPrecision());
            static::assertEquals(2, $moneyFormattedString->getCurrency()->getPrecision());
        }
    }

    /**
     * @return array<string,array<string>>
     */
    public function deserializeIntegerDataProvider(): array
    {
        return [
            'moneyIntegerAsStringEur' => [
                '{"money_integer": "123"}',
            ],
            'moneyIntegerAsIntegerEur' => [
                '{"money_integer": 123}',
            ],
            'moneyIntegerAsFloat' => [
                '{"money_integer": 123.0}',
            ],
        ];
    }

    /**
     * @dataProvider deserializeIntegerDataProvider
     */
    public function testDeserializeOfInteger(string $json): void
    {
        $serializer = $this->createSerializer();
        /** @var Basket $model */
        $model = $serializer->deserialize($json, Basket::class, 'json');
        $moneyInteger = $model->moneyInteger;
        static::assertInstanceOf(Money::class, $moneyInteger);
        if ($moneyInteger instanceof Money) {
            static::assertSame(1.23, $moneyInteger->toFloat());
            static::assertEquals('EUR', $moneyInteger->getCurrency()->getCodeWithoutPrecision());
            static::assertEquals(2, $moneyInteger->getCurrency()->getPrecision());
        }
    }

    /**
     * @return array<string,array<mixed>>
     */
    public function deserializeFloatDataProvider(): array
    {
        return [
            'moneyFloatAsStringEur' => [
                '{"money_float": "1.23"}',
                1.23,
            ],
            'moneyFloatAsIntegerEur' => [
                '{"money_float": 13}',
                13.0,
            ],
            'moneyFloatAsFloat' => [
                '{"money_float": 1.23}',
                1.23,
            ],
        ];
    }

    /**
     * @dataProvider deserializeFloatDataProvider
     *
     * @param string $json
     * @param float  $expectedResult
     */
    public function testDeserializeOfFloat(string $json, float $expectedResult): void
    {
        $serializer = $this->createSerializer();
        /** @var Basket $model */
        $model = $serializer->deserialize($json, Basket::class, 'json');
        $moneyFloat = $model->moneyFloat;
        static::assertInstanceOf(Money::class, $moneyFloat);
        if ($moneyFloat instanceof Money) {
            static::assertSame($expectedResult, $moneyFloat->toFloat());
            static::assertEquals('EUR', $moneyFloat->getCurrency()->getCodeWithoutPrecision());
            static::assertEquals(2, $moneyFloat->getCurrency()->getPrecision());
        }
    }

    /**
     * @return array<string,array<mixed>>
     */
    public function deserializeArrayDataProvider(): array
    {
        return [
            'moneyFloatAsStringEur' => [
                '{"money_array":{"amount": 1.23,"currency":{"code": "EUR","precision": 2}}}',
                1.23,
            ],
        ];
    }

    /**
     * @dataProvider deserializeArrayDataProvider
     *
     * @param string $json
     * @param float  $expectedResult
     */
    public function testDeserializeOfArray(string $json, float $expectedResult): void
    {
        $serializer = $this->createSerializer();
        /** @var Basket $model */
        $model = $serializer->deserialize($json, Basket::class, 'json');
        $moneyFloat = $model->moneyArray;
        static::assertInstanceOf(Money::class, $moneyFloat);
        if ($moneyFloat instanceof Money) {
            static::assertSame($expectedResult, $moneyFloat->toFloat());
            static::assertEquals('EUR', $moneyFloat->getCurrency()->getCodeWithoutPrecision());
            static::assertEquals(2, $moneyFloat->getCurrency()->getPrecision());
        }
    }

    public function testSerializeMoneyObject(): void
    {
        $serializer = $this->createSerializer();
        $model = new Basket();
        $model->moneyFloat = Money::EUR(123);
        $model->moneyString = Money::EUR(123);
        $model->moneyDecimal = Money::fromInt(1230000, new Currency('EUR', 6));
        $model->moneyInteger = Money::EUR(123);
        $model->moneyArray = Money::EUR(123);
        $json = $serializer->serialize($model, 'json');

        static::assertJson($json);
        static::assertJsonStringEqualsJsonFile(
            __DIR__ . '/Fixtures/Doctrine/Entity/JmsSerializerTest/expected_serialize.json',
            $json
        );
    }

    /**
     * @return array<string,array<mixed>>
     */
    public function invalidMoneyExceptionDataProvider(): array
    {
        return [
            'moneyDecimalWrongCurrency' => [
                'moneyDecimal',
                Money::fromInt(123, new Currency('USD', 6)),
                InvalidCurrencyException::class,
            ],
            'moneyDecimalWrongPrecision' => [
                'moneyDecimal',
                Money::fromInt(123, new Currency('EUR', 2)),
                InvalidPrecisionException::class,
            ],
            'moneyIntWrongCurrency' => [
                'moneyInteger',
                Money::fromInt(123, new Currency('USD', 2)),
                InvalidCurrencyException::class,
            ],
            'moneyIntWrongPrecision' => [
                'moneyInteger',
                Money::fromInt(123, new Currency('EUR', 6)),
                InvalidPrecisionException::class,
            ],
            'moneyFloatWrongCurrency' => [
                'moneyFloat',
                Money::fromInt(123, new Currency('USD', 2)),
                InvalidCurrencyException::class,
            ],
            'moneyFloatWrongPrecision' => [
                'moneyFloat',
                Money::fromInt(123, new Currency('EUR', 6)),
                InvalidPrecisionException::class,
            ],
            'moneyStringWrongPrecision' => [
                'moneyString',
                Money::fromInt(123, new Currency('EUR', 6)),
                InvalidPrecisionException::class,
            ],
        ];
    }

    /**
     * @dataProvider invalidMoneyExceptionDataProvider
     *
     * @param string         $property
     * @param Money $money
     * @param        class-string<\Throwable> $expectedException
     */
    public function testExceptionOnInvalidCurrencyForProperty(string $property, Money $money, string $expectedException): void
    {
        $this->expectException($expectedException);
        $serializer = $this->createSerializer();
        $model = new Basket();
        // Valid once
        $model->moneyFloat = Money::EUR(123);
        $model->moneyString = Money::EUR(123);
        $model->moneyDecimal = Money::fromInt(1230000, new Currency('EUR', 6));
        $model->moneyInteger = Money::EUR(123);
        // overwrite test property
        $model->{$property} = $money;
        $serializer->serialize($model, 'json');
    }

    private function createSerializer(): SerializerInterface
    {
        return SerializerBuilder::create()
           ->addDefaultHandlers()
           ->addMetadataDir(JmsMetadataDirectoryFactory::create())
           ->configureHandlers(
               function (HandlerRegistryInterface $handlerRegistry) {
                   $handlerRegistry->registerSubscribingHandler(
                       new MoneyHandler()
                   );
               }
           )->build();
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
}

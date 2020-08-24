<?php

namespace Re2bit\Types\Tests;

use Fixtures\Doctrine\Entity\JmsTest\Basket;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Re2bit\Types\Currency;
use Re2bit\Types\Jms\Handler\MoneyHandler;
use Re2bit\Types\Money;

class JmsTest extends AbstractDoctrineTest
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

    /**
     * @return array<string,array<string>>
     */
    public function deserializeOfDecimalCurrencyDataProvider(): array
    {
        return [
            'decimalEncodedAsString' => ['{"money_string": "12.443312"}'],
            'decimalEncodedAsFloat'  => ['{"money_string": 12.443312}'],
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
        $moneyString = $model->moneyString;
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
                "{\"money_formatted_string\": \"1,23\xc2\xa0€\"}",
                'EUR',
            ],
            'formattedStringUSD' => [
                "{\"money_formatted_string\": \"1,23\xc2\xa0$\"}",
                'USD',
            ],
            'formattedStringEURWhitespace' => [
                '{"money_formatted_string": "1,23 €"}',
                'EUR',
            ],
            'formattedStringUSDWhitespace' => [
                '{"money_formatted_string": "1,23 $"}',
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
        $moneyFormattedString = $model->moneyFormattedString;
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

    private function createSerializer(): SerializerInterface
    {
        return SerializerBuilder::create()
           ->addDefaultHandlers()
           ->configureHandlers(
               function (HandlerRegistryInterface $handlerRegistry) {
                   $handlerRegistry->registerSubscribingHandler(
                       new MoneyHandler()
                   );
               }
           )->build();
    }
}

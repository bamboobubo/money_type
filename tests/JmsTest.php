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

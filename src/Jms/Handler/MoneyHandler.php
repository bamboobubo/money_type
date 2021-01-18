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

declare(strict_types=1);

namespace Re2bit\Types\Jms\Handler;

use DomainException;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use NumberFormatter;
use Re2bit\Types\Currency;
use Re2bit\Types\Money;

final class MoneyHandler implements SubscribingHandlerInterface
{
    private const MODE_ARRAY = 'array';
    private const MODE_DECIMAL = 'decimal';
    private const MODE_STRING = 'string';
    private const MODE_INTEGER = 'integer';
    private const MODE_FLOAT = 'float';
    private const MODES = [
        self::MODE_ARRAY,
        self::MODE_DECIMAL,
        self::MODE_STRING,
        self::MODE_INTEGER,
        self::MODE_FLOAT,
    ];

    /**
     * {@inheritdoc}
     *
     * @return mixed[]
     */
    public static function getSubscribingMethods(): array
    {
        $methods = [];
        $type = Money::class;

        foreach (['json'] as $format) {
            $methods[] = [
                'type'      => $type,
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format'    => $format,
                'method'    => 'deserialize',
            ];
            $methods[] = [
                'type'      => $type,
                'format'    => $format,
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'method'    => 'serialize',
            ];
        }

        return $methods;
    }

    /**
     * @param mixed[] $type
     *
     * @return mixed
     */
    public function serialize(SerializationVisitorInterface $visitor, Money $money, array $type, SerializationContext $context)
    {
        $mode = $this->getMode($type);
        switch ($mode) {
            case self::MODE_DECIMAL:
                return $this->serializeDecimal($money, $type);
            case self::MODE_STRING:
                return $this->serializeString($money, $type);
            case self::MODE_INTEGER:
                return $this->serializeInteger($money, $type);
            case self::MODE_FLOAT:
                return $this->serializeFloat($money, $type);
            case self::MODE_ARRAY:
                return $this->serializeArray($money);
        }
        throw new DomainException('Mode is not Valid', 1598281688658);
    }

    /**
     * @param Money $money
     * @param mixed[]        $type
     *
     * @return void
     */
    private function assertCurrencyAndPrecisionMatch(Money $money, array $type): void
    {
        $moneyAmount = $money->getAmount();
        $moneyCurrency = $money->getCurrency()->getCodeWithoutPrecision();
        $moneyPrecision = $money->getCurrency()->getPrecision();
        $typePrecision = $this->getPrecision($type);
        $typeCurrency = $this->getCurrency($type);
        if ($moneyPrecision !== $typePrecision) {
            throw new InvalidPrecisionException(
                "Money Precision '{$moneyPrecision}' does not match Type Precision '{$typePrecision}' for '{$moneyAmount}'"
            );
        }

        if ($moneyCurrency !== $typeCurrency) {
            throw new InvalidCurrencyException(
                "Money Currency '{$moneyCurrency}' does not match Type Currency '{$typeCurrency}' for '{$moneyAmount}'"
            );
        }
    }

    private function assertHasIso4217Precision(Money $money): void
    {
        if (!$money->getCurrency()->hasPrecisionOtherThanIso4217()) {
            throw new InvalidPrecisionException(
                "Money has non ISO4217 Precision"
            );
        }
    }

    /**
     * @param Money $money
     * @param mixed[]        $type
     *
     * @return string
     */
    private function serializeDecimal(Money $money, array $type): string
    {
        $this->assertCurrencyAndPrecisionMatch($money, $type);
        $precision = $this->getPrecision($type);
        return $money->toDecimalString($precision);
    }

    /**
     * @param Money $money
     * @param mixed[]        $type
     *
     * @return string
     */
    private function serializeString(Money $money, array $type): string
    {
        $this->assertHasIso4217Precision($money);
        $currencyFormatter = $this->getNumberFormatter($type);
        return $money->toString($currencyFormatter);
    }

    /**
     * @param Money $money
     * @param mixed[]        $type
     *
     * @return int
     */
    private function serializeInteger(Money $money, array $type): int
    {
        $this->assertCurrencyAndPrecisionMatch($money, $type);
        return $money->toInt();
    }

    /**
     * @param Money $money
     * @param mixed[]        $type
     *
     * @return float
     */
    private function serializeFloat(Money $money, array $type): float
    {
        $this->assertCurrencyAndPrecisionMatch($money, $type);
        return round(
            $money->toFloat(),
            $money->getCurrency()->getPrecision(),
            Money::ROUND_HALF_UP
        );
    }

    /**
     * @param Money $money
     *
     * @return mixed[]
     */
    private function serializeArray(Money $money): array
    {
        return [
            'amount'   => $money->toFloat(),
            'currency' => [
                'code'      => $money->getCurrency()->getCode(),
                'precision' => $money->getCurrency()->getPrecision(),
            ],
        ];
    }

    /**
     * @param DeserializationVisitorInterface $visitor
     * @param mixed                           $data
     * @param mixed[]                         $type
     *
     * @return Money
     */
    public function deserialize(DeserializationVisitorInterface $visitor, $data, array $type, DeserializationContext $context): Money
    {
        $mode = $this->getMode($type);
        switch ($mode) {
            case self::MODE_DECIMAL:
                return $this->parseDecimal($data, $type);
            case self::MODE_STRING:
                return $this->parseString($data, $type);
            case self::MODE_INTEGER:
                return $this->parseInteger($data, $type);
            case self::MODE_FLOAT:
                return $this->parseFloat($data, $type);
            case self::MODE_ARRAY:
                return $this->parseArray($data);
        }
        throw new DomainException('Mode is not Valid', 1597732468436);
    }

    /**
     * @param mixed   $data
     * @param mixed[] $type
     *
     * @return Money
     */
    private function parseDecimal($data, array $type): Money
    {
        $currency = $this->getCurrency($type);
        $precision = $this->getPrecision($type);
        return Money::fromDecimalString(
            (string)$data,
            new Currency(
                $currency,
                $precision
            )
        );
    }

    /**
     * @param mixed   $data
     * @param mixed[] $type
     *
     * @return Money
     */
    private function parseString($data, array $type): Money
    {
        $numberFormatter = $this->getNumberFormatter($type);
        return Money::fromFormattedString(
            (string)$data,
            $numberFormatter
        );
    }

    /**
     * @param mixed   $data
     * @param mixed[] $type
     *
     * @return Money
     */
    private function parseInteger($data, array $type): Money
    {
        $currency = $this->getCurrency($type);
        $precision = $this->getPrecision($type);
        return Money::fromInt(
            (int)$data,
            new Currency(
                $currency,
                $precision
            )
        );
    }

    /**
     * @param mixed   $data
     * @param mixed[] $type
     *
     * @return Money
     */
    private function parseFloat($data, array $type): Money
    {
        $currency = $this->getCurrency($type);
        $precision = $this->getPrecision($type);
        return Money::fromFloat(
            (float)$data,
            new Currency(
                $currency,
                $precision
            )
        );
    }

    /**
     * @param mixed   $data
     *
     * @return Money
     */
    private function parseArray($data): Money
    {
        return Money::fromArray($data);
    }

    /**
     * @param mixed[] $type
     *
     * @return string
     */
    private function getMode(array $type): string
    {
        $mode = $type['params'][0];
        if (!in_array($mode, self::MODES, true)) {
            throw new DomainException(
                sprintf(
                    'Mode "%s"is not Valid. Valid Modes are: "%s"',
                    $mode,
                    implode(', ', self::MODES)
                )
            );
        }

        return $mode;
    }

    /**
     * @param mixed[] $type
     *
     * @return string
     */
    private function getCurrency(array $type): string
    {
        $currency = $type['params'][1];
        return (string)$currency;
    }

    /**
     * @param mixed[] $type
     *
     * @return int
     */
    private function getPrecision(array $type): int
    {
        if (!isset($type['params'][2])) {
            $currency = new Currency($this->getCurrency($type));
            return $currency->getPrecision();
        }

        return (int)$type['params'][2];
    }

    /**
     * @param mixed[] $type
     *
     * @return string|null
     */
    private function getLocal(array $type): ?string
    {
        if (!isset($type['params'][1])) {
            return null;
        }

        return (string)$type['params'][1];
    }

    /**
     * @param mixed[] $type
     * @return NumberFormatter
     */
    private function getNumberFormatter(array $type): NumberFormatter
    {
        $local = $this->getLocal($type);
        if (null === $local) {
            throw new DomainException(
                'Local is required for String mode.'
                . ' Write @Serializer\Type("Re2bit\Types\Money<\'string\', \'DE_de\'>")',
                1597734424902
            );
        }

        return new NumberFormatter($local, NumberFormatter::CURRENCY);
    }
}

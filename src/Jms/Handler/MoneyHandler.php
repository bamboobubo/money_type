<?php

declare(strict_types=1);

namespace Re2bit\Types\Jms\Handler;

use DateTimeInterface;
use DomainException;
use DOMCdataSection;
use DOMText;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\DeserializationVisitorInterface;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Re2bit\Types\Currency;
use Re2bit\Types\Money;

final class MoneyHandler implements SubscribingHandlerInterface
{
    private bool $xmlCData;

    private const MODE_DECIMAL = 'decimal';
    private const MODE_STRING = 'string';
    private const MODE_INTEGER = 'integer';
    private const MODE_FLOAT = 'float';
    private const MODES = [
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

        foreach (['json', 'xml'] as $format) {
            $methods[] = [
                'type' => $type,
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => $format,
                'method' => 'deserialize',
            ];
            $methods[] = [
                'type' => $type,
                'format' => $format,
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'method' => 'serialize',
            ];
        }

        return $methods;
    }

    public function __construct(bool $xmlCData = true)
    {
        $this->xmlCData = $xmlCData;
    }

    /**
     * @param array $type
     *
     * @return DOMCdataSection|DOMText|mixed
     */
    public function serialize(SerializationVisitorInterface $visitor, Money $money, array $type, SerializationContext $context)
    {

    }

    /**
     * @param DeserializationVisitorInterface $visitor
     * @param mixed                           $data
     * @param array                           $type
     *
     * @return DateTimeInterface|null
     */
    public function deserialize(DeserializationVisitorInterface $visitor, $data, array $type): ?Money
    {
        return $this->parseMoney($data, $type);
    }

    private function parseMoney($data, $type): ?Money
    {
        $mode = $this->getMode($type);
        switch ($mode) {
            case self::MODE_DECIMAL:
                return $this->parseDecimal($data, $type);
        }
    }

    private function parseDecimal($data, array $type): ?Money
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


    private function getMode(array $type): string
    {
        $mode = $type['params'][0];
        if (!in_array($mode, self::MODES, true)) {
            throw new DomainException(
                sprintf('Mode "%s"is not Valid. Valid Modes are: "%s"',
                    $mode,
                implode(', ', self::MODES)
                )
            );
        }

        return $mode;
    }

    private function getCurrency(array $type): string
    {
        $currency = $type['params'][1];
        return (string)$currency;
    }

    private function getPrecision(array $type): ?int
    {
        if(!isset ($type['params'][2])) {
            return null;
        }

        return (int)$type['params'][2];
    }
}

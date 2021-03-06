<?php

namespace Re2bit\Types;

use DomainException;
use InvalidArgumentException;
use Money\Money as PhpMoney;
use NumberFormatter;

/**
 * @filesource
 */
class Money
{
    private const NON_BREAKING_SPACE = "\xc2\xa0";

    public const ROUND_HALF_UP = PHP_ROUND_HALF_UP;

    public const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;

    public const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;

    public const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;

    public const ROUND_UP = 5;

    public const ROUND_DOWN = 6;

    public const ROUND_HALF_POSITIVE_INFINITY = 7;

    public const ROUND_HALF_NEGATIVE_INFINITY = 8;

    protected Currency $currency;

    protected string $amount;

    /** @var PhpMoney */
    private $money;

    final private function __construct()
    {
    }

    public static function fromFloat(float $amount, Currency $currency, int $roundingMode = self::ROUND_HALF_UP): Money
    {
        $instance = new static();
        $precision = $currency->getPrecision();
        $instance->amount = sprintf(
            "%.0F",
            round(
                round(
                    $amount,
                    $precision,
                    $roundingMode
                ) * (10 ** $precision),
                0
            )
        );
        $instance->currency = $currency;
        $instance->initialize();
        return $instance;
    }

    public static function fromInt(int $amount, Currency $currency): Money
    {
        $instance = new static();
        $instance->amount = (string)$amount;
        $instance->currency = $currency;
        $instance->initialize();
        return $instance;
    }

    public static function fromFormattedString(
        string $amount,
        NumberFormatter $numberFormatter,
        int $roundingMode = self::ROUND_HALF_UP
    ): Money {
        $amount = str_replace(' ', self::NON_BREAKING_SPACE, $amount);
        $curr = '';
        $amountFloat = $numberFormatter->parseCurrency($amount, $curr);
        if (!$amountFloat || !$curr) {
            throw new DomainException(
                $numberFormatter->getErrorMessage(),
                $numberFormatter->getErrorCode()
            );
        }
        return self::fromFloat(
            $amountFloat,
            new Currency($curr),
            $roundingMode
        );
    }

    public static function fromString(string $decimalString, Currency $currency, NumberFormatter $numberFormatter): Money
    {
        $decimal = $numberFormatter->parse($decimalString);
        if (!$decimal) {
            throw new InvalidArgumentException(
                $numberFormatter->getErrorMessage(),
                $numberFormatter->getErrorCode()
            );
        }
        return self::fromFloat($decimal, $currency);
    }

    public static function fromDecimalString(string $decimalString, Currency $currency): Money
    {
        [$a, $b] = explode('.', $decimalString);
        $integer = (int)implode('', [$a, substr($b, 0, $currency->getPrecision())]);
        return self::fromInt($integer, $currency);
    }

    /**
     * @param mixed $value
     *
     * @return Money
     * @deprecated
     */
    public static function EUR($value): Money
    {
        if (is_int($value)) {
            return self::fromInt($value, new Currency('EUR'));
        }
        if (is_float($value)) {
            return self::fromFloat($value, new Currency('EUR'));
        }
        if (is_string($value)) {
            return self::fromDecimalString($value, new Currency('EUR'));
        }
        throw new DomainException('Invalid Value', 1599807600739);
    }

    /**
     * @param mixed[] $data
     *
     * @return Money
     */
    public static function fromArray($data): Money
    {
        $amount = $data['amount'] ?? null;
        $currency = $data['currency'] ?? null;
        if (null === $amount) {
            throw new DomainException('Expected "amount" for Money Array', 1606203192839);
        }
        $currency = new Currency($currency['code'] ?? null, $currency['precision']);
        return self::fromFloat((float)$amount, $currency);
    }

    /**
     * @deprecated
     */
    public static function create(float $amount, string $currency): Money
    {
        return self::fromFloat($amount, new Currency($currency));
    }

    /**
     * @param PhpMoney       $phpMoney
     * @param Money $money
     *
     * @return Money
     */
    private static function fromPhpMoney(PhpMoney $phpMoney, Money $money)
    {
        $instance = new static();
        $instance->money = $phpMoney;
        $instance->amount = $phpMoney->getAmount();
        $instance->currency = clone $money->currency;
        return $instance;
    }

    private function initialize(): void
    {
        if (null === $this->money) {
            $phpMoneyCurrency = $this->currency->toPhpMoneyCurrency();
            $this->money = new PhpMoney(
                $this->amount,
                $phpMoneyCurrency
            );
        }
    }

    private function unwrap(): PhpMoney
    {
        return $this->money;
    }

    /**
     * Checks whether a Money has the same Currency as this.
     */
    public function isSameCurrency(Money $other): bool
    {
        return $this->money->isSameCurrency($other->unwrap());
    }

    /**
     * Checks whether the value represented by this object equals to the other.
     */
    public function equals(Money $other): bool
    {
        $this->ensureSamePrecision($other);
        return $this->money->equals($other->unwrap());
    }

    /**
     * ≈ (U+2248, almost equal to)
     *
     * @param Money $other
     * @param int            $epsilon
     * @param int|null       $precision
     * @param int            $roundingMode
     *
     * @return bool
     */
    public function almostEqualTo(
        Money $other,
        int $epsilon = 1,
        int $precision = null,
        int $roundingMode = self::ROUND_HALF_UP
    ): bool {
        if (null === $precision) {
            $precision = $this->getCurrency()->getPrecision();
        }

        $precisionMoneyThis = $this->precisionTo($precision, $roundingMode);
        $precisionMoneyOther = $other->precisionTo($precision, $roundingMode) ;
        $precisionMoneyThis->ensureSameRawCurrency($precisionMoneyOther);

        $difference = $precisionMoneyThis->subtract($precisionMoneyOther);
        return $difference->absolute()->toInt() <= $epsilon;
    }

    public function precisionTo(int $precision, int $roundingMode = self::ROUND_HALF_UP): Money
    {
        $asFloat = $this->toFloat();

        return self::fromFloat(
            $asFloat,
            new Currency(
                $this->currency->getCodeWithoutPrecision(),
                $precision,
            ),
            $roundingMode
        );
    }

    /**
     * ≉ (U+2249, not almost equal to)
     */
    public function notAlmostEqualTo(
        Money $other,
        int $epsilon = 1,
        int $precision = null,
        int $roundingMode = self::ROUND_HALF_UP
    ): bool {
        return !$this->almostEqualTo($other, $epsilon, $precision, $roundingMode);
    }

    private function ensureSameRawCurrency(Money $other): void
    {
        if (!$this->isSameCurrency($other)) {
            throw new DomainException(
                'Money has not Same Currency: ' . $this->getCurrency()->getCode() . ' - ' . $other->getCurrency()->getCode()
            );
        }
    }

    /**
     * Returns an integer less than, equal to, or greater than zero
     * if the value of this object is considered to be respectively
     * less than, equal to, or greater than the other.
     */
    public function compare(Money $other): int
    {
        $this->ensureSamePrecision($other);
        return $this->money->compare($other->unwrap());
    }

    /**
     * Checks whether the value represented by this object is greater than the other.
     */
    public function greaterThan(Money $other): bool
    {
        $this->ensureSamePrecision($other);
        return $this->money->greaterThan($other->unwrap());
    }

    public function greaterThanOrEqual(Money $other): bool
    {
        $this->ensureSamePrecision($other);
        return $this->money->greaterThanOrEqual($other->unwrap());
    }

    /**
     * Checks whether the value represented by this object is less than the other.
     */
    public function lessThan(Money $other): bool
    {
        $this->ensureSamePrecision($other);
        return $this->money->lessThan($other->unwrap());
    }

    public function lessThanOrEqual(Money $other): bool
    {
        $this->ensureSamePrecision($other);
        return $this->money->lessThanOrEqual($other->unwrap());
    }

    /**
     * Returns the value represented by this object.
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * Returns the currency of this object.
     *
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @return array<PhpMoney>
     */
    private static function unwrapMoneyArray(Money ...$values): array
    {
        return array_map(static function (Money $money) {
            return $money->unwrap();
        }, $values);
    }

    /**
     * @return array<Money>
     */
    private static function warpPhpMoneyArray(Money $money, PhpMoney ...$values): array
    {
        return array_map(static function (PhpMoney $phpMoney) use ($money) {
            return self::fromPhpMoney($phpMoney, $money);
        }, $values);
    }


    /**
     * Returns a new Money object that represents
     * the sum of this and an other Money object.
     */
    public function add(Money ...$addends): Money
    {
        $this->ensureSamePrecision(...$addends);
        $unwrappedAddends = self::unwrapMoneyArray(...$addends);
        return self::fromPhpMoney($this->money->add(...$unwrappedAddends), $this);
    }

    /**
     * Returns a new Money object that represents
     * the difference of this and an other Money object.
     *
     * @return Money
     *
     * @psalm-pure
     */
    public function subtract(Money ...$subtrahends): Money
    {
        $this->ensureSamePrecision(...$subtrahends);
        $unwrappedSubtrahends = self::unwrapMoneyArray(...$subtrahends);
        return self::fromPhpMoney(
            $this->money->subtract(...$unwrappedSubtrahends),
            $this
        );
    }

    /**
     * Returns a new Money object that represents
     * the multiplied value by the given factor.
     *
     * @param float|int|string $multiplier
     * @param int              $roundingMode
     *
     * @return Money
     */
    public function multiply($multiplier, int $roundingMode = self::ROUND_HALF_UP): Money
    {
        return self::fromPhpMoney(
            $this->money->multiply($multiplier, $roundingMode),
            $this
        );
    }

    /**
     * Returns a new Money object that represents
     * the divided value by the given factor.
     *
     * @param float|int|string $divisor
     * @param int              $roundingMode
     *
     * @return Money
     */
    public function divide($divisor, int $roundingMode = self::ROUND_HALF_UP): Money
    {
        return self::fromPhpMoney(
            $this->money->divide($divisor, $roundingMode),
            $this
        );
    }

    /**
     * Returns a new Money object that represents
     * the remainder after dividing the value by
     * the given factor.
     *
     * @param Money $divisor
     *
     * @return Money
     */
    public function mod(Money $divisor): Money
    {
        $this->ensureSamePrecision($divisor);
        return self::fromPhpMoney(
            $this->money->mod($divisor->unwrap()),
            $this
        );
    }

    /**
     * Allocate the money according to a list of ratios.
     *
     * @param array<mixed> $ratios
     *
     * @return Money[]
     */
    public function allocate(array $ratios): array
    {
        return self::warpPhpMoneyArray($this, ...$this->money->allocate($ratios));
    }

    /**
     * Allocate the money among N targets.
     *
     * @return array<Money>
     */
    public function allocateTo(int $n): array
    {
        return self::warpPhpMoneyArray($this, ...$this->money->allocateTo($n));
    }

    public function ratioOf(Money $money): string
    {
        return $this->money->ratioOf($money->unwrap());
    }

    public function absolute(): Money
    {
        return self::fromPhpMoney($this->money->absolute(), $this);
    }

    public function negative(): Money
    {
        return self::fromPhpMoney($this->money->negative(), $this);
    }

    /**
     * Checks if the value represented by this object is zero.
     */
    public function isZero(): bool
    {
        return $this->money->isZero();
    }

    /**
     * Checks if the value represented by this object is positive.
     */
    public function isPositive(): bool
    {
        return $this->money->isPositive();
    }

    /**
     * Checks if the value represented by this object is negative.
     */
    public function isNegative(): bool
    {
        return $this->money->isNegative();
    }


    /**
     * @param Money $first
     * @param Money ...$collection
     *
     * @return Money
     *
     * @psalm-pure
     */
    public static function min(Money $first, Money ...$collection): Money
    {
        $firstPhpMoney = $first->unwrap();
        $phpMoneyCollection = self::unwrapMoneyArray(...$collection);
        $first->ensureSamePrecision(...$collection);
        return self::fromPhpMoney(
            PhpMoney::min($firstPhpMoney, ...$phpMoneyCollection),
            $first
        );
    }

    /**
     * @param Money $first
     * @param Money ...$collection
     *
     * @return Money
     *
     * @psalm-pure
     */
    public static function max(Money $first, Money ...$collection): Money
    {
        $firstPhpMoney = $first->unwrap();
        $first->ensureSamePrecision(...$collection);
        $phpMoneyCollection = self::unwrapMoneyArray(...$collection);
        return self::fromPhpMoney(
            PhpMoney::max($firstPhpMoney, ...$phpMoneyCollection),
            $first
        );
    }

    /**
     * @param Money $first
     * @param Money ...$collection
     *
     * @return Money
     *
     * @psalm-pure
     */
    public static function sum(Money $first, Money ...$collection): Money
    {
        $firstPhpMoney = $first->unwrap();
        $first->ensureSamePrecision(...$collection);
        $phpMoneyCollection = self::unwrapMoneyArray(...$collection);
        return self::fromPhpMoney(
            PhpMoney::sum($firstPhpMoney, ...$phpMoneyCollection),
            $first
        );
    }

    /**
     * @param Money $first
     * @param Money ...$collection
     *
     * @return Money
     *
     * @psalm-pure
     */
    public static function avg(Money $first, Money ...$collection): Money
    {
        $firstPhpMoney = $first->unwrap();
        $first->ensureSamePrecision(...$collection);
        $phpMoneyCollection = self::unwrapMoneyArray(...$collection);
        return self::fromPhpMoney(
            PhpMoney::sum($firstPhpMoney, ...$phpMoneyCollection),
            $first
        );
    }

    public function toFloat(): float
    {
        return round(
            ((int)$this->amount) / (10 ** $this->currency->getPrecision()),
            $this->currency->getPrecision()
        );
    }

    public function toString(NumberFormatter $currencyFormatter): string
    {
        $formattedMoney = $currencyFormatter->formatCurrency($this->toFloat(), $this->currency->getCode());
        if (!$formattedMoney) {
            throw new DomainException($currencyFormatter->getErrorMessage(), $currencyFormatter->getErrorCode());
        }
        return $formattedMoney;
    }

    public function toDecimalString(int $precision): string
    {
        $amount = $this->getAmount();
        if ($this->getCurrency()->getPrecision() === 0) {
            return $amount;
        }
        $start = strlen($amount) - $this->getCurrency()->getPrecision();
        return substr(
            $amount,
            0,
            $start
        ) . '.' . substr($amount, $start, $precision);
    }

    public function toInt(): int
    {
        return (int)$this->amount;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            'amount'   => $this->toFloat(),
            'currency' => [
                'code'      => $this->getCurrency()->getCodeWithoutPrecision(),
                'precision' => $this->getCurrency()->getPrecision(),
            ],
        ];
    }

    public function __toString(): string
    {
        return $this->amount . ' ' . $this->currency->getCode();
    }

    /**
     * @throws PrecisionException
     */
    private function ensureSamePrecision(Money ...$others): void
    {
        $ownCurrency = $this->getCurrency();
        foreach ($others as $other) {
            $otherCurrency = $other->getCurrency();
            $differentPrecision = $ownCurrency->getCodeWithoutPrecision()
                === $otherCurrency->getCodeWithoutPrecision()
                && $ownCurrency->getCode()
                !== $otherCurrency->getCode();
            if ($differentPrecision) {
                throw PrecisionException::createPrecisionException(
                    $ownCurrency->getPrecision(),
                    $otherCurrency->getPrecision()
                );
            }
        }
    }

    public function isLessOrEqualZero(): bool
    {
        return $this->isZero() || $this->isNegative();
    }

    /**
     * @deprecated use lessThan
     */
    public function isLessThan(Money $money): bool
    {
        return $this->lessThan($money);
    }

    /**
     * @deprecated use greaterThanOrEqual
     */
    public function isBiggerOrEqual(Money $money): bool
    {
        return $this->greaterThanOrEqual($money);
    }

    /**
     * @deprecated use equals
     */
    public function isEqual(Money $money): bool
    {
        return $this->equals($money);
    }

    /**
     * @deprecated
     */
    public function hasSameCurrencyAs(Money $other): bool
    {
        return $this->isSameCurrency($other);
    }
}

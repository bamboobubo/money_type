<?php

namespace Re2bit\Types;

use Doctrine\ORM\Mapping as ORM;
use DomainException;
use InvalidArgumentException;
use JMS\Serializer\Annotation as Serializer;
use Money\Money as PhpMoney;
use NumberFormatter;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Embeddable()
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

    /**
     * @Assert\Valid()
     * @ORM\Column(name="currency", type="money_currency")
     */
    private Currency $currency;

    /**
     * @Assert\Type(type="string")
     * @Assert\NotBlank()
     * @Assert\NotNull()
     * @ORM\Column(name="amount", type="money_amount")
     */
    private string $amount;

    /** @var PhpMoney */
    private $money;

    private function __construct()
    {
    }

    public static function fromFloat(float $amount, Currency $currency, int $roundingMode = self::ROUND_HALF_UP): self
    {
        $instance = new self();
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
        $instance->ensureInitialized();
        return $instance;
    }

    public static function fromInt(int $amount, Currency $currency): self
    {
        $instance = new self();
        $instance->amount = (string)$amount;
        $instance->currency = $currency;
        $instance->ensureInitialized();
        return $instance;
    }

    public static function fromFormattedString(
        string $amount,
        NumberFormatter $numberFormatter,
        int $roundingMode = self::ROUND_HALF_UP
    ): self {
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
        $parts = explode('.', $decimalString);
        $integer = (int)implode('', $parts);
        return self::fromInt($integer, $currency);
    }

    /**
     * @param PhpMoney $phpMoney
     * @param Money    $money
     *
     * @return Money
     */
    private static function fromPhpMoney(PhpMoney $phpMoney, Money $money)
    {
        $instance = new self();
        $money->money = $phpMoney;
        $money->amount = $money->money->getAmount();
        $money->currency = clone $money->currency;
        return $instance;
    }

    /**
     * @Serializer\PostDeserialize()
     */
    private function ensureInitialized(): void
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
        $this->ensureInitialized();
        return $this->money;
    }

    /**
     * Checks whether a Money has the same Currency as this.
     */
    public function isSameCurrency(Money $other): bool
    {
        $this->ensureInitialized();
        return $this->money->isSameCurrency($other->unwrap());
    }

    /**
     * Checks whether the value represented by this object equals to the other.
     */
    public function equals(Money $other): bool
    {
        $this->ensureInitialized();
        return $this->money->equals($other->unwrap());
    }

    /**
     * ≈ (U+2248, almost equal to)
     *
     * @param Money    $other
     * @param int      $epsilon
     * @param int|null $precision
     * @param int      $roundingMode
     *
     * @return bool
     */
    public function almostEqualTo(
        Money $other,
        int $epsilon = 1,
        int $precision = null,
        int $roundingMode = self::ROUND_HALF_UP
    ): bool {
        $this->ensureInitialized();
        if (null === $precision) {
            $precision = $this->getCurrency()->getPrecision();
        }

        $precisionMoneyThis = $this->precisionTo($precision, $roundingMode);
        $precisionMoneyOther = $other->precisionTo($precision, $roundingMode) ;
        $precisionMoneyThis->ensureSameCurrency($precisionMoneyOther);

        return abs($precisionMoneyThis->compare($precisionMoneyOther)) <= $epsilon;
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

    private function ensureSameCurrency(Money $other): void
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
        $this->ensureInitialized();
        return $this->money->compare($other->unwrap());
    }

    /**
     * Checks whether the value represented by this object is greater than the other.
     */
    public function greaterThan(Money $other): bool
    {
        $this->ensureInitialized();
        return $this->money->greaterThan($other->unwrap());
    }

    public function greaterThanOrEqual(Money $other): bool
    {
        $this->ensureInitialized();
        return $this->money->greaterThanOrEqual($other->unwrap());
    }

    /**
     * Checks whether the value represented by this object is less than the other.
     */
    public function lessThan(Money $other): bool
    {
        $this->ensureInitialized();
        return $this->money->lessThan($other->unwrap());
    }

    public function lessThanOrEqual(Money $other): bool
    {
        $this->ensureInitialized();
        return $this->money->lessThanOrEqual($other->unwrap());
    }

    /**
     * Returns the value represented by this object.
     */
    public function getAmount(): string
    {
        $this->ensureInitialized();
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
        $this->ensureInitialized();
        return self::fromPhpMoney(
            $this->money->add(...self::unwrapMoneyArray(...$addends)),
            $this
        );
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
        $this->ensureInitialized();
        return self::fromPhpMoney(
            $this->money->subtract(...self::unwrapMoneyArray(...$subtrahends)),
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
        $this->ensureInitialized();
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
        $this->ensureInitialized();
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
        $this->ensureInitialized();
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
        $this->ensureInitialized();
        return self::warpPhpMoneyArray($this, ...$this->money->allocate($ratios));
    }

    /**
     * Allocate the money among N targets.
     *
     * @return array<Money>
     */
    public function allocateTo(int $n): array
    {
        $this->ensureInitialized();
        return self::warpPhpMoneyArray($this, ...$this->money->allocateTo($n));
    }

    public function ratioOf(Money $money): string
    {
        $this->ensureInitialized();
        return $this->money->ratioOf($money->unwrap());
    }

    public function absolute(): Money
    {
        return self::fromPhpMoney($this->money->absolute(), $this);
    }

    public function negative(): Money
    {
        $this->ensureInitialized();
        return self::fromPhpMoney($this->money->negative(), $this);
    }

    /**
     * Checks if the value represented by this object is zero.
     */
    public function isZero(): bool
    {
        $this->ensureInitialized();
        return $this->money->isZero();
    }

    /**
     * Checks if the value represented by this object is positive.
     */
    public function isPositive(): bool
    {
        $this->ensureInitialized();
        return $this->money->isPositive();
    }

    /**
     * Checks if the value represented by this object is negative.
     */
    public function isNegative(): bool
    {
        $this->ensureInitialized();
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
    public static function min(self $first, self ...$collection): Money
    {
        $firstPhpMoney = $first->unwrap();
        $phpMoneyCollection = self::unwrapMoneyArray(...$collection);
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
    public static function max(self $first, self ...$collection): Money
    {
        $firstPhpMoney = $first->unwrap();
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
    public static function sum(self $first, self ...$collection): Money
    {
        $firstPhpMoney = $first->unwrap();
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
        $phpMoneyCollection = self::unwrapMoneyArray(...$collection);
        return self::fromPhpMoney(
            PhpMoney::sum($firstPhpMoney, ...$phpMoneyCollection),
            $first
        );
    }

    public function toFloat(): float
    {
        $this->ensureInitialized();
        return round(
            ((int)$this->amount) / (10 ** $this->currency->getPrecision()),
            $this->currency->getPrecision()
        );
    }

    public function toString(NumberFormatter $currencyFormatter): string
    {
        $this->ensureInitialized();
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

    public function __toString(): string
    {
        return $this->amount . ' ' . $this->currency->getCode();
    }
}

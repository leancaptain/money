<?php

declare(strict_types=1);

namespace LeanCaptain\Money;

use JsonSerializable;
use LeanCaptain\Money\Contracts\CurrencyContract;
use LeanCaptain\Money\Exceptions\CurrencyMismatch;
use LeanCaptain\Money\Exceptions\InvalidMoneyAmount;
use LeanCaptain\Money\Exceptions\RoundingRequired;
use Stringable;

final readonly class Money implements JsonSerializable, Stringable
{
    private function __construct(
        private int $minorAmount,
        private CurrencyContract $currency,
    ) {}

    public static function of(
        int|string $amount,
        CurrencyContract $currency,
        RoundingMode $roundingMode = RoundingMode::REJECT,
    ): self {
        return new self(
            self::toMinorAmount($amount, $currency->minorUnit(), $roundingMode),
            $currency,
        );
    }

    public static function fromMinor(
        int $minorAmount,
        CurrencyContract $currency,
    ): self {
        return new self($minorAmount, $currency);
    }

    public function minorAmount(): int
    {
        return $this->minorAmount;
    }

    public function currency(): CurrencyContract
    {
        return $this->currency;
    }

    public function toDecimal(): string
    {
        $minorUnit = $this->currency->minorUnit();

        if ($minorUnit === 0) {
            return (string) $this->minorAmount;
        }

        $factor = 10 ** $minorUnit;

        $whole = intdiv(abs($this->minorAmount), $factor);
        $fraction = abs($this->minorAmount) % $factor;

        $decimal = sprintf(
            '%d.%0'.$minorUnit.'d',
            $whole,
            $fraction,
        );

        return $this->minorAmount < 0
            ? '-'.$decimal
            : $decimal;
    }

    public function equals(self $other): bool
    {
        return $this->hasSameCurrency($other)
            && $this->minorAmount === $other->minorAmount;
    }

    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    public function isPositive(): bool
    {
        return $this->minorAmount > 0;
    }

    public function isNegative(): bool
    {
        return $this->minorAmount < 0;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->minorAmount > $other->minorAmount;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->minorAmount >= $other->minorAmount;
    }

    public function isLessThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->minorAmount < $other->minorAmount;
    }

    public function isLessThanOrEqual(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->minorAmount <= $other->minorAmount;
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            $this->minorAmount + $other->minorAmount,
            $this->currency,
        );
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self(
            $this->minorAmount - $other->minorAmount,
            $this->currency,
        );
    }

    public function negate(): self
    {
        return new self(
            -$this->minorAmount,
            $this->currency,
        );
    }

    public function absolute(): self
    {
        return $this->minorAmount < 0
            ? $this->negate()
            : $this;
    }

    public function multiply(
        int|string $multiplier,
        RoundingMode $roundingMode = RoundingMode::REJECT,
    ): self {
        [$numerator, $scale] = self::parseDecimalFactor($multiplier);

        $result = self::divideAndRound(
            $this->minorAmount * $numerator,
            10 ** $scale,
            $roundingMode,
        );

        return new self($result, $this->currency);
    }

    public function divide(
        int|string $divisor,
        RoundingMode $roundingMode = RoundingMode::REJECT,
    ): self {
        [$numerator, $scale] = self::parseDecimalFactor($divisor);

        if ($numerator === 0) {
            throw new \DivisionByZeroError('Cannot divide money by zero.');
        }

        $result = self::divideAndRound(
            $this->minorAmount * (10 ** $scale),
            $numerator,
            $roundingMode,
        );

        return new self($result, $this->currency);
    }

    public function __toString(): string
    {
        return $this->toDecimal().' '.$this->currency->code();
    }

    /**
     * @return array{
     *     amount: string,
     *     currency: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->toDecimal(),
            'currency' => $this->currency->code(),
        ];
    }


    /*
     *
     * ======== Private Methods ========
     *
     */

    private static function toMinorAmount(
        int|string $amount,
        int $minorUnit,
        RoundingMode $roundingMode,
    ): int {
        if ($minorUnit < 0) {
            throw new InvalidMoneyAmount(
                'Currency minor unit cannot be negative.',
            );
        }

        $amount = (string) $amount;

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $amount)) {
            throw new InvalidMoneyAmount(
                "Invalid money amount: {$amount}.",
            );
        }

        $negative = str_starts_with($amount, '-');

        if ($negative) {
            $amount = substr($amount, 1);
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            '',
        );

        if ($minorUnit === 0) {
            return self::toZeroMinorUnitAmount(
                $whole,
                $fraction,
                $negative,
                $roundingMode,
            );
        }

        if (strlen($fraction) > $minorUnit) {
            [$fraction, $increment] = self::roundFraction(
                $fraction,
                $minorUnit,
                $roundingMode,
            );
        } else {
            $fraction = str_pad(
                $fraction,
                $minorUnit,
                '0',
            );

            $increment = false;
        }

        $factor = 10 ** $minorUnit;

        $minorAmount =
            ((int) $whole * $factor)
            + (int) $fraction;

        if ($increment) {
            $minorAmount++;
        }

        return $negative
            ? -$minorAmount
            : $minorAmount;
    }

    /**
     * @return array{string, bool}
     */
    private static function roundFraction(
        string $fraction,
        int $minorUnit,
        RoundingMode $roundingMode,
    ): array {
        $kept = substr($fraction, 0, $minorUnit);
        $discarded = substr($fraction, $minorUnit);

        return match ($roundingMode) {
            RoundingMode::REJECT => throw new InvalidMoneyAmount(
                "Amount has more than {$minorUnit} decimal places.",
            ),

            RoundingMode::DOWN => [
                $kept,
                false,
            ],

            RoundingMode::HALF_UP => [
                $kept,
                (int) $discarded[0] >= 5,
            ],
        };
    }

    private static function toZeroMinorUnitAmount(
        string $whole,
        string $fraction,
        bool $negative,
        RoundingMode $roundingMode,
    ): int {
        $amount = (int) $whole;

        if ($fraction !== '') {
            switch ($roundingMode) {
                case RoundingMode::REJECT:
                    throw new InvalidMoneyAmount(
                        'Amount has more than 0 decimal places.',
                    );

                case RoundingMode::HALF_UP:
                    if ((int) $fraction[0] >= 5) {
                        $amount++;
                    }

                    break;

                case RoundingMode::DOWN:
                    break;
            }
        }

        return $negative
            ? -$amount
            : $amount;
    }

    private function hasSameCurrency(self $other): bool
    {
        return $this->currency->code() === $other->currency->code()
            && $this->currency->minorUnit() === $other->currency->minorUnit();
    }

    private function ensureSameCurrency(self $other): void
    {
        if (! $this->hasSameCurrency($other)) {
            throw new CurrencyMismatch(sprintf(
                'Cannot compare %s with %s.',
                $this->currency->code(),
                $other->currency->code(),
            ));
        }
    }

    /**
     * @return array{int, int}
     */
    private static function parseDecimalFactor(
        int|string $value,
    ): array {
        $value = (string) $value;

        if (! preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            throw new InvalidMoneyAmount(
                "Invalid decimal value: {$value}.",
            );
        }

        $negative = str_starts_with($value, '-');

        if ($negative) {
            $value = substr($value, 1);
        }

        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            '',
        );

        $scale = strlen($fraction);

        $numerator = (int) ($whole.$fraction);

        if ($negative) {
            $numerator = -$numerator;
        }

        return [$numerator, $scale];
    }

    private static function divideAndRound(
        int $numerator,
        int $denominator,
        RoundingMode $roundingMode,
    ): int {
        if ($denominator === 0) {
            throw new \DivisionByZeroError('Cannot divide money by zero.');
        }

        $negative = ($numerator < 0) !== ($denominator < 0);

        $numerator = abs($numerator);
        $denominator = abs($denominator);

        $quotient = intdiv($numerator, $denominator);
        $remainder = $numerator % $denominator;

        if ($remainder === 0) {
            return $negative ? -$quotient : $quotient;
        }

        $quotient = match ($roundingMode) {
            RoundingMode::REJECT => throw new RoundingRequired(
                'Result requires rounding.',
            ),

            RoundingMode::DOWN => $quotient,

            RoundingMode::HALF_UP => (
            $remainder * 2 >= $denominator
                ? $quotient + 1
                : $quotient
            ),
        };

        return $negative ? -$quotient : $quotient;
    }
}
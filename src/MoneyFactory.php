<?php

declare(strict_types=1);

namespace LeanCaptain\Money;

use LeanCaptain\Money\Contracts\CurrencyContract;

final readonly class MoneyFactory
{
    public function __construct(
        private CurrencyContract $currency,
        private RoundingMode $roundingMode = RoundingMode::REJECT,
    ) {}

    public function of(int|string $amount): Money
    {
        return Money::of(
            $amount,
            $this->currency,
            $this->roundingMode,
        );
    }

    public function fromMinor(int $minorAmount): Money
    {
        return Money::fromMinor(
            $minorAmount,
            $this->currency,
        );
    }

    public function currency(): CurrencyContract
    {
        return $this->currency;
    }

    public function roundingMode(): RoundingMode
    {
        return $this->roundingMode;
    }
}
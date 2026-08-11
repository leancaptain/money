<?php

namespace LeanCaptain\Money;

use LeanCaptain\Money\Contracts\CurrencyContract;

enum Currency: string implements CurrencyContract
{
    case BDT = 'BDT';
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case JPY = 'JPY';

    public function code(): string
    {
        return $this->value;
    }

    public function minorUnit(): int
    {
        return match ($this) {
            self::JPY => 0,
            default => 2,
        };
    }
}

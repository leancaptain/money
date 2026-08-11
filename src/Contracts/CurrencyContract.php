<?php

namespace LeanCaptain\Money\Contracts;

interface CurrencyContract
{
    public function code(): string;

    public function minorUnit(): int;
}
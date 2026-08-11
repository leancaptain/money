<?php

use LeanCaptain\Money\Currency;

it('has currency codes as values', function () {
    expect(Currency::BDT->value)->toBe('BDT')
        ->and(Currency::USD->value)->toBe('USD');
});

it('knows its minor unit', function () {
    expect(Currency::BDT->minorUnit())->toBe(2)
        ->and(Currency::USD->minorUnit())->toBe(2)
        ->and(Currency::JPY->minorUnit())->toBe(0);
});
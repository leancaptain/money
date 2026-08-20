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

it('provides the correct minor unit for supported currencies', function (
    Currency $currency,
    int $minorUnit,
) {
    expect($currency->minorUnit())->toBe($minorUnit);
})->with([
    [Currency::BDT, 2],
    [Currency::USD, 2],
    [Currency::EUR, 2],
    [Currency::GBP, 2],
    [Currency::JPY, 0],
    [Currency::INR, 2],
    [Currency::AED, 2],
    [Currency::SAR, 2],
    [Currency::SGD, 2],
    [Currency::AUD, 2],
    [Currency::CAD, 2],
    [Currency::CNY, 2],
]);

it('supports the expected built-in currencies', function () {
    expect(array_column(Currency::cases(), 'value'))->toBe([
        'BDT',
        'USD',
        'EUR',
        'GBP',
        'JPY',
        'INR',
        'AED',
        'SAR',
        'SGD',
        'AUD',
        'CAD',
        'CNY',
    ]);
});
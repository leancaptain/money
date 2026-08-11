<?php

use LeanCaptain\Money\Contracts\CurrencyContract;
use LeanCaptain\Money\Currency;
use LeanCaptain\Money\Exceptions\InvalidMoneyAmount;
use LeanCaptain\Money\Exceptions\RoundingRequired;
use LeanCaptain\Money\MoneyFactory;
use LeanCaptain\Money\RoundingMode;

it('creates money using the configured currency', function () {
    $factory = new MoneyFactory(Currency::BDT);

    $money = $factory->of('100.50');

    expect($money->minorAmount())->toBe(10050)
        ->and($money->currency())->toBe(Currency::BDT)
        ->and($money->toDecimal())->toBe('100.50');
});

it('uses the configured rounding mode', function () {
    $factory = new MoneyFactory(
        Currency::BDT,
        RoundingMode::HALF_UP,
    );

    $money = $factory->of('10.999');

    expect($money->toDecimal())->toBe('11.00');
});

it('rejects excessive precision by default', function () {
    $factory = new MoneyFactory(Currency::BDT);

    $factory->of('10.999');
})->throws(InvalidMoneyAmount::class);

it('creates money directly from minor units', function () {
    $factory = new MoneyFactory(Currency::BDT);

    $money = $factory->fromMinor(12345);

    expect($money->minorAmount())->toBe(12345)
        ->and($money->toDecimal())->toBe('123.45');
});

it('exposes its configuration', function () {
    $factory = new MoneyFactory(
        Currency::BDT,
        RoundingMode::HALF_UP,
    );

    expect($factory->currency())->toBe(Currency::BDT)
        ->and($factory->roundingMode())
        ->toBe(RoundingMode::HALF_UP);
});

it('supports consumer-defined currencies', function () {
    $currency = new class implements CurrencyContract
    {
        public function code(): string
        {
            return 'ABC';
        }

        public function minorUnit(): int
        {
            return 3;
        }
    };

    $factory = new MoneyFactory($currency);

    $money = $factory->of('12.345');

    expect($money->minorAmount())->toBe(12345)
        ->and($money->currency())->toBe($currency)
        ->and($money->toDecimal())->toBe('12.345');
});


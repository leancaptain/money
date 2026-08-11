<?php

use LeanCaptain\Money\Contracts\CurrencyContract;
use LeanCaptain\Money\Currency;
use LeanCaptain\Money\Exceptions\CurrencyMismatch;
use LeanCaptain\Money\Exceptions\InvalidMoneyAmount;
use LeanCaptain\Money\Exceptions\RoundingRequired;
use LeanCaptain\Money\Money;
use LeanCaptain\Money\RoundingMode;

it('creates money from minor units', function () {
    $money = Money::fromMinor(12550, Currency::BDT);

    expect($money->minorAmount())->toBe(12550)
        ->and($money->currency())->toBe(Currency::BDT);
});

it('converts minor units to decimal representation', function () {
    $money = Money::fromMinor(12550, Currency::BDT);

    expect($money->toDecimal())->toBe('125.50');
});

it('supports zero amounts', function () {
    $money = Money::fromMinor(0, Currency::BDT);

    expect($money->minorAmount())->toBe(0)
        ->and($money->toDecimal())->toBe('0.00');
});

it('supports negative amounts', function () {
    $money = Money::fromMinor(-12550, Currency::BDT);

    expect($money->minorAmount())->toBe(-12550)
        ->and($money->toDecimal())->toBe('-125.50')
        ->and(
            Money::of('-12.50', Currency::BDT)->minorAmount()
        )->toBe(-1250);
});

it('supports currencies without minor units', function () {
    $money = Money::fromMinor(125, Currency::JPY);

    expect($money->minorAmount())->toBe(125)
        ->and($money->toDecimal())->toBe('125');
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

    $money = Money::fromMinor(12345, $currency);

    expect($money->minorAmount())->toBe(12345)
        ->and($money->currency())->toBe($currency)
        ->and($money->currency()->code())->toBe('ABC')
        ->and($money->toDecimal())->toBe('12.345');
});

it('creates money from a decimal amount', function () {
    $money = Money::of('125.50', Currency::BDT);

    expect($money->minorAmount())->toBe(12550)
        ->and($money->toDecimal())->toBe('125.50');
});

it('pads missing decimal places', function () {
    expect(
        Money::of('12.5', Currency::BDT)->minorAmount()
    )->toBe(1250);
});

it('supports whole amounts', function () {
    expect(
        Money::of(12, Currency::BDT)->minorAmount()
    )->toBe(1200);
});

it('rejects excessive precision by default', function () {
    Money::of('10.999', Currency::BDT);
})->throws(InvalidMoneyAmount::class);

it('rounds half up when explicitly requested', function () {
    expect(
        Money::of(
            '10.995',
            Currency::BDT,
            RoundingMode::HALF_UP,
        )->minorAmount()
    )->toBe(1100);
});

it('rounds down when explicitly requested', function () {
    expect(
        Money::of(
            '10.999',
            Currency::BDT,
            RoundingMode::DOWN,
        )->minorAmount()
    )->toBe(1099);
});

it('handles rounding carry over', function () {
    expect(
        Money::of(
            '99.999',
            Currency::BDT,
            RoundingMode::HALF_UP,
        )->toDecimal()
    )->toBe('100.00');
});

it('supports zero-decimal currencies', function () {
    expect(
        Money::of('125', Currency::JPY)->minorAmount()
    )->toBe(125);
});

it('rounds zero-decimal currencies when requested', function () {
    expect(
        Money::of(
            '125.5',
            Currency::JPY,
            RoundingMode::HALF_UP,
        )->minorAmount()
    )->toBe(126);
});

it('rejects invalid amounts', function (string $amount) {
    Money::of($amount, Currency::BDT);
})->with([
    '',
    'abc',
    '12abc',
    '1,000.00',
    '--10',
    '10.',
    '.50',
])->throws(InvalidMoneyAmount::class);

// Money Arithmetic Tests
it('compares equal money values', function () {
    expect(
        Money::of('100.00', Currency::BDT)
            ->equals(Money::of('100.00', Currency::BDT))
    )->toBeTrue();
});

it('does not consider different amounts equal', function () {
    expect(
        Money::of('100.00', Currency::BDT)
            ->equals(Money::of('99.00', Currency::BDT))
    )->toBeFalse();
});

it('does not consider different currencies equal', function () {
    expect(
        Money::of('100.00', Currency::BDT)
            ->equals(Money::of('100.00', Currency::USD))
    )->toBeFalse();
});

it('identifies zero amounts', function () {
    expect(Money::of('0', Currency::BDT)->isZero())->toBeTrue()
        ->and(Money::of('1', Currency::BDT)->isZero())->toBeFalse();
});

it('identifies positive amounts', function () {
    expect(Money::of('10', Currency::BDT)->isPositive())->toBeTrue()
        ->and(Money::of('0', Currency::BDT)->isPositive())->toBeFalse()
        ->and(Money::of('-10', Currency::BDT)->isPositive())->toBeFalse();
});

it('identifies negative amounts', function () {
    expect(Money::of('-10', Currency::BDT)->isNegative())->toBeTrue()
        ->and(Money::of('0', Currency::BDT)->isNegative())->toBeFalse()
        ->and(Money::of('10', Currency::BDT)->isNegative())->toBeFalse();
});

it('compares greater amounts', function () {
    $larger = Money::of('100', Currency::BDT);
    $smaller = Money::of('50', Currency::BDT);

    expect($larger->isGreaterThan($smaller))->toBeTrue()
        ->and($smaller->isGreaterThan($larger))->toBeFalse();
});

it('compares greater than or equal amounts', function () {
    $money = Money::of('100', Currency::BDT);

    expect(
        $money->isGreaterThanOrEqual(
            Money::of('100', Currency::BDT)
        )
    )->toBeTrue();
});

it('compares lesser amounts', function () {
    expect(
        Money::of('50', Currency::BDT)
            ->isLessThan(Money::of('100', Currency::BDT))
    )->toBeTrue();
});

it('compares less than or equal amounts', function () {
    $money = Money::of('100', Currency::BDT);

    expect(
        $money->isLessThanOrEqual(
            Money::of('100', Currency::BDT)
        )
    )->toBeTrue();
});

it('rejects comparisons between different currencies', function () {
    Money::of('100', Currency::BDT)
        ->isGreaterThan(Money::of('100', Currency::USD));
})->throws(CurrencyMismatch::class);

it('treats equivalent currency implementations as compatible', function () {
    $currency = new class implements CurrencyContract
    {
        public function code(): string
        {
            return 'BDT';
        }

        public function minorUnit(): int
        {
            return 2;
        }
    };

    $packageMoney = Money::of('100', Currency::BDT);
    $consumerMoney = Money::of('100', $currency);

    expect($packageMoney->equals($consumerMoney))->toBeTrue();
});

it('rejects currencies with matching codes but different minor units', function () {
    $currency = new class implements CurrencyContract
    {
        public function code(): string
        {
            return 'BDT';
        }

        public function minorUnit(): int
        {
            return 3;
        }
    };

    Money::of('100', Currency::BDT)
        ->isGreaterThan(Money::of('100', $currency));
})->throws(CurrencyMismatch::class);

// Money Arithmetic Tests
it('adds money', function () {
    $money = Money::of('100.50', Currency::BDT);

    $result = $money->add(
        Money::of('50.25', Currency::BDT)
    );

    expect($result->toDecimal())->toBe('150.75')
        ->and($money->toDecimal())->toBe('100.50');
});

it('subtracts money', function () {
    $result = Money::of('100.50', Currency::BDT)
        ->subtract(Money::of('50.25', Currency::BDT));

    expect($result->toDecimal())->toBe('50.25');
});

it('allows subtraction to produce negative money', function () {
    $result = Money::of('50', Currency::BDT)
        ->subtract(Money::of('100', Currency::BDT));

    expect($result->toDecimal())->toBe('-50.00')
        ->and($result->isNegative())->toBeTrue();
});

it('negates money', function () {
    $money = Money::of('100.50', Currency::BDT);

    expect($money->negate()->toDecimal())->toBe('-100.50')
        ->and($money->toDecimal())->toBe('100.50');
});

it('negates negative money', function () {
    expect(
        Money::of('-100.50', Currency::BDT)
            ->negate()
            ->toDecimal()
    )->toBe('100.50');
});

it('returns the absolute amount', function () {
    expect(
        Money::of('-100.50', Currency::BDT)
            ->absolute()
            ->toDecimal()
    )->toBe('100.50');
});

it('keeps zero unchanged when negated', function () {
    $money = Money::of('0', Currency::BDT);

    expect($money->negate()->isZero())->toBeTrue();
});

it('rejects adding different currencies', function () {
    Money::of('100', Currency::BDT)
        ->add(Money::of('50', Currency::USD));
})->throws(CurrencyMismatch::class);

it('rejects subtracting different currencies', function () {
    Money::of('100', Currency::BDT)
        ->subtract(Money::of('50', Currency::USD));
})->throws(CurrencyMismatch::class);

it('multiplies money by a whole number', function () {
    expect(
        Money::of('10.50', Currency::BDT)
            ->multiply(2)
            ->toDecimal()
    )->toBe('21.00');
});

it('multiplies money by a decimal', function () {
    expect(
        Money::of('100.00', Currency::BDT)
            ->multiply('1.15')
            ->toDecimal()
    )->toBe('115.00');
});

it('multiplies money by zero', function () {
    expect(
        Money::of('100', Currency::BDT)
            ->multiply(0)
            ->isZero()
    )->toBeTrue();
});

it('supports negative multiplication', function () {
    expect(
        Money::of('10', Currency::BDT)
            ->multiply('-2')
            ->toDecimal()
    )->toBe('-20.00');
});

it('rejects multiplication requiring rounding by default', function () {
    Money::of('10.00', Currency::BDT)
        ->multiply('0.3333');
})->throws(RoundingRequired::class);

it('rounds multiplication down', function () {
    expect(
        Money::of('10.01', Currency::BDT)
            ->multiply('0.3355', RoundingMode::DOWN)
            ->toDecimal()
    )->toBe('3.35');
});

it('rounds multiplication half up', function () {
    expect(
        Money::of('10.00', Currency::BDT)
            ->multiply(
                '0.333',
                RoundingMode::HALF_UP,
            )
            ->toDecimal()
    )->toBe('3.33');
});

it('divides money evenly', function () {
    expect(
        Money::of('100.00', Currency::BDT)
            ->divide(4)
            ->toDecimal()
    )->toBe('25.00');
});

it('rejects division requiring rounding by default', function () {
    Money::of('10.00', Currency::BDT)
        ->divide(3);
})->throws(RoundingRequired::class);

it('rounds division half up', function () {
    expect(
        Money::of('10.00', Currency::BDT)
            ->divide(
                3,
                RoundingMode::HALF_UP,
            )
            ->toDecimal()
    )->toBe('3.33');
});

it('rounds division down', function () {
    expect(
        Money::of('10.00', Currency::BDT)
            ->divide(
                3,
                RoundingMode::DOWN,
            )
            ->toDecimal()
    )->toBe('3.33');
});

it('rejects division by zero', function () {
    Money::of('100', Currency::BDT)
        ->divide(0);
})->throws(DivisionByZeroError::class);

it('rounds division upward when halfway or greater', function () {
    expect(
        Money::of('10.01', Currency::BDT)
            ->divide(
                2,
                RoundingMode::HALF_UP,
            )
            ->toDecimal()
    )->toBe('5.01');
});

it('converts money to string', function () {
    $money = Money::of('1250.50', Currency::BDT);

    expect((string) $money)->toBe('1250.50 BDT');
});

it('serializes money to json', function () {
    $money = Money::of('1250.50', Currency::BDT);

    expect($money->jsonSerialize())->toBe([
        'amount' => '1250.50',
        'minor_amount' => 125050,
        'currency' => 'BDT',
    ]);
});

it('can be encoded directly as json', function () {
    $money = Money::of('1250.50', Currency::BDT);

    expect(json_decode(
        json_encode($money, JSON_THROW_ON_ERROR),
        true,
        flags: JSON_THROW_ON_ERROR,
    ))->toBe([
        'amount' => '1250.50',
        'minor_amount' => 125050,
        'currency' => 'BDT',
    ]);
});

it('serializes consumer-defined currencies', function () {
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

    $money = Money::of('12.345', $currency);

    expect($money->jsonSerialize())->toBe([
        'amount' => '12.345',
        'minor_amount' => 12345,
        'currency' => 'ABC',
    ]);
});
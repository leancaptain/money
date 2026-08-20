# LeanCaptain Money

A small, framework-agnostic PHP money value object for representing, comparing, and calculating monetary values safely using integer minor units.

The package is intentionally lightweight and has no runtime dependencies.

## Requirements

* PHP 8.4+

## Installation

Install via Composer:

```bash
composer require leancaptain/money
```

## Quick Start

```php
use LeanCaptain\Money\Currency;
use LeanCaptain\Money\Money;

$money = Money::of('1250.50', Currency::BDT);

$money->minorAmount(); // 125050
$money->toDecimal();   // "1250.50"
$money->currency();    // Currency::BDT
```

Monetary amounts should be provided as integers or decimal strings rather than floats.

```php
Money::of('19.99', Currency::USD);
Money::of(100, Currency::BDT);
```

## Minor Units

Money is stored internally using integer minor units.

```php
$money = Money::of('125.50', Currency::BDT);

$money->minorAmount(); // 12550
```

You can also construct money directly from minor units:

```php
$money = Money::fromMinor(12550, Currency::BDT);

$money->toDecimal(); // "125.50"
```

Currencies determine their own minor-unit precision.

```php
Currency::BDT->minorUnit(); // 2
Currency::USD->minorUnit(); // 2
Currency::JPY->minorUnit(); // 0
```

## Built-in Currencies

The package ships with a set of commonly used currencies:

* AED
* AUD
* BDT
* CAD
* CNY
* EUR
* GBP
* INR
* JPY
* SAR
* SGD
* USD

Applications are not required to use the built-in `Currency` enum.

## Custom Currencies

Implement `CurrencyContract` to provide an application-specific currency:

```php
use LeanCaptain\Money\Contracts\CurrencyContract;

enum AppCurrency: string implements CurrencyContract
{
    case BDT = 'BDT';
    case USD = 'USD';

    public function code(): string
    {
        return $this->value;
    }

    public function minorUnit(): int
    {
        return 2;
    }
}
```

Then use it normally:

```php
$money = Money::of('100.00', AppCurrency::BDT);
```

Currency compatibility is determined by both the currency code and minor-unit precision.

## Arithmetic

### Addition

```php
$total = Money::of('100.50', Currency::BDT)
    ->add(Money::of('50.25', Currency::BDT));

$total->toDecimal(); // "150.75"
```

### Subtraction

```php
$balance = Money::of('100.00', Currency::BDT)
    ->subtract(Money::of('25.50', Currency::BDT));

$balance->toDecimal(); // "74.50"
```

### Multiplication

```php
$result = Money::of('100.00', Currency::BDT)
    ->multiply('1.15');

$result->toDecimal(); // "115.00"
```

### Division

```php
$result = Money::of('100.00', Currency::BDT)
    ->divide(4);

$result->toDecimal(); // "25.00"
```

Arithmetic operations return new `Money` instances. Existing instances are never mutated.

## Rounding

The default rounding behavior is intentionally strict.

If a value cannot be represented exactly using the currency's minor-unit precision, a `RoundingRequired` exception is thrown.

```php
use LeanCaptain\Money\Exceptions\RoundingRequired;

Money::of('10.999', Currency::BDT);
// throws RoundingRequired
```

Rounding can be requested explicitly:

```php
use LeanCaptain\Money\RoundingMode;

$money = Money::of(
    '10.999',
    Currency::BDT,
    RoundingMode::HALF_UP,
);

$money->toDecimal(); // "11.00"
```

Supported rounding modes:

* `RoundingMode::REJECT`
* `RoundingMode::HALF_UP`
* `RoundingMode::DOWN`

The same rules apply to calculations that require rounding:

```php
$result = Money::of('10.01', Currency::BDT)
    ->divide(2, RoundingMode::HALF_UP);

$result->toDecimal(); // "5.01"
```

## Comparisons

```php
$first = Money::of('100.00', Currency::BDT);
$second = Money::of('50.00', Currency::BDT);

$first->equals($second);               // false
$first->isGreaterThan($second);        // true
$first->isGreaterThanOrEqual($second); // true
$first->isLessThan($second);           // false
$first->isLessThanOrEqual($second);    // false
```

You can also inspect the state of an amount:

```php
$money->isZero();
$money->isPositive();
$money->isNegative();
```

Ordering or arithmetic between incompatible currencies throws a `CurrencyMismatch` exception.

```php
Money::of('100', Currency::BDT)
    ->add(Money::of('100', Currency::USD));

// throws CurrencyMismatch
```

The package does not perform currency conversion.

## Other Operations

Negate an amount:

```php
Money::of('100', Currency::BDT)
    ->negate()
    ->toDecimal();

// "-100.00"
```

Get the absolute value:

```php
Money::of('-100', Currency::BDT)
    ->absolute()
    ->toDecimal();

// "100.00"
```

## Money Factory

`MoneyFactory` can be used when an application has default currency and rounding requirements.

```php
use LeanCaptain\Money\Currency;
use LeanCaptain\Money\MoneyFactory;
use LeanCaptain\Money\RoundingMode;

$factory = new MoneyFactory(
    Currency::BDT,
    RoundingMode::HALF_UP,
);

$money = $factory->of('100.50');
```

This avoids repeating application defaults while keeping `Money` itself free from global configuration.

You can also construct values from minor units:

```php
$money = $factory->fromMinor(10050);

$money->toDecimal(); // "100.50"
```

## String Representation

`Money` implements `Stringable`.

```php
$money = Money::of('1250.50', Currency::BDT);

(string) $money;
// "1250.50 BDT"
```

The string representation is intentionally predictable and locale-independent.

Locale-aware presentation formatting should be handled separately by the consuming application.

## JSON Serialization

`Money` implements `JsonSerializable`.

```php
$money = Money::of('1250.50', Currency::BDT);

json_encode($money);
```

Produces:

```json
{
    "amount": "1250.50",
    "currency": "BDT"
}
```

The decimal amount is serialized as a string to avoid introducing floating-point representation into the monetary value.

## Exceptions

The package provides dedicated exceptions for common invalid operations:

### `InvalidMoneyAmount`

Thrown when an amount has invalid syntax.

```php
Money::of('invalid', Currency::BDT);
```

### `RoundingRequired`

Thrown when an otherwise valid value cannot be represented exactly and the rounding mode is `REJECT`.

```php
Money::of('10.999', Currency::BDT);
```

### `CurrencyMismatch`

Thrown when arithmetic or ordering is attempted between incompatible currencies.

```php
Money::of('100', Currency::BDT)
    ->add(Money::of('100', Currency::USD));
```

Division by zero throws PHP's native `DivisionByZeroError`.

## Design Principles

LeanCaptain Money intentionally follows a few simple rules:

* Monetary values are stored as integer minor units.
* Core monetary operations do not use floating-point arithmetic.
* Currency precision is explicit.
* Rounding is explicit when required.
* Money values are immutable.
* Different currencies cannot accidentally be combined.
* Consumer applications may provide their own currencies.
* The core package is framework-agnostic.
* The package has no runtime dependencies.

The goal is to provide a small, predictable money primitive rather than a complete financial or accounting framework.

## Testing

Run the test suite with:

```bash
composer test
```

## License

LeanCaptain Money is open-sourced software licensed under the MIT license.

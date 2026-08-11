# Roadmap

`leancaptain/money` is a small, framework-agnostic Money library for PHP.

The roadmap is intentionally conservative. New features should solve repeated real-world problems without weakening the package's strict money, currency, and rounding semantics.

## Current focus

The current priority is stabilizing the core API through real application usage.

Key areas include:

* strengthening edge-case coverage
* improving documentation and examples
* refining exception behavior where necessary
* validating parsing, arithmetic, rounding, comparison, and serialization semantics
* preserving support for consumer-defined currencies through `CurrencyContract`

## Possible next steps

Future releases may explore:

* richer parsing APIs where they improve clarity
* formatting primitives that remain framework- and locale-library agnostic
* improved ergonomics for application-defined currencies
* small comparison or convenience APIs proven useful across multiple applications
* additional safeguards discovered through production usage

These ideas are not guaranteed features and may change as the package evolves.

## Out of scope

The package is not intended to become:

* an exchange-rate or FX service
* an accounting library
* a database abstraction
* a Laravel package
* an exhaustive ISO currency database
* a localization framework
* a payment-processing SDK

Those concerns should remain outside the core Money domain.

## Versioning direction

### `0.0.x`

Early stabilization releases.

Expect bug fixes, documentation improvements, additional tests, and small API corrections where needed.

### `0.1.x`

A more mature pre-1.0 API shaped by usage across real applications.

Potential focus areas include custom-currency ergonomics, parsing, formatting boundaries, and API consistency.

### `1.0.0`

The goal for `1.0.0` is a stable, well-tested public API with predictable semantics for:

* money construction
* integer minor-unit representation
* currencies
* rounding
* arithmetic
* comparisons
* serialization
* exceptions

## Guiding principle

Features should be extracted from repeated application needs rather than added speculatively.

If a capability is framework-specific, application-specific, or belongs to another financial domain, it should remain outside `leancaptain/money`.

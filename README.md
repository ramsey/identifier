<h1 align="center">ramsey/identifier</h1>

<p align="center">
    <strong>A PHP library for generating and working with identifiers</strong>
</p>

<p align="center">
    <a href="https://github.com/ramsey/identifier"><img src="https://img.shields.io/badge/source-ramsey/identifier-blue.svg?style=flat-square" alt="Source Code"></a>
    <a href="https://packagist.org/packages/ramsey/identifier"><img src="https://img.shields.io/packagist/v/ramsey/identifier.svg?style=flat-square&label=release" alt="Download Package"></a>
    <a href="https://php.net"><img src="https://img.shields.io/packagist/dependency-v/ramsey/identifier/php-64bit?style=flat-square&colorB=%238892BF" alt="PHP Programming Language"></a>
    <a href="https://github.com/ramsey/identifier/blob/main/COPYING.LESSER"><img src="https://img.shields.io/packagist/l/ramsey/identifier.svg?style=flat-square&colorB=darkcyan" alt="Read License"></a>
    <a href="https://github.com/ramsey/identifier/actions/workflows/continuous-integration.yml"><img src="https://img.shields.io/github/actions/workflow/status/ramsey/identifier/continuous-integration.yml?branch=main&style=flat-square&logo=github" alt="Build Status"></a>
    <a href="https://codecov.io/gh/ramsey/identifier"><img src="https://img.shields.io/codecov/c/gh/ramsey/identifier?label=codecov&logo=codecov&style=flat-square" alt="Codecov Code Coverage"></a>
</p>

## About

ramsey/identifier is a PHP library designed for generating, parsing, and working with a variety of unique identifiers.
It provides an object-oriented interface for several industry-standard identifier formats—including UUIDs, ULIDs, and
Snowflake IDs. This library emphasizes standards-compliance, interoperability, and developer ergonomics, making it easy
to create and manipulate identifiers in modern PHP applications.

This project adheres to a [code of conduct](CODE_OF_CONDUCT.md). By participating in this project and its community, you
are expected to uphold this code.

## Installation

Install this package as a dependency using [Composer](https://getcomposer.org).

``` bash
composer require ramsey/identifier
```

## Usage

ramsey/identifier provides ways to generate industry-standard identifiers, such as UUIDs, ULIDs, and Snowflake IDs. You
can do this using the provided factory classes:

```php
use Ramsey\Identifier\Snowflake\GenericSnowflakeFactory;
use Ramsey\Identifier\Snowflake\Epoch;
use Ramsey\Identifier\Ulid\UlidFactory;
use Ramsey\Identifier\Uuid\UuidFactory;

// Create a UUID.
$uuid = (new UuidFactory())->create();

// Create a ULID.
$ulid = (new UlidFactory())->create();

// Create a Snowflake.
$snowflake = (new GenericSnowflakeFactory(1, Epoch::Unix))->create();
```

With `UuidFactory`, you may create UUIDs of different versions:

```php
use Ramsey\Identifier\Uuid\NamespaceId;

$factory = new UuidFactory();

// Create random UUIDs (version 4).
$uuidV4 = $factory->v4();

// Create named-based UUIDs using SHA-1 hashing (version 5).
$uuidV5 = $factory->v5(NamespaceId::Url, 'https://example.com/post/1234');

// Create Unix Epoch time-based UUIDs (version 7).
$uuidV7 = $factory->v7();
```

You may also parse existing identifiers:

```php
// Parse a UUID.
$uuid = (new UuidFactory())->createFromString('01977bea-d1c0-7154-87bb-6550974155c2');

// Parse a ULID.
$ulid = (new UlidFactory())->createFromString('01JXXYNME0E5A8FEV5A2BM2NE2');

// Parse a Snowflake ID.
$snowflake = (new GenericSnowflakeFactory(1, Epoch::Unix))->createFromInteger(7340580095540599922);
```

Each of these identifiers happen to contain the same timestamp, which we can retrieve:

```php
echo $uuid->getDateTime()->format('Y-m-d H:i:s.v P') . "\n";
echo $ulid->getDateTime()->format('Y-m-d H:i:s.v P') . "\n";
echo $snowflake->getDateTime()->format('Y-m-d H:i:s.v P') . "\n";
```

This will print:

```
2025-06-17 03:24:36.160 +00:00
2025-06-17 03:24:36.160 +00:00
2025-06-17 03:24:36.160 +00:00
```

The UUID and ULID shown above have the same underlying byte values. Every version 7 UUID can also be expressed as a
ULID, but not every ULID can be converted into a version 7 UUID. Similarly, Snowflake identifiers and ULIDs both encode
a timestamp and randomness, but their formats are not directly interchangeable with UUIDs or each other.

```php
if ($uuid->equals($ulid)) {
    echo "They are equal!\n";
}

if ($uuid->equals($snowflake)) {
    echo "The timestamp is the same, but they aren't equal.\n"
}
```

## Contributing

Contributions are welcome! To contribute, please familiarize yourself with [CONTRIBUTING.md](CONTRIBUTING.md).

## Coordinated Disclosure

Keeping user information safe and secure is a top priority, and we welcome the contribution of external security
researchers. If you believe you've found a security issue in software that is maintained in this repository, please read
[SECURITY.md](SECURITY.md) for instructions on submitting a vulnerability report.

## Copyright and License

ramsey/identifier is copyright © [Ben Ramsey](https://ramsey.dev) and [Contributors](https://github.com/ramsey/identifier/graphs/contributors)
and licensed for use under the terms of the GNU Lesser General Public License (LGPL-3.0-or-later) as published by the
Free Software Foundation. Please see [COPYING.LESSER](COPYING.LESSER), [COPYING](COPYING), and [NOTICE](NOTICE) for more
information.

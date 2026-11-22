# ramsey/identifier Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add `getSortKey()` to every identifier. It returns a string of bytes that places the identifier in order among other identifiers. A Microsoft GUID's sort key is in RFC 9562 layout, so it differs from the bytes `toBytes()` returns.
- Add `Ramsey\Identifier\Exception\NotConvertible`, which `getDateTime()` throws when an identifier cannot return a date-time.

### Changed

- Require PHP 8.3 or later.
- **Breaking:** Require identifier/identifier `^0.6.1`.
- **Breaking:** `Ramsey\Identifier\Exception\NotComparable` extends `Ramsey\Identifier\Exception\InvalidArgument` in place of `RuntimeException`.
- `Uuid`, `Ulid`, and `Snowflake` now extend `Identifier\StringIdentifier`, `JsonSerializable`, and `Stringable`.
- `getDateTime()` on `MicrosoftGuid` and `UntypedUuid` throws `NotConvertible` for a UUID that has no date-time. It extends `BadMethodCall`, so code that catches `BadMethodCall` is unaffected.
- **Breaking:** The snowflake classes and factories refuse a string with a leading zero, such as `0123`.
- **Breaking:** `createFromInteger()` on every UUID factory and on the ULID factory refuses a string that is not decimal digits with no sign and no leading zero, such as an empty string, `+1`, `-0`, `0123`, `1.0`, or `1e3`.
- **Breaking:** `compareTo()` given any `Identifier\Identifier` compares the two sort keys and does not throw.
- **Breaking:** `compareTo()` throws `NotComparable` for `null`, numbers, booleans, objects that are neither identifiers nor `Stringable`, and strings (or `Stringable` objects) not in the identifier's own string form; raw bytes and bare hexadecimal strings are no longer compared.
- **Breaking:** `equals()` returns `true` only for an identifier of the same kind (UUID, ULID, or snowflake) with the same sort key, or a string in the identifier's own string form with the same value; a UUID and a ULID with the same bytes are no longer equal.
- **Breaking:** `toUuid()` on a ULID returns an `UntypedUuid` with the same bytes as the ULID. Call `toTypedUuid()` on it to get a typed UUID, such as `UuidV7` or `NonstandardUuid`.
- **Breaking:** `UntypedUuid` and `NonstandardUuid` read a byte string in RFC 9562 layout, whatever its variant, so the bytes have the version and the value their string form shows. Use `MicrosoftGuid` or `MicrosoftGuidFactory` for bytes in Microsoft GUID byte order; when `toTypedUuid()` returns a `MicrosoftGuid` for bytes of the Microsoft variant, its `toBytes()` is in GUID byte order.
- **Breaking:** A version 3 or 5 UUID created from a `MicrosoftGuid` namespace hashes the namespace by its value in RFC 9562 layout, so it is the same UUID as one created from the namespace's string form; a version 3 or 5 UUID stored from a `MicrosoftGuid` namespace by an earlier release differs.
- **Breaking:** `UlidFactory::createFromUuid()` given a `MicrosoftGuid` returns a ULID with the GUID's value, the same value as its string form; a ULID created from a `MicrosoftGuid` by an earlier release differs.
- `getDateTime()` on a version 1, 2, or 6 UUID whose timestamp is not a whole number of microseconds rounds to the nearest microsecond, with a half rounded up, on both sides of the Unix Epoch.

### Fixed

- `createFromInteger()` and `toInteger()` on UUIDs and ULIDs keep the exact value of an integer just above `PHP_INT_MAX`, such as `9223372036854775808`.
- `getDateTime()` on a snowflake whose epoch offset puts the date-time out of range throws `NotConvertible` in place of a PHP error.
- `getDateTime()` on a snowflake returned the wrong date-time when the date-time was less than one second before the Unix Epoch.
- An `UntypedUuid` created from bytes of the Microsoft variant is equal to the `MicrosoftGuid` that `toTypedUuid()` returns, and the two have the same string form.
- A version 1, 2, or 6 UUID, or a snowflake, created from a date-time before the Unix Epoch that has a fraction of a second has that date-time.
- A snowflake value above the largest unsigned 64-bit integer was accepted when written with a leading zero.
- Identifiers whose bytes look like numbers to PHP no longer compare as equal.
- `MonotonicBytesGenerator::bytes()` throws `InvalidArgument` for a date-time before 1970-01-01 or after 10889-08-02 05:31:50.655 UTC, in place of bytes with a wrong timestamp.
- GUIDs sort in the order of their string form.
- `GenericSnowflakeFactory::createFromDateTime()` with a negative epoch offset names the factory's epoch and its latest date-time correctly in the exceptions it throws, in place of a PHP error.
- `Rfc4122ClockSequence` compares date-times before the Unix Epoch that have a fraction of a second in the right order, so its sequence rises only when the date-time moves backward.
- `UlidFactory::createFromUuid()` given a `MicrosoftGuid` returns a ULID with the GUID's value, so the two compare as equal.
- `UuidV3Factory` and `UuidV5Factory` given a `MicrosoftGuid` namespace create the same UUID as for any other UUID with the same string form.
- `InMemoryCache` evicts the least recently used item when its clock is before the Unix Epoch.
- `UuidFactory::createFromInteger()` given an integer greater than 128 bits throws `InvalidArgument` with a message that names the integer.
- Unserializing an identifier whose serialized data has a value of the wrong type, or no value, throws `InvalidArgument` in place of a PHP `TypeError`.

## [0.1.0] - 2025-06-19

### Added

- Implement the [Common Interfaces and Factories for Identifiers](https://github.com/php-identifier/identifier) specification.
- Support universally unique identifiers (UUIDs) as defined in [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562).
- Support universally unique lexicographically sortable identifiers (ULIDs) as defined in [ulid/spec](https://github.com/ulid/spec).
- Support [snowflake identifiers](https://en.wikipedia.org/wiki/Snowflake_ID), including popular variants for Twitter, Instagram, Discord, and Mastodon.

[unreleased]: https://github.com/ramsey/identifier/compare/0.1.0...HEAD
[0.1.0]: https://github.com/ramsey/identifier/commits/0.1.0/

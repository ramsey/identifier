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

### Fixed

- `createFromInteger()` and `toInteger()` on UUIDs and ULIDs keep the exact value of an integer just above `PHP_INT_MAX`, such as `9223372036854775808`.
- `getDateTime()` on a snowflake whose epoch offset puts the date-time out of range throws `NotConvertible` in place of a PHP error.
- `getDateTime()` on a snowflake returned the wrong date-time when the date-time was less than one second before the Unix Epoch.
- A snowflake value above the largest unsigned 64-bit integer was accepted when written with a leading zero.
- Identifiers whose bytes look like numbers to PHP no longer compare as equal.
- GUIDs sort in the order of their string form.

## [0.1.0] - 2025-06-19

### Added

- Implement the [Common Interfaces and Factories for Identifiers](https://github.com/php-identifier/identifier) specification.
- Support universally unique identifiers (UUIDs) as defined in [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562).
- Support universally unique lexicographically sortable identifiers (ULIDs) as defined in [ulid/spec](https://github.com/ulid/spec).
- Support [snowflake identifiers](https://en.wikipedia.org/wiki/Snowflake_ID), including popular variants for Twitter, Instagram, Discord, and Mastodon.

[unreleased]: https://github.com/ramsey/identifier/compare/0.1.0...HEAD
[0.1.0]: https://github.com/ramsey/identifier/commits/0.1.0/
